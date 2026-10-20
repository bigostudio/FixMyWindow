<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { background: #1a1a2e; color: #fff; padding: 20px 24px; border-radius: 6px 6px 0 0; }
        .body { background: #f9f9f9; padding: 24px; border: 1px solid #e0e0e0; }
        .badge { display: inline-block; background: #e8f4fd; color: #1a6fa8; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .footer { font-size: 12px; color: #aaa; margin-top: 24px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;">Registration Received</h2>
    </div>
    <div class="body">
        <p>Hi {{ $name }},</p>
        <p>Your registration on <strong>FixMyWindow</strong> has been received successfully.</p>
        <p>
            Role requested: <span class="badge">{{ ucwords(str_replace('_', ' ', $role)) }}</span>
        </p>
        <p>Your account is currently <strong>pending approval</strong> by an administrator. You will be notified once your account is activated and you can log in.</p>
        <p>If you have any questions, please contact the FixMyWindow operations team.</p>
    </div>
    <div class="footer">
        <p>FixMyWindow — Operations Team</p>
    </div>
</div>
</body>
</html>
