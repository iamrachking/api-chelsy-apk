<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('email-title', 'CHELSY Restaurant')</title>
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
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #2563eb;
        }
        .info-box {
            background: white;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
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
        .token-box {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
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
        <p>@yield('header-subtitle')</p>
    </div>
    
    <div class="content">
        @yield('email-content')
        
        <p style="margin-top: 30px;">Cordialement,<br>L'équipe CHELSY Restaurant</p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} CHELSY Restaurant. Tous droits réservés.</p>
    </div>
</body>
</html>

