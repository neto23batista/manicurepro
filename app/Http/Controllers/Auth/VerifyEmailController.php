<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /** Tela "Verifique seu e-mail" */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->dashboardFor($request->user()));
        }

        return view('auth.verify-email');
    }

    /** Endpoint que recebe o clique no link do e-mail */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->dashboardFor($request->user()))->with('info', 'E-mail já verificado.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect($this->dashboardFor($request->user()))
            ->with('success', 'E-mail verificado com sucesso! 💅');
    }

    /** Reenviar e-mail de verificação */
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->dashboardFor($request->user()));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link de verificação reenviado!');
    }

    private function dashboardFor($user): string
    {
        return match ($user->role) {
            'admin'             => route('admin.dashboard'),
            'dono', 'atendente' => route('dono.dashboard'),
            'manicure'          => route('manicure.dashboard'),
            default             => route('cliente.dashboard'),
        };
    }
}
