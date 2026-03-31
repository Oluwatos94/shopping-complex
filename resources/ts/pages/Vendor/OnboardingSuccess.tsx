import { Head, Link } from '@inertiajs/react';

export default function OnboardingSuccess() {
    return (
        <>
            <Head title="Application Submitted - Shopping Complex" />

            <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                <div className="max-w-md w-full text-center">
                    {/* Success Icon */}
                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg className="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    {/* Title */}
                    <h1 className="text-2xl font-serif font-bold text-gray-900 mb-2">
                        Application Submitted!
                    </h1>

                    {/* Description */}
                    <p className="text-gray-600 mb-8">
                        Thank you for applying to become a vendor on Shopping Complex.
                        Our team will review your application and get back to you within 2-3 business days.
                    </p>

                    {/* What's Next */}
                    <div className="bg-white rounded-xl p-6 text-left mb-8 border border-gray-200">
                        <h2 className="font-semibold text-gray-900 mb-4">What happens next?</h2>
                        <ul className="space-y-3">
                            <li className="flex items-start gap-3">
                                <div className="w-6 h-6 bg-primary-olive/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span className="text-xs font-semibold text-primary-olive">1</span>
                                </div>
                                <span className="text-sm text-gray-600">
                                    Our team will verify your business documents
                                </span>
                            </li>
                            <li className="flex items-start gap-3">
                                <div className="w-6 h-6 bg-primary-olive/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span className="text-xs font-semibold text-primary-olive">2</span>
                                </div>
                                <span className="text-sm text-gray-600">
                                    You'll receive an email notification about your application status
                                </span>
                            </li>
                            <li className="flex items-start gap-3">
                                <div className="w-6 h-6 bg-primary-olive/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span className="text-xs font-semibold text-primary-olive">3</span>
                                </div>
                                <span className="text-sm text-gray-600">
                                    Once approved, you can start adding products to your store
                                </span>
                            </li>
                        </ul>
                    </div>

                    {/* Actions */}
                    <div className="space-y-3">
                        <Link
                            href="/"
                            className="block w-full py-3 bg-primary-olive text-white rounded-lg font-medium hover:bg-primary-dark transition-colors"
                        >
                            Back to Home
                        </Link>
                        <Link
                            href="/products"
                            className="block w-full py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                        >
                            Browse Products
                        </Link>
                    </div>

                    {/* Support */}
                    <p className="text-sm text-gray-500 mt-8">
                        Have questions?{' '}
                        <a href="mailto:support@shoppingcomplex.com" className="text-primary-olive hover:underline">
                            Contact our support team
                        </a>
                    </p>
                </div>
            </div>
        </>
    );
}
