<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Aplica uma Content-Security-Policy com nonce por requisição.
 *
 * - Gera um nonce único e o injeta em todas as tags <script> inline da resposta
 *   HTML (e também o compartilha com o Vite), evitando 'unsafe-inline' no script-src.
 * - Estilos usam 'unsafe-inline' (necessário para os atributos style= do Bootstrap
 *   e do tema); injeção de script — vetor principal de XSS — fica travada.
 *
 * Handlers inline (onclick=, onsubmit=...) NÃO são permitidos por esta política;
 * a UI usa listeners delegados (data-*) no bundle, que é servido por 'self'.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        if (! config('manicure.security.csp_enabled', true)) {
            return $next($request);
        }

        $nonce = base64_encode(random_bytes(16));

        // O Vite usa o mesmo nonce nas tags que gera (@vite).
        Vite::useCspNonce($nonce);
        // Disponível nas views como $cspNonce, caso algum script precise referenciá-lo.
        view()->share('cspNonce', $nonce);

        /** @var SymfonyResponse $response */
        $response = $next($request);

        $this->aplicarNonceNoCorpo($response, $nonce);

        $header = config('manicure.security.csp_report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, $this->montarPolitica($nonce));

        return $response;
    }

    /**
     * Injeta nonce="..." em cada <script> inline da resposta HTML.
     * Tags externas (<script src=...>) também recebem o atributo — inofensivo,
     * pois são liberadas por host-source na política.
     */
    private function aplicarNonceNoCorpo(SymfonyResponse $response, string $nonce): void
    {
        if (! $response instanceof Response) {
            return; // ignora downloads/streams/redirects
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();
        if ($content === false || ! str_contains($content, '<script')) {
            return;
        }

        // Só casa a abertura real da tag (<script seguido de espaço ou >),
        // sem mexer em scripts que já tenham nonce.
        $novo = preg_replace_callback(
            '/<script(?![^>]*\bnonce=)(?=[\s>])/i',
            fn () => '<script nonce="' . $nonce . '"',
            $content
        );

        // setContent() do Illuminate sobrescreve $response->original (a View);
        // preservamos para não quebrar viewData()/testes e introspecção da resposta.
        $original = $response->getOriginalContent();
        $response->setContent($novo);
        $response->original = $original;
    }

    private function montarPolitica(string $nonce): string
    {
        $diretivas = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "worker-src 'self'",
            "manifest-src 'self'",
        ];

        return implode('; ', $diretivas);
    }
}
