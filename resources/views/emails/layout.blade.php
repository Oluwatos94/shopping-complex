<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>@yield('title', 'jiidaa')</title>
    <style>
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
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
            padding: 0;
            text-align: center;
            font-size: 0;
            line-height: 0;
        }
        .email-header img {
            display: block;
            width: 100%;
            max-width: 580px;
            height: auto;
            border: 0;
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

        /* ── Campaign banner ── */
        .campaign-banner {
            margin: 0 0 28px;
        }
        .campaign-banner img {
            display: block;
            width: 100%;
            height: auto;
            border: 0;
            border-radius: 12px;
        }

        /* ── Rich text (admin-authored Markdown) ── */
        .rich-text {
            margin: 20px 0 8px;
        }
        .rich-text h1,
        .rich-text h2,
        .rich-text h3,
        .rich-text h4 {
            color: #0B1F3A;
            font-weight: 700;
            line-height: 1.35;
            margin: 26px 0 10px;
        }
        .rich-text h1 { font-size: 20px; }
        .rich-text h2 { font-size: 18px; }
        .rich-text h3 { font-size: 16px; }
        .rich-text h4 { font-size: 15px; }
        .rich-text p {
            color: #475467;
            font-size: 15px;
            line-height: 1.7;
            margin: 12px 0;
        }
        .rich-text ul,
        .rich-text ol {
            margin: 12px 0;
            padding-left: 22px;
        }
        .rich-text li {
            color: #475467;
            font-size: 15px;
            line-height: 1.7;
            margin: 6px 0;
        }
        .rich-text strong { color: #0B1F3A; font-weight: 700; }
        .rich-text a { color: #1EB85A; text-decoration: underline; }
        .rich-text blockquote {
            margin: 18px 0;
            padding: 2px 0 2px 16px;
            border-left: 4px solid #25D366;
            color: #667085;
        }
        .rich-text hr {
            border: none;
            border-top: 1px solid #e4e7ec;
            margin: 24px 0;
        }
        .rich-text img {
            display: block;
            width: 100%;
            height: auto;
            border: 0;
            border-radius: 12px;
            margin: 18px 0;
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
            .email-header { padding: 0; }
            .email-body { padding: 28px 24px; }
            .email-footer { padding: 20px 24px; }
            .action-button { padding: 14px 32px; font-size: 14px; }
        }

        @media (prefers-color-scheme: dark) {
            .email-header,
            .email-footer {
                background-color: #0B1F3A !important;
            }
            .action-button {
                background-color: #25D366 !important;
                color: #ffffff !important;
            }
        }

        [data-ogsc] .email-header,
        [data-ogsb] .email-header,
        [data-ogsc] .email-footer,
        [data-ogsb] .email-footer {
            background-color: #0B1F3A !important;
        }
        [data-ogsc] .email-footer p {
            color: rgba(255,255,255,0.6) !important;
        }
        [data-ogsc] .action-button,
        [data-ogsb] .action-button {
            background-color: #25D366 !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="email-container">

        <!-- Header -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0B1F3A">
            <tr>
                <td class="email-header" align="center" bgcolor="#0B1F3A" style="background-color: #0B1F3A; padding: 0;">
                    <img src="{{ asset('logo/emailLogo.png') }}" alt="jiidaa" width="580"
                         style="display: block; width: 100%; max-width: 580px; height: auto; border: 0;">
                </td>
            </tr>
        </table>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0B1F3A">
            <tr>
                <td class="email-footer" align="center" bgcolor="#0B1F3A" style="background-color: #0B1F3A;">
                    <p>&copy; {{ date('Y') }} jiidaa. All rights reserved.</p>
                    <p>This is an automated email — please do not reply to this message.</p>
                </td>
            </tr>
        </table>

    </div>
</div>
</body>
</html>
