import { useForm, router } from "@inertiajs/react";
import { useState, useMemo } from "react";
import PasswordRequirement from "@/components/Forms/PasswordRequirement";
import AuthBrandPanel from "@/components/Auth/AuthBrandPanel";

function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [passwordTouched, setPasswordTouched] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
        password_confirmation: "",
    });

    const passwordRules = useMemo(() => ({
        minLength: data.password.length >= 8,
        hasUpper:  /[A-Z]/.test(data.password),
        hasNumber: /\d/.test(data.password),
        hasSymbol: /[^a-zA-Z0-9]/.test(data.password),
    }), [data.password]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post("/register");
    };

    const EyeToggle: React.FC<{ shown: boolean; onClick: () => void }> = ({ shown, onClick }) => (
        <button
            type="button"
            onClick={onClick}
            className="text-brand-muted transition hover:text-brand-ink"
            aria-label={shown ? "Hide password" : "Show password"}
        >
            {shown ? (
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
    );

    return (
        <div className="grid min-h-screen font-display text-brand-ink lg:grid-cols-2">
            {/* Left — brand collage */}
            <AuthBrandPanel
                title={
                    <>
                        Join thousands finding
                        <br />
                        local vendors, <span className="text-brand-green">fast.</span>
                    </>
                }
                subtitle="Create a free account and start discovering vendors near you in seconds."
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
                    <a href="/" className="mb-8 flex items-center lg:hidden">
                        <img src="/logo/Logo.svg" alt="Jiidaa" className="h-10 w-auto" />
                    </a>

                    <h2 className="font-serif text-[34px] font-semibold leading-tight text-brand-ink">Create an account</h2>
                    <p className="mt-2 text-[15px] text-brand-muted">Sign up to start discovering local vendors near you</p>

                    {/* Sign in / up toggle */}
                    <div className="mt-8 grid grid-cols-2 gap-1 rounded-full bg-brand-surface p-1.5 ring-1 ring-brand-line">
                        <button
                            type="button"
                            onClick={() => router.visit("/login")}
                            className="rounded-full py-3 text-[15px] font-semibold text-brand-muted transition hover:text-brand-ink"
                        >
                            Sign In
                        </button>
                        <button
                            type="button"
                            className="rounded-full bg-brand-ink py-3 text-[15px] font-semibold text-white shadow-sm"
                        >
                            Sign Up
                        </button>
                    </div>

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
                        <span className="text-[13px] text-brand-muted">or sign up with email</span>
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
                                placeholder="Create a password"
                                value={data.password}
                                onChange={(e) => setData("password", e.target.value)}
                                onFocus={() => setPasswordTouched(true)}
                                disabled={processing}
                                className="w-full bg-transparent text-[15px] text-brand-ink placeholder:text-brand-muted focus:outline-none disabled:opacity-50"
                            />
                            <EyeToggle shown={showPassword} onClick={() => setShowPassword(!showPassword)} />
                        </div>
                        {errors.password && <p className="mt-1.5 text-xs text-brand-danger">{errors.password}</p>}
                        {passwordTouched && !errors.password && (
                            <ul className="mt-3 space-y-1 pl-1">
                                <PasswordRequirement met={passwordRules.minLength} label="At least 8 characters" />
                                <PasswordRequirement met={passwordRules.hasUpper} label="At least one uppercase letter" />
                                <PasswordRequirement met={passwordRules.hasNumber} label="At least one number" />
                                <PasswordRequirement met={passwordRules.hasSymbol} label="At least one special character (!@#$...)" />
                            </ul>
                        )}

                        {/* Confirm Password */}
                        <label className="mt-5 block text-[14px] font-bold text-brand-ink">Confirm Password</label>
                        <div
                            className={`mt-2 flex items-center gap-3 rounded-2xl border bg-white px-4 py-3.5 transition focus-within:border-brand-green focus-within:ring-2 focus-within:ring-brand-green/20 ${
                                errors.password_confirmation ? "border-brand-danger" : "border-brand-line"
                            }`}
                        >
                            <svg className="h-5 w-5 text-brand-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2" />
                                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                            </svg>
                            <input
                                type={showConfirmPassword ? "text" : "password"}
                                placeholder="Confirm your password"
                                value={data.password_confirmation}
                                onChange={(e) => setData("password_confirmation", e.target.value)}
                                disabled={processing}
                                className="w-full bg-transparent text-[15px] text-brand-ink placeholder:text-brand-muted focus:outline-none disabled:opacity-50"
                            />
                            <EyeToggle shown={showConfirmPassword} onClick={() => setShowConfirmPassword(!showConfirmPassword)} />
                        </div>
                        {errors.password_confirmation && <p className="mt-1.5 text-xs text-brand-danger">{errors.password_confirmation}</p>}

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
                                    Creating account...
                                </span>
                            ) : (
                                "Create Account"
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

export default Register;
