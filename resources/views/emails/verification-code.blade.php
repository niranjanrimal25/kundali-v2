<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#f4f1ea;font-family:Georgia,'Times New Roman',serif;color:#2b2520;">
    <div style="max-width:520px;margin:0 auto;background:#fffdf8;border:1px solid #e3dccd;border-radius:6px;padding:32px;">

        <h1 style="margin:0 0 4px;font-size:22px;color:#4a2c5a;">Verify your email</h1>
        <p style="margin:0 0 24px;font-size:14px;color:#7b3f61;">{{ config('app.name') }}</p>

        <p style="font-size:15px;line-height:1.7;">
            Hello {{ $user->name }}, use the code below to finish creating your account.
        </p>

        <div style="margin:24px 0;padding:18px;text-align:center;background:#f4f1ea;border:1px solid #e3dccd;border-radius:6px;">
            <div style="font-family:'Courier New',monospace;font-size:34px;letter-spacing:10px;font-weight:bold;color:#4a2c5a;">
                {{ $code }}
            </div>
        </div>

        <p style="font-size:14px;line-height:1.7;color:#5a5048;">
            This code expires in {{ $minutes }} minutes and can only be used once.
        </p>

        <p style="font-size:13px;line-height:1.7;color:#8a7f74;border-top:1px solid #ede5d6;padding-top:16px;margin-top:24px;">
            If you did not try to create an account, you can ignore this email —
            nobody can access the account without this code.
        </p>
    </div>
</body>
</html>
