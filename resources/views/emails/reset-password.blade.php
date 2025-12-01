@extends('emails.layout')

@section('email-title', 'Réinitialisation du mot de passe')

@section('header-subtitle', 'Réinitialisation de votre mot de passe')

@section('email-content')
    <p>Bonjour <strong>{{ $user->firstname }} {{ $user->lastname }}</strong>,</p>
    
    <p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte CHELSY Restaurant.</p>
    
    <div class="info-box">
        <h2 style="margin-top: 0; color: #1f2937;">Pour réinitialiser votre mot de passe :</h2>
        
        <p>Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :</p>
        <a href="{{ $resetUrl }}" class="button">Réinitialiser mon mot de passe</a>
    </div>
    
    <div class="warning">
        <strong>⚠️ Important :</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Ce lien est valable pendant <strong>60 minutes</strong> uniquement.</li>
            <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</li>
            <li>Pour des raisons de sécurité, ne partagez jamais ce lien avec personne.</li>
        </ul>
    </div>
    
    <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
    <div class="token-box" style="font-size: 11px;">
        {{ $resetUrl }}
    </div>
@endsection

