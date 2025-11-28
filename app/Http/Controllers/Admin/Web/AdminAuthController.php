<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if (!$user->is_admin) {
            throw ValidationException::withMessages([
                'email' => ['Vous n\'avez pas les droits d\'accès.'],
            ]);
        }

        // Vérifier si le compte est bloqué
        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte a été bloqué. Veuillez contacter un administrateur.'],
            ]);
        }

        // Authentifier l'utilisateur avec la session web
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Afficher le formulaire de demande de réinitialisation de mot de passe
     */
    public function showForgotPasswordForm()
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Envoyer le lien de réinitialisation de mot de passe
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Cette adresse email n\'existe pas dans notre système.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Vérifier si c'est un admin
        if (!$user || !$user->is_admin) {
            return back()->withErrors(['email' => 'Cette adresse email n\'est pas associée à un compte administrateur.']);
        }

        // Vérifier si le compte est bloqué
        if ($user->is_blocked) {
            return back()->withErrors(['email' => 'Impossible de réinitialiser le mot de passe d\'un compte bloqué.']);
        }

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', 'Un lien de réinitialisation de mot de passe a été envoyé à votre adresse email.');
            }

            return back()->withErrors(['email' => __($status)]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de réinitialisation', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer plus tard.']);
        }
    }

    /**
     * Afficher le formulaire de réinitialisation de mot de passe
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.exists' => 'Cette adresse email n\'existe pas dans notre système.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Vérifier si c'est un admin
        if (!$user || !$user->is_admin) {
            return back()->withErrors(['email' => 'Cette adresse email n\'est pas associée à un compte administrateur.']);
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->password = Hash::make($password);
                    $user->save();
                    // Supprimer tous les tokens existants pour forcer une nouvelle connexion
                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()->route('admin.login')->with('status', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            }

            return back()->withErrors(['email' => [__($status)]]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Une erreur est survenue lors de la réinitialisation. Veuillez réessayer plus tard.']);
        }
    }
}
