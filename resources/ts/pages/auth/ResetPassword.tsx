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
                            Shopping Complex
                        </h1>
                    </div>
                </div>

                {/* Right Half - Sign up form (overlays the left half) */}
                <div className="absolute right-0 top-0 bottom-0 w-full md:w-1/2 bg-[#43442F]/60 backdrop-blur-md p-6 sm:p-8 lg:p-12 rounded-l-2xl flex flex-col justify-center gap-4 sm:gap-5 lg:gap-6 overflow-y-auto">
                    {/* Header - aligned to top center */}
                    <div className="text-center">
                        <h2 className="text-2xl lg:text-3xl font-serif font-semibold text-white mb-2">
                            Reset Password
                        </h2>
                        <p className="text-white/80 text-base">
                            Kindly set your new password
                        </p>
                    </div>

                    <form className="space-y-4">
                        {/* Password Input */}
                        <div>
                            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                                New Password
                            </label>
                            <div className="relative">
                                <input
                                    type={showPassword ? "text" : "password"}
                                    className="w-full px-4 py-3 pr-12 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowPassword(!showPassword)
                                    }
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

                        {/* Re-enter password */}
                        <div>
                            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                                Re-enter Password
                            </label>
                            <div className="relative">
                                <input
                                    type={
                                        showConfirmPassword
                                            ? "text"
                                            : "password"
                                    }
                                    className="w-full px-4 py-3 pr-12 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowConfirmPassword(
                                            !showConfirmPassword
                                        )
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

                        {/* Reset Password Button */}
                        <button
                            type="submit"
                            className="w-full py-3 bg-[#272518] text-white font-semibold rounded-3xl hover:bg-[#272518]/90 transition-all mt-4"
                        >
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}

export default Register;
