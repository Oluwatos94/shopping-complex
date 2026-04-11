<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Shopping Complex')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #272518;
            background-color: #f2f0eb;
        }
        .wrapper {
            background-color: #f2f0eb;
            padding: 40px 16px;
        }
        .email-container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(39,37,24,0.10);
        }

        /* ── Header ── */
        .email-header {
            background: linear-gradient(135deg, #272518 0%, #523026 100%);
            padding: 36px 40px 32px;
            text-align: center;
        }
        .email-header .logo-row {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        .email-header .brand-name {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* ── Body ── */
        .email-body {
            padding: 40px 40px 32px;
        }
        .email-body h2 {
            color: #272518;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .email-body p {
            color: #555;
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
            background: linear-gradient(135deg, #272518 0%, #523026 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.2px;
        }

        /* ── Info box ── */
        .info-box {
            background-color: #faf9f7;
            border-left: 4px solid #86885e;
            padding: 14px 18px;
            margin: 24px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid #ece9e3;
            margin: 28px 0;
        }

        /* ── Link fallback ── */
        .link-fallback p {
            font-size: 13px;
            color: #888;
            margin-bottom: 8px;
        }
        .link-text {
            word-break: break-all;
            color: #86885e;
            font-size: 12px;
        }

        /* ── Footer ── */
        .email-footer {
            background-color: #272518;
            padding: 24px 40px;
            text-align: center;
        }
        .email-footer p {
            color: rgba(202,207,202,0.7);
            font-size: 12px;
            margin: 4px 0;
            line-height: 1.6;
        }
        .email-footer .footer-brand {
            color: #d49f89;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
        }

        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 12px; }
            .email-header { padding: 28px 24px; }
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
            <div class="logo-row">
                <!-- Inline SVG logo (white paths work on dark background) -->
                <svg width="32" height="48" viewBox="0 0 77 116" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16.8679 33.9963C15.3485 31.3682 14.73 28.5038 15.067 25.6568C15.404 22.8099 16.6862 20.0683 18.7999 17.6749C20.9137 15.2815 23.7938 13.3102 27.1852 11.9355C30.5766 10.5608 34.3745 9.82529 38.2425 9.79401C42.1106 9.76273 45.9294 10.4367 49.3607 11.7562C52.792 13.0757 55.7298 15 57.914 17.3586C60.0982 19.7173 61.4613 22.4375 61.8825 25.2783C62.3038 28.1191 61.7702 30.9928 60.3291 33.6449L54.6985 31.9749C55.7679 30.0069 56.1638 27.8745 55.8512 25.7664C55.5386 23.6584 54.5271 21.6398 52.9063 19.8896C51.2855 18.1393 49.1055 16.7114 46.5593 15.7322C44.0131 14.7531 41.1793 14.253 38.3089 14.2762C35.4386 14.2994 32.6203 14.8452 30.1037 15.8653C27.5871 16.8854 25.4499 18.3482 23.8814 20.1243C22.3128 21.9003 21.3614 23.9347 21.1113 26.0473C20.8612 28.1599 21.3202 30.2855 22.4477 32.2357L16.8679 33.9963Z" fill="#D49F89"/>
                    <rect x="9.98486" y="29.1865" width="57.0299" height="9.98504" rx="3.8404" fill="white"/>
                    <circle cx="58.7576" cy="33.2193" r="2.30424" fill="#272518"/>
                    <rect x="9.98486" y="57.6055" width="57.0299" height="9.98504" rx="3.8404" fill="white"/>
                    <rect x="9.98486" y="86.0244" width="57.0299" height="9.98504" rx="3.8404" fill="white"/>
                    <rect x="20.1619" y="29.1865" width="38.404" height="9.98504" rx="3.8404" transform="rotate(90 20.1619 29.1865)" fill="white"/>
                    <circle cx="19.0098" cy="33.2193" r="2.30424" fill="#272518"/>
                    <rect x="67.0146" y="57.6055" width="38.404" height="9.98504" rx="3.8404" transform="rotate(90 67.0146 57.6055)" fill="white"/>
                    <rect x="14.7856" y="97.7383" width="8.44888" height="8.44888" rx="4.22444" fill="#D49F89"/>
                    <circle cx="19.01" cy="101.963" r="1.9202" fill="#272518"/>
                    <rect x="56.4539" y="97.7383" width="8.44888" height="8.44888" rx="4.22444" fill="#D49F89"/>
                    <circle cx="60.678" cy="101.962" r="1.9202" fill="#272518"/>
                </svg>
                <span class="brand-name">Shopping Complex</span>
            </div>
        </div>

        <!-- Body -->
        <div class="email-body">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-brand">Shopping Complex</p>
            <p>&copy; {{ date('Y') }} Shopping Complex. All rights reserved.</p>
            <p>This is an automated email — please do not reply to this message.</p>
        </div>

    </div>
</div>
</body>
</html>
