import { Link } from "@inertiajs/react";

function ForgotPassword() {
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
                        <h1 className="text-3xl lg:text-5xl font-semibold text-white mb-4">
                            Shopping Complex
                        </h1>
                    </div>
                </div>

                {/* Right Half - Sign up form (overlays the left half) */}
                <div className="absolute right-0 top-0 bottom-0 w-full md:w-1/2 bg-[#43442F]/60 backdrop-blur-md p-6 sm:p-8 lg:p-12 rounded-l-2xl flex flex-col">
                    {/* Header - aligned to top center */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl lg:text-3xl font-semibold text-white mb-2">
                            Forget Password?
                        </h2>
                        <p className="text-white/80 text-base">
                            Enter your email and we'll send you a reset link.
                        </p>
                    </div>

                    <form className="space-y-5 flex-1 flex flex-col justify-center gap-6 lg:gap-8">
                        {/* Email Input */}
                        <div>
                            <input
                                type="email"
                                placeholder="example@gmail.com"
                                className="w-full px-4 py-3 bg-transparent border border-white rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all"
                            />
                        </div>

                        {/* Send Email Button */}
                        <button
                            type="submit"
                            className="w-full py-3 bg-[#272518] text-white font-semibold rounded-3xl hover:bg-[#272518]/90 transition-all mt-6"
                        >
                            Send Email
                        </button>

                        {/* Sign In Link */}
                        <p className="text-white text-center mt-1">
                            <Link
                                href="/login"
                                className="font-semibold hover:text-white/80 transition-colors"
                            >
                                ← Back to Login
                            </Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}

export default ForgotPassword;
