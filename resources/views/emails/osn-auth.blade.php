<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>OSN Verification</title>
</head>

<body style="
    margin:0;
    padding:0;
    background:#f5f5f5;
    font-family:Arial,Helvetica,sans-serif;
    color:#222;
">

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="padding:30px 15px;background:#f5f5f5;">
    <tr>
        <td align="center">

            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="
                       max-width:600px;
                       background:#ffffff;
                       border-radius:12px;
                       overflow:hidden;
                   ">

                <tr>
                    <td style="
                        background:#0D1B2A;
                        padding:28px 32px;
                    ">
                        <div style="
                            font-size:28px;
                            font-weight:700;
                            color:#C8A96A;
                        ">
                            OSN
                        </div>

                        <div style="
                            margin-top:6px;
                            font-size:13px;
                            color:#ffffff;
                        ">
                            On-demand Services Network
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:36px 32px;">

                        <p style="
                            margin:0 0 20px;
                            font-size:16px;
                            line-height:1.6;
                        ">
                            Hello {{ $recipientName }},
                        </p>

                        <h1 style="
                            margin:0 0 16px;
                            font-size:24px;
                            color:#0D1B2A;
                        ">
                            {{ match ($purpose) {
                                'registration' => 'Verify Your Account',
                                'forgot_password' => 'Reset Your Password',
                                'resend_otp' => 'Your New Verification Code',
                                'change_email' => 'Verify Your New Email',
                                default => 'Verification Code',
                            } }}
                        </h1>

                        <p style="
                            margin:0 0 24px;
                            font-size:16px;
                            line-height:1.7;
                            color:#444;
                        ">
                            {{ match ($purpose) {
                                'registration'
                                    => 'Use the verification code below to verify your OSN account.',
                                'forgot_password'
                                    => 'Use the verification code below to reset your OSN password.',
                                'resend_otp'
                                    => 'Use the new verification code below to continue.',
                                'change_email'
                                    => 'Use the verification code below to verify your new email address.',
                                default
                                    => 'Use the verification code below to continue.',
                            } }}
                        </p>

                        <div style="
                            text-align:center;
                            margin:30px 0;
                            padding:20px;
                            background:#f8f8f8;
                            border-radius:10px;
                            border:1px solid #eeeeee;
                        ">
                            <div style="
                                font-size:13px;
                                color:#777;
                                margin-bottom:8px;
                            ">
                                VERIFICATION CODE
                            </div>

                            <div style="
                                font-size:32px;
                                font-weight:700;
                                letter-spacing:8px;
                                color:#0D1B2A;
                            ">
                                {{ $otp }}
                            </div>
                        </div>

                        <p style="
                            margin:0;
                            font-size:14px;
                            line-height:1.6;
                            color:#777;
                        ">
                            This code expires in 10 minutes.
                            If you did not request this code,
                            you can safely ignore this email.
                        </p>

                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:24px 32px;
                        background:#f8f8f8;
                        border-top:1px solid #eeeeee;
                    ">
                        <p style="
                            margin:0;
                            font-size:13px;
                            color:#777;
                        ">
                            © {{ date('Y') }} OSN. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>