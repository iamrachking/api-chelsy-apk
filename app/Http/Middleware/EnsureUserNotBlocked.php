<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vérifier si l'utilisateur est bloqué
        if ($user && $user->is_blocked) {
            // Supprimer tous les tokens de l'utilisateur pour le déconnecter
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été bloqué. Veuillez contacter un administrateur pour plus d\'informations.',
                'code' => 'ACCOUNT_BLOCKED',
            ], 403);
        }

        return $next($request);
    }
}

