<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="{{ asset('assets/logo.png') }}" alt="Logo" style="max-width: 150px; height: auto;">
        </div>
        <h2 style="color: #333333; text-align: center; margin-bottom: 20px;">Reset Your Password</h2>
        <p style="color: #555555; line-height: 1.6;">Hello {{ $user->name }},</p>
        <p style="color: #555555; line-height: 1.6;">You are receiving this email because we received a password reset request for your account.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #007bff; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Reset Password</a>
        </div>
        <p style="color: #555555; line-height: 1.6;">If you did not request a password reset, no further action is required.</p>
        <p style="color: #555555; line-height: 1.6;">Regards,<br><strong>Your Application Team</strong></p>
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eeeeee; color: #999999; font-size: 12px;">
            <p>This email was sent to {{ $user->email }}. If you have any questions, please contact support.</p>
        </div>
    </div>
</body>
</html>

