<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Your Email — FixMyWindow</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5;padding:40px 0;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;">

        {{-- Header --}}
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <span style="font-size:22px;font-weight:700;color:#1a1a2e;letter-spacing:-0.5px;">FixMyWindow</span>
          </td>
        </tr>

        {{-- Card --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:12px;padding:40px 40px 32px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">

            <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a1a2e;">Verify your email address</p>
            <p style="margin:0 0 24px;font-size:15px;color:#6b7280;line-height:1.6;">
              Hi {{ $name }}, thanks for signing up. Click the button below to confirm your email address and activate your account.
            </p>

            {{-- CTA button --}}
            <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr>
                <td style="background-color:#2563eb;border-radius:8px;">
                  <a href="{{ $verifyUrl }}"
                     style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                    Verify Email Address
                  </a>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 8px;font-size:13px;color:#9ca3af;line-height:1.6;">
              If the button doesn't work, copy and paste this link into your browser:
            </p>
            <p style="margin:0 0 24px;font-size:12px;color:#2563eb;word-break:break-all;">
              <a href="{{ $verifyUrl }}" style="color:#2563eb;text-decoration:none;">{{ $verifyUrl }}</a>
            </p>

            <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 20px;">

            <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6;">
              This link expires in <strong>24 hours</strong>. If you didn't create a FixMyWindow account, you can safely ignore this email.
            </p>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td align="center" style="padding-top:24px;">
            <p style="margin:0;font-size:12px;color:#9ca3af;">
              &copy; {{ date('Y') }} FixMyWindow. All rights reserved.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
