import { Link } from "@inertiajs/react";
import { useState } from "react";

function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    return (
        <div className="h-screen w-full fixed inset-0 flex items-center justify-center p-4 sm:p-6 md:p-8 overflow-hidden">
            {/* Background Image */}
            <img
                src="/images/Polo Park 2.jpg"
                alt="register page background"
                className="fixed inset-0 w-full h-full object-cover z-0"
            />
            {/* Translucent overlay */}
            <div className="fixed inset-0 bg-black/40 z-0"></div>

            {/* Modal Container */}
            <div className="relative z-10 w-full max-w-7xl h-[85vh] rounded-2xl overflow-hidden border border-white/50">
                {/* Left Half - Full width background, hidden on mobile */}
                <div className="hidden md:flex absolute inset-0 bg-white/10 backdrop-blur-md p-8 lg:p-12 flex-col justify-end pb-16">
                    <div className="mb-10">
                        <h1 className="text-3xl lg:text-5xl font-serif font-medium text-white mb-4">
                            Let's Get Started
                        </h1>
                        <p className="text-white/90 text-lg lg:text-2xl leading-relaxed max-w-md">
                            Join us and enjoy seamless shopping, exclusive
                            offers, and easy access to your favorite stores.
                        </p>
                    </div>
                </div>

                {/* Right Half - Sign up form (overlays the left half) */}
                <div className="absolute right-0 top-0 bottom-0 w-full md:w-1/2 bg-[#43442F]/60 backdrop-blur-md p-6 sm:p-8 lg:p-12 rounded-l-2xl flex flex-col justify-center gap-4 sm:gap-5 lg:gap-6 overflow-y-auto">
                    {/* Header - aligned to top center */}
                    <div className="text-center">
                        <h2 className="text-2xl lg:text-3xl font-serif font-medium text-white mb-2">
                            Let's get started
                        </h2>
                        <p className="text-white/80 text-base">
                            Register Your Account
                        </p>
                    </div>

                    <form className="space-y-4">
                        {/* Email Input */}
                        <div>
                            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                                Email
                            </label>
                            <input
                                type="email"
                                className="w-full px-4 py-3 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                            />
                        </div>

                        {/* Password Input */}
                        <div>
                            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                                Password
                            </label>
                            <div className="relative">
                                <input
                                    type={showPassword ? "text" : "password"}
                                    className="w-full px-4 py-3 pr-12 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors"
                                    aria-label={
                                        showPassword
                                            ? "Hide password"
                                            : "Show password"
                                    }
                                >
                                {showPassword ? (
                                    <svg
                                        className="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                ) : (
                                    <svg
                                        className="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                )}
                                </button>
                            </div>
                        </div>

                        {/* Confirm Password Input */}
                        <div>
                            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                                Confirm Password
                            </label>
                            <div className="relative">
                                <input
                                    type={showConfirmPassword ? "text" : "password"}
                                    className="w-full px-4 py-3 pr-12 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowConfirmPassword(!showConfirmPassword)
                                    }
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors"
                                    aria-label={
                                        showConfirmPassword
                                            ? "Hide password"
                                            : "Show password"
                                    }
                                >
                                {showConfirmPassword ? (
                                    <svg
                                        className="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                ) : (
                                    <svg
                                        className="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                )}
                                </button>
                            </div>
                        </div>

                        {/* Create Account Button */}
                        <button
                            type="submit"
                            className="w-full py-3 bg-[#272518] text-white font-semibold rounded-3xl hover:bg-[#272518]/90 transition-all mt-4"
                        >
                            Create account
                        </button>

                        {/* Sign In Link */}
                        <p className="text-white text-center mt-2 lg:mt-1">
                        Already have an account?{" "}
                        <Link
                            href="/login"
                            className="font-semibold text-[#272518] hover:text-white/80 transition-colors"
                        >
                            Sign in
                        </Link>
                        </p>
                    </form>

                    {/* Social Sign In */}
                    <div>
                        <p className="text-white text-center mb-4">
                            Or Sign Up With
                        </p>
                        <div className="flex items-center justify-center gap-4">
                            {/* Google */}
                            <button
                                type="button"
                                className="w-12 h-12 rounded-full border border-[#272518] bg-transparent flex items-center justify-center hover:bg-white/10 transition-all"
                                aria-label="Sign up with Google"
                            >
                                <svg className="w-6 h-6" viewBox="0 0 24 24">
                                    <path
                                        fill="#4285F4"
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                    />
                                    <path
                                        fill="#34A853"
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                    />
                                    <path
                                        fill="#FBBC05"
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                    />
                                    <path
                                        fill="#EA4335"
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                    />
                                </svg>
                            </button>

                            {/* Facebook */}
                            <button
                                type="button"
                                className="w-12 h-12 rounded-full border border-[#272518] bg-transparent flex items-center justify-center hover:bg-white/10 transition-all"
                                aria-label="Sign up with Facebook"
                            >
                                <svg className="w-5 h-5" viewBox="0 0 10 20">
                                    <path
                                        fill="#1877F2"
                                        d="M6.821 20v-9.196h3.085l.462-3.583H6.821V4.932c0-1.037.288-1.744 1.776-1.744h1.897V.134A25.478 25.478 0 0 0 7.726 0C4.984 0 3.127 1.656 3.127 4.699v2.522H0v3.583h3.127V20h3.694z"
                                    />
                                </svg>
                            </button>

                            {/* Apple */}
                            <button
                                type="button"
                                className="w-12 h-12 rounded-full border border-[#272518] bg-transparent flex items-center justify-center hover:bg-white/10 transition-all"
                                aria-label="Sign up with Apple"
                            >
                                <svg className="w-6 h-6" viewBox="0 0 24 24">
                                    <path
                                        fill="#000000"
                                        d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Register;