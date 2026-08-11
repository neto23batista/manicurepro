<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Redimensiona e comprime uploads de imagem.
 * Preferência: Intervention Image (se instalado) → GD → store cru.
 *
 * Intervention é opcional — referências via string para não exigir o pacote em análise estática.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1920;

    public const MAX_HEIGHT = 1920;

    public const JPEG_QUALITY = 82;

    public const WEBP_QUALITY = 80;

    public const PNG_COMPRESSION = 6;

    public function isAvailable(): bool
    {
        return $this->hasIntervention() || extension_loaded('gd');
    }

    /**
     * Grava a imagem otimizada no disco e devolve o caminho relativo.
     */
    public function storeOptimized(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $directory = trim($directory, '/');

        try {
            if ($this->hasIntervention()) {
                $path = $this->storeWithIntervention($file, $directory, $disk);
                if ($path !== null) {
                    return $path;
                }
            }

            if (extension_loaded('gd')) {
                $path = $this->storeWithGd($file, $directory, $disk);
                if ($path !== null) {
                    return $path;
                }
            }
        } catch (Throwable) {
            // Fallback seguro: arquivo original.
        }

        return $file->store($directory, $disk);
    }

    private function hasIntervention(): bool
    {
        return $this->classAvailable($this->interventionFqcn(['ImageManager']))
            || $this->classAvailable($this->interventionFqcn(['ImageManagerStatic']));
    }

    /**
     * Monta FQCN Intervention em runtime (evita class-string estático para PHPStan).
     *
     * @param  list<string>  $parts
     */
    private function interventionFqcn(array $parts): string
    {
        return implode('\\', array_merge(['Intervention', 'Image'], $parts));
    }

    private function classAvailable(string $class): bool
    {
        try {
            new \ReflectionClass($class);

            return true;
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * @param  list<mixed>  $args
     */
    private function newInterventionInstance(string $class, array $args = []): ?object
    {
        if (! $this->classAvailable($class)) {
            return null;
        }

        try {
            $ref = new \ReflectionClass($class);

            return $args === []
                ? $ref->newInstance()
                : $ref->newInstanceArgs($args);
        } catch (Throwable) {
            return null;
        }
    }

    private function storeWithIntervention(UploadedFile $file, string $directory, string $disk): ?string
    {
        $managerClass = $this->interventionFqcn(['ImageManager']);
        if ($this->classAvailable($managerClass)) {
            $gdDriver = $this->interventionFqcn(['Drivers', 'Gd', 'Driver']);
            $imagickDriver = $this->interventionFqcn(['Drivers', 'Imagick', 'Driver']);
            $driverClass = $this->classAvailable($gdDriver) ? $gdDriver : $imagickDriver;

            if (! $this->classAvailable($driverClass)) {
                return null;
            }

            $driver = $this->newInterventionInstance($driverClass);
            if ($driver === null) {
                return null;
            }

            $manager = $this->newInterventionInstance($managerClass, [$driver]);
            if ($manager === null || ! method_exists($manager, 'read')) {
                return null;
            }

            $image = $manager->read($file->getRealPath());
            if (! is_object($image) || ! method_exists($image, 'scaleDown')) {
                return null;
            }

            $image->scaleDown(width: self::MAX_WIDTH, height: self::MAX_HEIGHT);

            $mime = strtolower((string) $file->getMimeType());
            if (str_contains($mime, 'png') && method_exists($image, 'toPng')) {
                $ext = 'png';
                $encoded = (string) $image->toPng();
            } elseif (str_contains($mime, 'webp') && method_exists($image, 'toWebp')) {
                $ext = 'webp';
                $encoded = (string) $image->toWebp(quality: self::WEBP_QUALITY);
            } elseif (method_exists($image, 'toJpeg')) {
                $ext = 'jpg';
                $encoded = (string) $image->toJpeg(quality: self::JPEG_QUALITY);
            } else {
                return null;
            }

            return $this->persistBinary($directory, $ext, $encoded, $disk);
        }

        $staticClass = $this->interventionFqcn(['ImageManagerStatic']);
        if (! $this->classAvailable($staticClass)) {
            return null;
        }

        // Intervention v2 (legado)
        if (! method_exists($staticClass, 'make')) {
            return null;
        }

        $image = $staticClass::make($file->getRealPath());
        if (! is_object($image) || ! method_exists($image, 'resize') || ! method_exists($image, 'encode')) {
            return null;
        }

        $image->resize(self::MAX_WIDTH, self::MAX_HEIGHT, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $mime = strtolower((string) $file->getMimeType());
        [$ext, $format, $quality] = match (true) {
            str_contains($mime, 'png')  => ['png', 'png', self::PNG_COMPRESSION],
            str_contains($mime, 'webp') => ['webp', 'webp', self::WEBP_QUALITY],
            default                     => ['jpg', 'jpg', self::JPEG_QUALITY],
        };

        return $this->persistBinary($directory, $ext, (string) $image->encode($format, $quality), $disk);
    }

    private function storeWithGd(UploadedFile $file, string $directory, string $disk): ?string
    {
        $realPath = $file->getRealPath();
        $info = @getimagesize($realPath);

        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($realPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($realPath)
                : false,
            default => false,
        };

        if ($src === false) {
            return null;
        }

        $scale = min(1.0, self::MAX_WIDTH / max(1, $width), self::MAX_HEIGHT / max(1, $height));
        $newW = (int) max(1, round($width * $scale));
        $newH = (int) max(1, round($height * $scale));

        $dst = imagecreatetruecolor($newW, $newH);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($src);

        $ext = match ($type) {
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            default        => 'jpg',
        };

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imgopt_'.Str::uuid().'.'.$ext;

        $ok = match ($type) {
            IMAGETYPE_PNG  => imagepng($dst, $tmp, self::PNG_COMPRESSION),
            IMAGETYPE_WEBP => function_exists('imagewebp')
                ? imagewebp($dst, $tmp, self::WEBP_QUALITY)
                : false,
            default => imagejpeg($dst, $tmp, self::JPEG_QUALITY),
        };

        imagedestroy($dst);

        if ($ok === false || ! is_file($tmp) || filesize($tmp) < 1) {
            @unlink($tmp);

            return null;
        }

        $filename = Str::uuid().'.'.$ext;
        $path = Storage::disk($disk)->putFileAs($directory, new File($tmp), $filename);
        @unlink($tmp);

        if ($path === false || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return $path;
    }

    private function persistBinary(string $directory, string $ext, string $binary, string $disk): ?string
    {
        if ($binary === '') {
            return null;
        }

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imgopt_'.Str::uuid().'.'.$ext;
        if (file_put_contents($tmp, $binary) === false) {
            return null;
        }

        $filename = Str::uuid().'.'.$ext;
        $path = Storage::disk($disk)->putFileAs($directory, new File($tmp), $filename);
        @unlink($tmp);

        if ($path === false || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return $path;
    }
}
