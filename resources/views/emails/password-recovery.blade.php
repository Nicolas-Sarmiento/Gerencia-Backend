<!DOCTYPE html>
<html>
<head>
    <title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Recuperación de Contraseña</h2>
    <p>Has solicitado restablecer tu contraseña. Utiliza el siguiente código de 6 dígitos para completar el proceso:</p>
    
    <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; border-left: 4px solid #A8CF45; text-align: center; margin: 20px 0;">
        <h1 style="letter-spacing: 5px; margin: 0; color: #2F569D;">{{ $code }}</h1>
    </div>

    <p>Este código expirará en 15 minutos.</p>
    
    <p>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
    
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 0.8em; color: #888;">Este es un correo automático, por favor no respondas.</p>
</body>
</html>
