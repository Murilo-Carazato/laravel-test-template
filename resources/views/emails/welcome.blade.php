<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bem-vindo ao {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #f8f9fa;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bem-vindo ao {{ config('app.name') }}!</h1>
        </div>
        
        <div class="content">
            <p>Olá {{ $name }},</p>
            
            <p>Estamos muito felizes em ter você conosco! Sua conta foi criada com sucesso.</p>
            
            <p>Com o {{ config('app.name') }}, você pode:</p>
            <ul>
                <li>Acessar todos os recursos do aplicativo</li>
                <li>Gerenciar seu perfil e preferências</li>
                <li>Personalizar sua experiência</li>
            </ul>
            
            <p>Se você tiver alguma dúvida, não hesite em nos contatar.</p>
            
            <a href="{{ config('app.url') }}" class="button">Acessar sua conta</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
            <p>Este é um e-mail automatizado, por favor não responda.</p>
        </div>
    </div>
</body>
</html>
