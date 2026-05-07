import React from 'react';
import { Head, Link } from '@inertiajs/react';

const Privacy: React.FC = () => {
    return (
        <>
            <Head title="Privacy Policy - jiidaa" />
            <div className="min-h-screen bg-gray-50">
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="container mx-auto px-4 py-3">
                        <Link href="/">
                            <img src="/logo/light.svg" alt="jiidaa" className="h-10 w-auto" />
                        </Link>
                    </div>
                </nav>

                <div className="max-w-3xl mx-auto px-4 py-12">
                    <h1 className="text-3xl font-bold text-primary-dark mb-2">Privacy Policy</h1>
                    <p className="text-sm text-gray-400 mb-10">Last updated: April 2026</p>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8 space-y-8 text-primary-brown leading-relaxed">

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">1. Introduction</h2>
                            <p>jiidaa ("we", "our", or "us") operates a local vendor discovery platform accessible via web and WhatsApp. This Privacy Policy explains how we collect, use, and protect your personal information when you use our services.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">2. Information We Collect</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li><strong>Account information:</strong> Name, email address, and password when you register.</li>
                                <li><strong>Vendor information:</strong> Business name, address, WhatsApp number, and product listings.</li>
                                <li><strong>WhatsApp interactions:</strong> Phone number and message content when you use our WhatsApp bot to search for vendors.</li>
                                <li><strong>Usage data:</strong> Pages visited, search queries, and interactions with vendor profiles.</li>
                                <li><strong>Location data:</strong> Approximate location you share when searching for nearby vendors via WhatsApp.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">3. How We Use Your Information</h2>
                            <ul className="list-disc pl-5 space-y-2">
                                <li>To connect buyers with nearby vendors based on location and product searches.</li>
                                <li>To operate the WhatsApp bot and deliver vendor results.</li>
                                <li>To maintain your account and vendor profile.</li>
                                <li>To improve platform performance and user experience.</li>
                                <li>To send notifications about messages, orders, and platform updates.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">4. WhatsApp Data</h2>
                            <p>When you interact with our WhatsApp bot, your phone number and messages are processed through Meta's WhatsApp Business API. We store your search queries and location data solely to provide vendor results. We do not share this data with third parties for marketing purposes.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">5. Data Sharing</h2>
                            <p>We do not sell your personal data. We may share information with:</p>
                            <ul className="list-disc pl-5 space-y-2 mt-2">
                                <li><strong>Vendors</strong> — only the information needed to fulfil your request (e.g. your message content when you initiate a chat).</li>
                                <li><strong>Service providers</strong> — such as hosting and infrastructure providers who process data on our behalf.</li>
                                <li><strong>Legal authorities</strong> — when required by law.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">6. Data Retention</h2>
                            <p>We retain your data for as long as your account is active or as needed to provide services. WhatsApp session data is automatically cleared after 30 minutes of inactivity. You may request deletion of your data at any time.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">7. Your Rights</h2>
                            <p>You have the right to access, correct, or delete your personal data. To exercise these rights, contact us at <a href="mailto:support.shoppingComplex@gmail.com" className="text-primary-olive hover:underline">support.shoppingComplex@gmail.com</a>.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">8. Security</h2>
                            <p>We implement industry-standard security measures including encrypted connections (HTTPS), hashed passwords, and access controls to protect your data.</p>
                        </section>

                        <section>
                            <h2 className="text-xl font-semibold text-primary-dark mb-3">9. Contact</h2>
                            <p>If you have questions about this Privacy Policy, contact us at:<br />
                            <a href="mailto:support.shoppingComplex@gmail.com" className="text-primary-olive hover:underline">support.shoppingComplex@gmail.com</a></p>
                        </section>

                    </div>

                    <div className="mt-8 text-center">
                        <Link href="/" className="text-primary-olive hover:underline text-sm">
                            ← Back to jiidaa
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
};

export default Privacy;
