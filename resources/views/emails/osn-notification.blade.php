<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title }}</title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
        font-family: Arial, Helvetica, sans-serif;
        color: #222222;
    "
>
    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        style="background-color: #f5f5f5; padding: 30px 15px;"
    >
        <tr>
            <td align="center">

                <table
                    width="100%"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="
                        max-width: 600px;
                        background-color: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                    "
                >

                    {{-- Header --}}
                    <tr>
                        <td
                            style="
                                background-color: #0D1B2A;
                                padding: 28px 32px;
                            "
                        >
                            <div
                                style="
                                    font-size: 28px;
                                    font-weight: 700;
                                    color: #C8A96A;
                                "
                            >
                                OSN
                            </div>

                            <div
                                style="
                                    margin-top: 6px;
                                    font-size: 13px;
                                    color: #ffffff;
                                "
                            >
                                On-demand Services Network
                            </div>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding: 36px 32px;">

                            @if($recipientName)
                                <p
                                    style="
                                        margin: 0 0 20px;
                                        font-size: 16px;
                                        line-height: 1.6;
                                    "
                                >
                                    Hello {{ $recipientName }},
                                </p>
                            @endif

                            <h1
                                style="
                                    margin: 0 0 18px;
                                    font-size: 24px;
                                    line-height: 1.3;
                                    color: #0D1B2A;
                                "
                            >
                                {{ $title }}
                            </h1>

                            <p
                                style="
                                    margin: 0;
                                    font-size: 16px;
                                    line-height: 1.7;
                                    color: #444444;
                                "
                            >
                                {{ $body }}
                            </p>

                            @if(!empty($data))
                                @if(isset($data['booking_id']))
                                    <div
                                        style="
                                            margin-top: 24px;
                                            padding: 16px;
                                            background-color: #f8f8f8;
                                            border-left: 4px solid #C8A96A;
                                            border-radius: 6px;
                                        "
                                    >
                                        <strong>Booking ID:</strong>
                                        #{{ $data['booking_id'] }}
                                    </div>
                                @endif
                            @endif

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td
                            style="
                                padding: 24px 32px;
                                background-color: #f8f8f8;
                                border-top: 1px solid #eeeeee;
                            "
                        >
                            <p
                                style="
                                    margin: 0;
                                    font-size: 13px;
                                    line-height: 1.6;
                                    color: #777777;
                                "
                            >
                                You are receiving this email because
                                of activity on your OSN account.
                            </p>

                            <p
                                style="
                                    margin: 12px 0 0;
                                    font-size: 13px;
                                    color: #777777;
                                "
                            >
                                © {{ date('Y') }} OSN.
                                All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>