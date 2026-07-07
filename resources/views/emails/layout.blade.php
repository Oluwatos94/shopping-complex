<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'jiidaa')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #0B1F3A;
            background-color: #f8fafc;
        }
        .wrapper {
            background-color: #f8fafc;
            padding: 40px 16px;
        }
        .email-container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e4e7ec;
            box-shadow: 0 4px 24px rgba(11,31,58,0.08);
        }

        /* ── Header ── */
        .email-header {
            background-color: #0B1F3A;
            padding: 28px 40px;
            text-align: center;
        }
        .email-header img {
            display: block;
            margin: 0 auto;
            width: 150px;
            max-width: 60%;
            height: auto;
        }

        /* ── Body ── */
        .email-body {
            padding: 40px 40px 32px;
        }
        .email-body h2 {
            color: #0B1F3A;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .email-body p {
            color: #667085;
            font-size: 15px;
            margin: 12px 0;
            line-height: 1.7;
        }

        /* ── Button ── */
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .action-button {
            display: inline-block;
            padding: 15px 44px;
            background-color: #25D366;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.2px;
        }

        /* ── Info box ── */
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #25D366;
            padding: 14px 18px;
            margin: 24px 0;
            border-radius: 8px;
        }
        .info-box p {
            margin: 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.6;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid #e4e7ec;
            margin: 28px 0;
        }

        /* ── Link fallback ── */
        .link-fallback p {
            font-size: 13px;
            color: #98a2b3;
            margin-bottom: 8px;
        }
        .link-text {
            word-break: break-all;
            color: #1EB85A;
            font-size: 12px;
        }

        /* ── Footer ── */
        .email-footer {
            background-color: #0B1F3A;
            padding: 24px 40px;
            text-align: center;
        }
        .email-footer p {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 4px 0;
            line-height: 1.6;
        }

        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 12px; }
            .email-header { padding: 24px 24px 20px; }
            .email-body { padding: 28px 24px; }
            .email-footer { padding: 20px 24px; }
            .action-button { padding: 14px 32px; font-size: 14px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="email-container">

        <!-- Header -->
        <div class="email-header">
            <img src="{{ asset('logo/whiteLogo.png') }}" alt="jiidaa" width="150">
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} jiidaa. All rights reserved.</p>
            <p>This is an automated email — please do not reply to this message.</p>
        </div>

    </div>
</div>
</body>
</html>
