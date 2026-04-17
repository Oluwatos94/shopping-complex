import React from 'react';
import { Head, Link } from '@inertiajs/react';

const Terms: React.FC = () => {
    return (
        <>
            <Head title="Terms of Service - Shopping Complex" />
            <div className="min-h-screen bg-gray-50">
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="container mx-auto px-4 py-3">
                        <Link href="/" className="flex items-center space-x-3">
                            <img src="/logo/dark-mode-2.svg" alt="Shopping Complex" className="h-10 w-auto" />
                            <span className="text-xl font-bold text-gray-900">Shopping Complex</span>
                        </Link>
                    </div>
                </nav>

                <div className="max-w-3xl mx-auto px-4 py-12">
                    <h1 className="text-3xl font-bold text-primary-dark mb-2">Terms of Service</h1>
                    <p className="text-sm text-gray-400 mb-10">Last updated: April 2026</p>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8 space-y-8 text-primary-brown leading-relaxed">

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">1. Acceptance of Terms</h2>
                            <p>By accessing or using Shopping Complex ("the platform"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the platform.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">2. Description of Service</h2>
                            <p>Shopping Complex is a local vendor discovery platform that connects buyers with nearby vendors via web and WhatsApp. Vendors can list products and services; buyers can search, browse, and contact vendors directly.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">3. User Accounts</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>You must provide accurate information when creating an account.</li>
                                <li>You are responsible for maintaining the security of your account credentials.</li>
                                <li>You must be at least 18 years old to use the platform.</li>
                                <li>We reserve the right to suspend or terminate accounts that violate these terms.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">4. Vendor Responsibilities</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>Vendors must provide accurate business information, product descriptions, and pricing.</li>
                                <li>Vendors are responsible for fulfilling orders and communicating with buyers in a timely manner.</li>
                                <li>Vendors must not list prohibited, counterfeit, or illegal products.</li>
                                <li>Shopping Complex reserves the right to remove listings or suspend vendors who violate these rules.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">5. Buyer Responsibilities</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>Buyers interact directly with vendors for purchases, delivery, and payment.</li>
                                <li>Shopping Complex is a discovery platform and is not a party to transactions between buyers and vendors.</li>
                                <li>Buyers should exercise due diligence before making any purchase.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">6. WhatsApp Bot</h2>
                            <p>By messaging our WhatsApp bot, you consent to receiving automated responses. The bot uses your location (when shared) to find nearby vendors. Standard WhatsApp messaging rates from your carrier may apply.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">7. Prohibited Conduct</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>Spamming, harassing, or abusing other users or vendors.</li>
                                <li>Posting false, misleading, or fraudulent content.</li>
                                <li>Attempting to compromise the security or integrity of the platform.</li>
                                <li>Using automated tools to scrape or abuse the platform.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">8. Limitation of Liability</h2>
                            <p>Shopping Complex is not liable for any disputes, losses, or damages arising from transactions between buyers and vendors. The platform is provided "as is" without warranties of any kind.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">9. Changes to Terms</h2>
                            <p>We may update these Terms at any time. Continued use of the platform after changes constitutes acceptance of the new terms.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">10. Contact</h2>
                            <p>For questions about these Terms, contact us at:<br />
                            <a href="mailto:support.shoppingComplex@gmail.com" className="text-primary-olive hover:underline">support.shoppingComplex@gmail.com</a></p>
                        </section>

                    </div>

                    <div className="mt-8 text-center">
                        <Link href="/" className="text-primary-olive hover:underline text-sm">
                            ← Back to Shopping Complex
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
};

export default Terms;
