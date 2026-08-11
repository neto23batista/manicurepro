<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $salaoId = (int) (auth()->user()->salao_id ?? \App\Models\Salao::principalId());

        $userIds = User::query()
            ->where(function ($q) use ($salaoId) {
                $q->where('salao_id', $salaoId)->orWhere('role', 'admin');
            })
            ->pluck('id');

        $logs = AuditLog::query()
            ->with('user:id,name,email,role')
            ->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)->orWhereNull('user_id');
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->user_id))
            ->when($request->filled('de'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('de')))
            ->when($request->filled('ate'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('ate')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $usuarios = User::query()
            ->where('salao_id', $salaoId)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('dono.auditoria.index', compact('logs', 'usuarios'));
    }
}
