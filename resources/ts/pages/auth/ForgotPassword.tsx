import { Link , useForm, usePage } from "@inertiajs/react";

function ForgotPassword() {
  const { flash } = usePage().props as any;
  const { data, setData, post, processing, errors } = useForm({
      email: "",
  });

  const handleSubmit = (e: React.FormEvent) => {
      e.preventDefault();
      post("/password/email");
  };
    return (
        <div className="h-screen w-full fixed inset-0 flex items-center justify-center p-4 sm:p-6 md:p-8 overflow-hidden">
            {/* Background Image */}
            <img
                src="https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/Polo Park 2.jpg"
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
                        <h1 className="text-3xl lg:text-5xl font-serif font-semibold text-white mb-4">
                            jiidaa
                        </h1>
                    </div>
                </div>

                {/* Right Half - Sign up form (overlays the left half) */}
                <div className="absolute right-0 top-0 bottom-0 w-full md:w-1/2 bg-[#43442F]/60 backdrop-blur-md p-6 sm:p-8 lg:p-12 rounded-l-2xl flex flex-col">
                    {/* Header - aligned to top center */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl lg:text-3xl font-serif font-medium text-white mb-2">
                            Forget Password?
                        </h2>
                        <p className="text-white/80 text-base">
                            Enter your email and we'll send you a reset link.
                        </p>
                    </div>

                    {/* Success Message */}
                    {flash?.status && (
                        <div className="bg-green-500/20 border border-green-400 text-green-300 px-4 py-3 rounded-lg mb-4 text-center">
                            {flash.status}
                        </div>
                    )}

                    <form
                        className="space-y-5 flex-1 flex flex-col justify-center gap-6 lg:gap-8"
                        onSubmit={handleSubmit}
                    >
                        {/* Email Input */}
                        <div>
                            <label className="block text-white text-xl font-serif font-medium mb-2 text-left">
                                Email
                            </label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                                disabled={processing}
                                className={`w-full px-4 py-3 bg-transparent border rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 transition-all disabled:opacity-50 disabled:cursor-not-allowed ${
                                    errors.email
                                        ? "border-red-400 focus:ring-red-400/50"
                                        : "border-white focus:ring-white/50"
                                }`}
                            />
                            {errors.email && (
                                <p className="text-red-400 text-sm mt-1">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        {/* Send Email Button */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3 bg-[#272518] text-white font-semibold rounded-3xl hover:bg-[#272518]/90 transition-all mt-6 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Sending...' : 'Send Email'}
                        </button>

                        {/* Sign In Link */}
                        <p className="text-white text-center mt-1">
                            <Link
                                href="/login"
                                className={`font-semibold transition-colors ${processing ? 'pointer-events-none opacity-50' : 'hover:text-white/80'}`}
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
