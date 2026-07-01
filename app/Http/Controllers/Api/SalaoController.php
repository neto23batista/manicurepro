<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManicureResource;
use App\Http\Resources\SalaoResource;
use App\Http\Resources\ServicoResource;
use App\Models\Manicure;
use App\Models\Salao;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalaoController extends Controller
{
    public function __construct(private AgendaService $agendaService) {}

    public function index(): AnonymousResourceCollection
    {
        $saloes = Salao::where('ativo', true)
            ->withCount('manicures')
            ->orderBy('nome')
            ->get();

        return SalaoResource::collection($saloes);
    }

    public function show(string $slug): SalaoResource
    {
        $salao = Salao::where('slug', $slug)
            ->where('ativo', true)
            ->withCount('manicures')
            ->firstOrFail();

        return new SalaoResource($salao);
    }

    public function servicos(string $slug): AnonymousResourceCollection
    {
        $salao = Salao::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $servicos = $salao->servicos()
            ->where('disponivel_online', true)
            ->with('categoria')
            ->orderBy('nome')
            ->get();

        return ServicoResource::collection($servicos);
    }

    public function manicures(string $slug): AnonymousResourceCollection
    {
        $salao = Salao::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $manicures = $salao->manicures()->get();

        return ManicureResource::collection($manicures);
    }

    public function slots(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'manicure_id' => ['required', 'exists:manicures,id'],
            'data'        => ['required', 'date', 'after_or_equal:today'],
            'duracao'     => ['required', 'integer', 'min:5'],
        ]);

        Salao::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $manicure = Manicure::with(['salao.configuracao', 'disponibilidades', 'folgas'])
            ->findOrFail($request->manicure_id);

        $slots = $this->agendaService->getSlotsDisponiveis(
            $manicure,
            Carbon::parse($request->data),
            (int) $request->duracao
        );

        return response()->json(['slots' => $slots]);
    }
}
