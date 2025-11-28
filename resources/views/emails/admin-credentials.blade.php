<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identifiants Administrateur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(to right, #3b82f6, #2563eb);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .credentials {
            background: white;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-item {
            margin: 15px 0;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 5px;
        }
        .label {
            font-weight: bold;
            color: #1f2937;
        }
        .value {
            color: #3b82f6;
            font-size: 18px;
            font-family: monospace;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍽️ CHELSY Restaurant</h1>
        <p>Bienvenue dans l'équipe d'administration</p>
    </div>
    
    <div class="content">
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
        
        <p style="margin-top: 30px;">Cordialement,<br>L'équipe CHELSY Restaurant</p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} CHELSY Restaurant. Tous droits réservés.</p>
    </div>
</body>
</html>

