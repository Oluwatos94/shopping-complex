import { useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';

interface PageProps {
    [key: string]: unknown;
    flash: { status?: string; success?: string; error?: string };
    auth: { user: { name: string; email: string } | null };
}

const RESEND_COOLDOWN = 60;

export default function VerifyEmail() {
    const { flash, auth } = usePage<PageProps>().props;
    const status = flash?.status;
    const { post, processing } = useForm({});
    const [countdown, setCountdown] = useState(RESEND_COOLDOWN);
    const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const startCountdown = () => {
        setCountdown(RESEND_COOLDOWN);
        if (timerRef.current) clearInterval(timerRef.current);
        timerRef.current = setInterval(() => {
            setCountdown(prev => {
                if (prev <= 1) {
                    clearInterval(timerRef.current!);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
    };

    useEffect(() => {
        startCountdown();
        return () => { if (timerRef.current) clearInterval(timerRef.current); };
    }, []);

    const handleBack = (to: string) => {
        router.post('/logout', {}, {
            onFinish: () => router.visit(to),
        });
    };

    const handleResend = (e: React.FormEvent) => {
        e.preventDefault();
        post('/email/verification-notification', {
            onSuccess: () => startCountdown(),
        });
    };

    const canResend = !processing && countdown === 0;

    return (
        <div
            className="min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
            style={{ background: 'linear-gradient(135deg, #272518 0%, #523026 100%)' }}
        >
            {/* Background image */}
            <img
                src="/images/Polo Park 2.jpg"
                alt=""
                className="absolute inset-0 w-full h-full object-cover opacity-20"
            />

            <div className="relative z-10 w-full max-w-md">
                <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">

                    {/* Branded header band */}
                    <div
                        className="px-8 pt-8 pb-6 text-center"
                        style={{ background: 'linear-gradient(135deg, #272518 0%, #523026 100%)' }}
                    >
                        {/* Logo + name */}
                        <div className="flex items-center justify-center gap-3 mb-5">
                            <img
                                src="/logo/jiidaa.jpeg"
                                alt="jiidaa"
                                className="h-10 w-auto"
                            />
                            <span className="text-white font-serif font-bold text-lg tracking-wide">
                                jiidaa
                            </span>
                        </div>

                        {/* Envelope icon on peach circle */}
                        <div
                            className="w-16 h-16 rounded-full flex items-center justify-center mx-auto"
                            style={{ background: 'rgba(212,159,137,0.2)', border: '2px solid #d49f89' }}
                        >
                            <svg className="w-7 h-7" fill="none" stroke="#d49f89" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="px-8 py-7 text-center">
                        <h1 className="text-xl font-serif font-bold text-primary-dark mb-2">
                            Verify your email address
                        </h1>

                        <p className="text-gray-500 text-sm leading-relaxed mb-1">
                            We sent a verification link to
                        </p>

                        {auth.user && (
                            <p className="font-semibold text-sm mb-4" style={{ color: '#86885e' }}>
                                {auth.user.email}
                            </p>
                        )}

                        <p className="text-gray-400 text-xs leading-relaxed mb-6">
                            Click the link in the email to activate your account.
                            The link expires in <span className="text-primary-dark font-medium">30 minutes</span>.
                        </p>

                        {/* Success feedback after resend */}
                        {status && (
                            <div
                                className="text-sm px-4 py-3 rounded-xl mb-5 flex items-center gap-2"
                                style={{ background: 'rgba(134,136,94,0.1)', border: '1px solid rgba(134,136,94,0.3)', color: '#86885e' }}
                            >
                                <svg className="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                </svg>
                                A new verification link has been sent.
                            </div>
                        )}

                        {/* Resend button */}
                        <form onSubmit={handleResend}>
                            <button
                                type="submit"
                                disabled={!canResend}
                                className="w-full py-3 text-white font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed text-sm mb-4"
                                style={{ background: 'linear-gradient(135deg, #272518 0%, #523026 100%)' }}
                            >
                                {processing ? (
                                    <span className="flex items-center justify-center gap-2">
                                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Sending...
                                    </span>
                                ) : countdown > 0 ? (
                                    `Resend in ${countdown}s`
                                ) : (
                                    'Resend verification email'
                                )}
                            </button>
                        </form>

                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex-1 h-px bg-gray-100" />
                            <span className="text-gray-300 text-xs">or</span>
                            <div className="flex-1 h-px bg-gray-100" />
                        </div>

                        <div className="flex flex-col items-center gap-2">
                            <button
                                type="button"
                                onClick={() => handleBack('/login')}
                                className="text-xs font-medium transition-colors"
                                style={{ color: '#86885e' }}
                            >
                                ← Back to sign in
                            </button>
                            <button
                                type="button"
                                onClick={() => handleBack('/register')}
                                className="text-xs text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                Wrong email? Register again
                            </button>
                        </div>
                    </div>
                </div>

                {/* Footer note */}
                <p className="text-center text-xs mt-4" style={{ color: 'rgba(202,207,202,0.6)' }}>
                    Didn't receive the email? Check your spam folder.
                </p>
            </div>
        </div>
    );
}
