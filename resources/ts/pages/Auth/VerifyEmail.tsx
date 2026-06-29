import { useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import AuthBrandPanel from '@/components/Auth/AuthBrandPanel';

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
        <div className="grid min-h-screen font-display text-brand-ink lg:grid-cols-2">
            {/* Left — brand collage */}
            <AuthBrandPanel
                title={
                    <>
                        One quick step to
                        <br />
                        get <span className="text-brand-green">started.</span>
                    </>
                }
                subtitle="Verify your email to activate your account and start connecting with local vendors."
            />

            {/* Right — verification panel */}
            <div className="flex min-w-0 items-center justify-center bg-white px-6 py-12 lg:px-20">
                <div className="w-full max-w-md">
                    {/* mobile back */}
                    <button
                        type="button"
                        onClick={() => handleBack('/login')}
                        className="mb-8 flex items-center gap-1.5 text-sm text-brand-muted transition hover:text-brand-ink lg:hidden"
                    >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to sign in
                    </button>

                    {/* mobile logo */}
                    <a href="/" className="mb-10 flex items-center lg:hidden">
                        <img src="/logo/Logo.svg" alt="Jiidaa" className="h-10 w-auto" />
                    </a>

                    {/* Envelope icon */}
                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-green/10 ring-1 ring-brand-green/20">
                        <svg className="h-7 w-7 text-brand-green-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                    </div>

                    <h1 className="mt-6 font-serif text-[34px] font-semibold leading-tight text-brand-ink">
                        Verify your email address
                    </h1>
                    <p className="mt-2 text-[15px] text-brand-muted">
                        We sent a verification link to
                    </p>

                    {auth.user && (
                        <p className="mt-1 text-[15px] font-semibold text-brand-green-dark">
                            {auth.user.email}
                        </p>
                    )}

                    <p className="mt-4 text-[14px] leading-relaxed text-brand-muted">
                        Click the link in the email to activate your account. The link expires in{' '}
                        <span className="font-semibold text-brand-ink">30 minutes</span>.
                    </p>

                    {/* Success feedback after resend */}
                    {status && (
                        <div className="mt-6 flex items-center gap-2 rounded-2xl border border-brand-green/20 bg-brand-green/5 px-4 py-3 text-sm text-brand-green-dark">
                            <svg className="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
                            className="mt-6 w-full rounded-2xl bg-brand-ink py-4 text-[15px] font-semibold text-white shadow-sm transition hover:bg-brand-ink/90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? (
                                <span className="flex items-center justify-center gap-2">
                                    <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24">
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

                    {/* divider */}
                    <div className="my-6 flex items-center gap-4">
                        <span className="h-px flex-1 bg-brand-line" />
                        <span className="text-[13px] text-brand-muted">or</span>
                        <span className="h-px flex-1 bg-brand-line" />
                    </div>

                    <div className="flex flex-col items-center gap-3">
                        <button
                            type="button"
                            onClick={() => handleBack('/login')}
                            className="text-[14px] font-semibold text-brand-green-dark transition hover:text-brand-green"
                        >
                            ← Back to sign in
                        </button>
                        <button
                            type="button"
                            onClick={() => handleBack('/register')}
                            className="text-[13px] text-brand-muted transition hover:text-brand-ink"
                        >
                            Wrong email? Register again
                        </button>
                    </div>

                    <p className="mt-8 text-center text-[13px] text-brand-muted">
                        Didn't receive the email? Check your spam folder.
                    </p>
                </div>
            </div>
        </div>
    );
}
