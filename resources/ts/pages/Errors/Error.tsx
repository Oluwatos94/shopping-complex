import { Head, Link } from '@inertiajs/react';

interface Props {
    status?: number;
}

interface ErrorCopy {
    title: string;
    body: string;
}

const COPY: Record<number, ErrorCopy> = {
    403: {
        title: "Hold up — you can't go there",
        body: "You don't have permission to view this page. Maybe try signing in, or head back to safer ground.",
    },
    404: {
        title: "Whoops — this page doesn't exist!",
        body: "Looks like you doodled outside the lines. The page you're after was moved, deleted, or never made it off the drawing board.",
    },
    500: {
        title: 'Yikes — something broke',
        body: "An unexpected error scribbled outside the lines on our end. Give it a moment and try again.",
    },
    503: {
        title: 'Back in a moment',
        body: "We're doing some quick touch-ups behind the scenes. Please check back shortly.",
    },
};

const handFont = "'Patrick Hand', system-ui, sans-serif";
const sketchFont = "'Caveat', cursive";

export default function Error({ status = 404 }: Props) {
    const code = String(status);
    const fallback: ErrorCopy = {
        title: 'Whoops — something went wrong',
        body: "The page you're after isn't here. Let's get you back on track.",
    };
    const { title, body } = COPY[status] ?? fallback;

    // Split the status code so the dizzy face sits in the middle as a "0".
    const first = code.slice(0, 1);
    const last = code.length > 1 ? code.slice(-1) : '';

    return (
        <>
            <Head title={`${status} — ${title}`}>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=Patrick+Hand&display=swap"
                    rel="stylesheet"
                />
                <style>{`
                    @keyframes jfloat { 0%,100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-10px) rotate(-3deg); } }
                    @keyframes jdash { to { stroke-dashoffset: -26; } }
                    @keyframes jwobble { 0%,100% { transform: rotate(-1.5deg); } 50% { transform: rotate(1.5deg); } }
                    @media (max-width: 640px) {
                        .jnav-links { display: none !important; }
                        .jbig { font-size: 150px !important; }
                        .jface { width: 116px !important; height: 116px !important; }
                        .jbtns { flex-direction: column !important; width: 100% !important; }
                        .jbtns a, .jbtns button { width: 100% !important; }
                        .jdoodle { display: none !important; }
                    }
                `}</style>
            </Head>

            <div
                style={{
                    position: 'relative',
                    minHeight: '100vh',
                    display: 'flex',
                    flexDirection: 'column',
                    fontFamily: handFont,
                    color: '#1C2B3A',
                    overflow: 'hidden',
                    background:
                        'repeating-linear-gradient(#FBFAF4 0px, #FBFAF4 45px, #C9DCEC 45px, #C9DCEC 46px)',
                }}
            >
                {/* red margin line */}
                <div style={{ position: 'absolute', top: 0, bottom: 0, left: 92, width: 2, background: '#F2B8B5', opacity: 0.8 }} />
                {/* punch holes */}
                <div style={{ position: 'absolute', left: 36, top: 160, width: 20, height: 20, borderRadius: '50%', background: '#EDEAE0', boxShadow: 'inset 0 1px 2px rgba(0,0,0,.18)' }} />
                <div style={{ position: 'absolute', left: 36, top: '50%', width: 20, height: 20, borderRadius: '50%', background: '#EDEAE0', boxShadow: 'inset 0 1px 2px rgba(0,0,0,.18)' }} />
                <div style={{ position: 'absolute', left: 36, bottom: 160, width: 20, height: 20, borderRadius: '50%', background: '#EDEAE0', boxShadow: 'inset 0 1px 2px rgba(0,0,0,.18)' }} />

                {/* NAV */}
                <header
                    style={{ position: 'relative', zIndex: 3, display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '20px 44px 20px 120px' }}
                >
                    <Link href="/">
                        <img src="/logo/darkLogo.svg" alt="jiidaa" style={{ height: 34, width: 'auto', display: 'block' }} />
                    </Link>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
                        <Link href="/products" className="jnav-links" style={{ fontFamily: handFont, fontSize: 19, color: '#5A6B7A', textDecoration: 'none' }}>
                            Browse
                        </Link>
                        <Link href="/vendors" className="jnav-links" style={{ fontFamily: handFont, fontSize: 19, color: '#5A6B7A', textDecoration: 'none' }}>
                            Vendors
                        </Link>
                        <Link
                            href="/login"
                            style={{ display: 'inline-flex', alignItems: 'center', fontFamily: handFont, fontSize: 18, color: '#11254a', textDecoration: 'none', border: '2px solid #11254a', borderRadius: '14px 10px 14px 10px', padding: '7px 18px', background: '#fff', boxShadow: '3px 3px 0 #11254a' }}
                        >
                            Sign In
                        </Link>
                    </div>
                </header>

                {/* CENTER */}
                <main
                    style={{ position: 'relative', zIndex: 3, flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center', padding: '30px 24px 70px' }}
                >
                    {/* doodles */}
                    <svg className="jdoodle" width="150" height="120" viewBox="0 0 150 120" fill="none" style={{ position: 'absolute', left: '9%', top: '18%' }}>
                        <path d="M10 100 C 40 40, 90 30, 138 16" stroke="#25D366" strokeWidth="2.5" strokeLinecap="round" strokeDasharray="2 9" style={{ animation: 'jdash 1.4s linear infinite' }} />
                        <path d="M138 16 l -16 -2 M138 16 l -6 14" stroke="#25D366" strokeWidth="2.5" strokeLinecap="round" />
                    </svg>
                    <svg className="jdoodle" width="80" height="80" viewBox="0 0 80 80" fill="none" style={{ position: 'absolute', right: '13%', top: '24%', animation: 'jwobble 4s ease-in-out infinite' }}>
                        <path d="M40 8 L48 30 L70 30 L52 44 L60 68 L40 53 L20 68 L28 44 L10 30 L32 30 Z" stroke="#F4B740" strokeWidth="2.5" strokeLinejoin="round" />
                    </svg>
                    <svg className="jdoodle" width="120" height="70" viewBox="0 0 120 70" fill="none" style={{ position: 'absolute', right: '9%', bottom: '20%' }}>
                        <path d="M14 40 q -8 -22 14 -22 q 6 -16 24 -10 q 12 -12 26 2 q 22 -4 20 18 q 12 4 4 20 L20 66 q -16 -6 -6 -26 Z" stroke="#9CB3C9" strokeWidth="2.4" strokeLinejoin="round" />
                    </svg>

                    {/* status code with dizzy face */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6 }}>
                        <span className="jbig" style={{ fontFamily: sketchFont, fontSize: 210, fontWeight: 700, lineHeight: 0.8, color: '#11254a' }}>
                            {first}
                        </span>

                        <div className="jface" style={{ position: 'relative', width: 172, height: 172, flex: 'none', animation: 'jfloat 4s ease-in-out infinite' }}>
                            <svg width="100%" height="100%" viewBox="0 0 172 172" fill="none">
                                <path d="M86 12 C 40 12, 14 50, 16 90 C 18 132, 52 160, 88 159 C 128 158, 158 124, 156 84 C 154 44, 124 13, 86 12 Z" stroke="#11254a" strokeWidth="6" strokeLinecap="round" fill="#fff" />
                                <path d="M84 16 C 44 18, 22 52, 24 90" stroke="#25D366" strokeWidth="6" strokeLinecap="round" fill="none" />
                                <path d="M55 70 l 20 16 M75 70 l -20 16" stroke="#11254a" strokeWidth="5.5" strokeLinecap="round" />
                                <path d="M97 70 l 20 16 M117 70 l -20 16" stroke="#11254a" strokeWidth="5.5" strokeLinecap="round" />
                                <path d="M64 122 q 12 -16 24 -2 q 12 14 24 -2" stroke="#11254a" strokeWidth="5.5" strokeLinecap="round" fill="none" />
                            </svg>
                        </div>

                        {last && (
                            <span className="jbig" style={{ fontFamily: sketchFont, fontSize: 210, fontWeight: 700, lineHeight: 0.8, color: '#11254a' }}>
                                {last}
                            </span>
                        )}
                    </div>

                    <h1 style={{ fontFamily: sketchFont, fontWeight: 700, fontSize: 52, margin: '14px 0 0', color: '#11254a' }}>{title}</h1>
                    <p style={{ fontFamily: handFont, fontSize: 21, lineHeight: 1.5, color: '#5A6B7A', maxWidth: 440, margin: '12px 0 0' }}>{body}</p>

                    <div className="jbtns" style={{ display: 'flex', gap: 16, marginTop: 32 }}>
                        <Link
                            href="/"
                            style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 9, fontFamily: handFont, fontSize: 20, color: '#fff', textDecoration: 'none', background: '#25D366', border: '2px solid #11254a', borderRadius: '16px 12px 16px 12px', padding: '13px 28px', boxShadow: '4px 4px 0 #11254a' }}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M3 11.5 12 4l9 7.5" />
                                <path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9" />
                            </svg>
                            Take me home
                        </Link>
                        <button
                            type="button"
                            onClick={() => window.history.back()}
                            style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 9, fontFamily: handFont, fontSize: 20, color: '#11254a', cursor: 'pointer', background: '#fff', border: '2px solid #11254a', borderRadius: '12px 16px 12px 16px', padding: '13px 28px', boxShadow: '4px 4px 0 #C9D6E2' }}
                        >
                            Go back
                        </button>
                    </div>

                    <div style={{ fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', fontSize: 14, color: '#9AAAB8', marginTop: 30, letterSpacing: '.04em' }}>
                        // error {status} — {status === 404 ? 'page not found' : title.toLowerCase()}
                    </div>
                </main>
            </div>
        </>
    );
}
