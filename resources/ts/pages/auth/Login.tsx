import { usePage, useForm, router } from "@inertiajs/react";
import { useState } from "react";
import AuthBrandPanel from "@/components/Auth/AuthBrandPanel";

function Login() {
    const [showPassword, setShowPassword] = useState(false);
    const { flash } = usePage().props as any;
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post("/login");
    };

    return (
        <div className="grid min-h-screen font-display text-brand-ink lg:grid-cols-2">
            {/* Left — brand collage */}
            <AuthBrandPanel
                title={
                    <>
                        Connect with vendors
                        <br />
                        near you, <span className="text-brand-green">instantly.</span>
                    </>
                }
                subtitle="Discover local vendors, browse products, and get what you need — all in one place."
            />

            {/* Right — form panel */}
            <div className="flex min-w-0 items-center justify-center bg-white px-6 py-12 lg:px-20">
                <div className="w-full max-w-md">
                    {/* mobile back */}
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="mb-8 flex items-center gap-1.5 text-sm text-brand-muted transition hover:text-brand-ink lg:hidden"
                    >
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>

                    {/* mobile logo */}
                    <a href="/" className="mb-10 flex items-center lg:hidden">
                        <img src="/logo/Logo.svg" alt="Jiidaa" className="h-10 w-auto" />
                    </a>

                    <h2 className="font-serif text-[34px] font-semibold leading-tight text-brand-ink">Welcome back</h2>
                    <p className="mt-2 text-[15px] text-brand-muted">Sign in to your account to continue</p>

                    {/* Sign in / up toggle */}
                    <div className="mt-8 grid grid-cols-2 gap-1 rounded-full bg-brand-surface p-1.5 ring-1 ring-brand-line">
                        <button
                            type="button"
                            className="rounded-full bg-brand-ink py-3 text-[15px] font-semibold text-white shadow-sm"
                        >
                            Sign In
                        </button>
                        <button
                            type="button"
                            onClick={() => router.visit("/register")}
                            className="rounded-full py-3 text-[15px] font-semibold text-brand-muted transition hover:text-brand-ink"
                        >
                            Sign Up
                        </button>
                    </div>

                    {/* Error / Flash messages */}
                    {(errors as any).general && (
                        <div className="mt-6 rounded-xl border border-brand-danger/20 bg-brand-danger/5 px-4 py-3 text-sm text-brand-danger">
                            {(errors as any).general}
                        </div>
                    )}
                    {flash?.success && (
                        <div className="mt-6 rounded-xl border border-brand-green/20 bg-brand-green/5 px-4 py-3 text-sm text-brand-green-dark">
                            {flash.success}
                        </div>
                    )}

                    {/* Google */}
                    <a
                        href="/auth/google"
                        className="mt-6 flex w-full items-center justify-center gap-3 rounded-2xl border border-brand-line bg-white py-3.5 text-[15px] font-semibold text-brand-ink shadow-sm transition hover:bg-brand-surface"
                    >
                        <svg className="h-5 w-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.6 12.2c0-.7-.1-1.4-.2-2H12v3.9h6c-.3 1.4-1 2.6-2.2 3.4v2.8h3.6c2.1-1.9 3.2-4.8 3.2-8.1Z" />
                            <path fill="#34A853" d="M12 23c2.9 0 5.4-1 7.2-2.7l-3.6-2.8c-1 .7-2.3 1.1-3.6 1.1-2.8 0-5.1-1.9-6-4.4H2.3v2.9C4.1 20.8 7.8 23 12 23Z" />
                            <path fill="#FBBC05" d="M6 14.2c-.2-.7-.4-1.4-.4-2.2s.1-1.5.4-2.2V6.9H2.3C1.5 8.4 1 10.2 1 12s.5 3.6 1.3 5.1L6 14.2Z" />
                            <path fill="#EA4335" d="M12 5.4c1.6 0 3 .5 4.1 1.6l3.1-3.1C17.4 2.1 14.9 1 12 1 7.8 1 4.1 3.2 2.3 6.9L6 9.8c.9-2.5 3.2-4.4 6-4.4Z" />
                        </svg>
                        Continue with Google
                    </a>

                    {/* divider */}
                    <div className="my-6 flex items-center gap-4">
                        <span className="h-px flex-1 bg-brand-line" />
                        <span className="text-[13px] text-brand-muted">or sign in with email</span>
                        <span className="h-px flex-1 bg-brand-line" />
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit}>
                        {/* Email */}
                        <label className="block text-[14px] font-bold text-brand-ink">Email</label>
                        <div
                            className={`mt-2 flex items-center gap-3 rounded-2xl border bg-white px-4 py-3.5 transition focus-within:border-brand-green focus-within:ring-2 focus-within:ring-brand-green/20 ${
                                errors.email ? "border-brand-danger" : "border-brand-line"
                            }`}
                        >
                            <svg className="h-5 w-5 text-brand-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2" />
                                <path d="m3 7 9 6 9-6" />
                            </svg>
                            <input
                                type="email"
                                placeholder="Enter your email"
                                value={data.email}
                                onChange={(e) => setData("email", e.target.value)}
                                disabled={processing}
                                className="w-full bg-transparent text-[15px] text-brand-ink placeholder:text-brand-muted focus:outline-none disabled:opacity-50"
                            />
                        </div>
                        {errors.email && <p className="mt-1.5 text-xs text-brand-danger">{errors.email}</p>}

                        {/* Password */}
                        <label className="mt-5 block text-[14px] font-bold text-brand-ink">Password</label>
                        <div
                            className={`mt-2 flex items-center gap-3 rounded-2xl border bg-white px-4 py-3.5 transition focus-within:border-brand-green focus-within:ring-2 focus-within:ring-brand-green/20 ${
                                errors.password ? "border-brand-danger" : "border-brand-line"
                            }`}
                        >
                            <svg className="h-5 w-5 text-brand-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2" />
                                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                            </svg>
                            <input
                                type={showPassword ? "text" : "password"}
                                placeholder="Enter your password"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                                disabled={processing}
                                className="w-full bg-transparent text-[15px] text-brand-ink placeholder:text-brand-muted focus:outline-none disabled:opacity-50"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                className="text-brand-muted transition hover:text-brand-ink"
                                aria-label={showPassword ? "Hide password" : "Show password"}
                            >
                                {showPassword ? (
                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                        <path d="M1 1l22 22M6.61 6.61A18.5 18.5 0 0 0 2 12s3 8 10 8a9.12 9.12 0 0 0 5.39-1.61" />
                                    </svg>
                                ) : (
                                    <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                )}
                            </button>
                        </div>
                        {errors.password && <p className="mt-1.5 text-xs text-brand-danger">{errors.password}</p>}

                        <div className="mt-3 text-right">
                            <a href="/password/reset" className="text-[14px] font-semibold text-brand-green-dark transition hover:text-brand-green">
                                Forgot password?
                            </a>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full rounded-2xl bg-brand-ink py-4 text-[15px] font-semibold text-white shadow-sm transition hover:bg-brand-ink/90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? (
                                <span className="flex items-center justify-center gap-2">
                                    <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Signing in...
                                </span>
                            ) : (
                                "Sign In"
                            )}
                        </button>
                    </form>

                    <p className="mt-7 text-center text-[13px] leading-relaxed text-brand-muted">
                        By continuing, you agree to our{" "}
                        <a href="/terms" className="font-semibold text-brand-ink underline underline-offset-2">Terms of Service</a>
                        {" "}and{" "}
                        <a href="/privacy" className="font-semibold text-brand-ink underline underline-offset-2">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    );
}

export default Login;
