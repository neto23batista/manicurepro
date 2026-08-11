<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Persiste (ou atualiza) a subscription Push do navegador do usuário autenticado.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'        => ['required', 'string', 'max:500'],
            'keys.p256dh'     => ['required', 'string', 'max:255'],
            'keys.auth'       => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'          => $request->user()->id,
                'public_key'       => $data['keys']['p256dh'],
                'auth_token'       => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent'       => substr((string) $request->userAgent(), 0, 255) ?: null,
            ],
        );

        return response()->json([
            'ok' => true,
            'id' => $subscription->id,
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Remove a subscription pelo endpoint (unsubscribe no cliente).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
