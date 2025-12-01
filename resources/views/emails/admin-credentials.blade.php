@extends('emails.layout')

@section('email-title', 'Identifiants Administrateur')

@section('header-subtitle', 'Bienvenue dans l'équipe d'administration')

@section('email-content')
    <p>Bonjour <strong>{{ $user->firstname }} {{ $user->lastname }}</strong>,</p>
    
    <p>Un compte administrateur a été créé pour vous sur la plateforme CHELSY Restaurant.</p>
    
    <div class="credentials">
        <h2 style="margin-top: 0; color: #1f2937;">Vos identifiants de connexion :</h2>
        
        <div class="credential-item">
            <div class="label">Email :</div>
            <div class="value">{{ $user->email }}</div>
        </div>
        
        <div class="credential-item">
            <div class="label">Mot de passe temporaire :</div>
            <div class="value">{{ $password }}</div>
        </div>
    </div>
    
    <div class="warning">
        <strong>⚠️ Important :</strong> Pour des raisons de sécurité, nous vous recommandons fortement de changer ce mot de passe lors de votre première connexion.
    </div>
    
    <p>Vous pouvez vous connecter à l'adresse suivante :</p>
    <a href="{{ url('/admin/login') }}" class="button">Accéder au panneau d'administration</a>
@endsection

