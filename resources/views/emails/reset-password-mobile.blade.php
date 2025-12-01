@extends('emails.layout')

@section('email-title', 'Réinitialisation du mot de passe')

@section('header-subtitle', 'Réinitialisation de votre mot de passe')

@section('email-content')
    <p>Bonjour <strong>{{ $user->firstname }} {{ $user->lastname }}</strong>,</p>
    
    <p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte CHELSY Restaurant.</p>
    
    <div class="info-box">
        <h2 style="margin-top: 0; color: #1f2937;">Pour réinitialiser votre mot de passe dans l'application :</h2>
        
        <p>Vous devez entrer les informations suivantes dans l'application mobile :</p>
        
        <div class="token-box">
            <strong>Token de réinitialisation :</strong><br>
            <span style="font-size: 14px; font-weight: bold; color: #3b82f6;">{{ $token }}</span>
        </div>
        
        <div class="token-box">
            <strong>Email :</strong><br>
            <span style="font-size: 14px;">{{ $user->email }}</span>
        </div>
        
        <p style="margin-top: 20px; font-size: 14px;">
            <strong>Instructions :</strong><br>
            1. Ouvrez l'application CHELSY Restaurant<br>
            2. Allez dans la section "Réinitialiser le mot de passe"<br>
            3. Entrez votre email et le token ci-dessus<br>
            4. Définissez votre nouveau mot de passe
        </p>
    </div>
    
    <div class="warning">
        <strong>⚠️ Important :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Ce token est valable pendant <strong>60 minutes</strong> uniquement.</li>
            <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</li>
            <li>Pour des raisons de sécurité, ne partagez jamais ce token avec personne.</li>
            <li>Entrez le token exactement comme indiqué (sensible à la casse).</li>
        </ul>
    </div>
@endsection

