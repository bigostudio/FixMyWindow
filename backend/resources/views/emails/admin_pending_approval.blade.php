<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { background: #1a1a2e; color: #fff; padding: 20px 24px; border-radius: 6px 6px 0 0; }
        .body { background: #f9f9f9; padding: 24px; border: 1px solid #e0e0e0; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 15px; font-weight: bold; }
        .footer { font-size: 12px; color: #aaa; margin-top: 24px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0;">New Registration — Approval Required</h2>
    </div>
    <div class="body">
        <p>A new team member has registered on FixMyWindow and is awaiting approval.</p>

        <div class="field">
            <div class="label">Name</div>
            <div class="value">{{ $name }}</div>
        </div>
        <div class="field">
            <div class="label">Email</div>
            <div class="value">{{ $email }}</div>
        </div>
        <div class="field">
            <div class="label">Phone</div>
            <div class="value">{{ $phone }}</div>
        </div>
        <div class="field">
            <div class="label">Role Requested</div>
            <div class="value">{{ ucwords(str_replace('_', ' ', $role)) }}</div>
        </div>

        <p style="margin-top:24px;">Please log in to the admin panel to review and approve this account.</p>
    </div>
    <div class="footer">
        <p>FixMyWindow — Operations Team</p>
    </div>
</div>
</body>
</html>
