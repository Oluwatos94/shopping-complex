import React, { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';

export interface GuestLayoutProps {
    children: ReactNode;
    title?: string;
    description?: string;
    showBackLink?: boolean;
    className?: string;
}

const GuestLayout: React.FC<GuestLayoutProps> = ({
    children,
    title = 'Shopping Complex',
    description = 'Your trusted marketplace connecting customers with quality vendors in real-time',
    showBackLink = true,
    className = '',
}) => {
    return (
        <>
            <Head>
                <title>{title}</title>
                <meta name="description" content={description} />
            </Head>

            <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-primary-light to-white py-12 px-4">
                {/* Logo/Brand */}
                <div className="mb-8 text-center">
                    <Link href="/" className="inline-flex items-center space-x-2">
                        <div className="bg-primary-olive text-white font-bold text-3xl px-6 py-3 rounded-lg shadow-lg">
                            SC
                        </div>
                        <span className="text-primary-dark font-bold text-2xl">
                            Shopping Complex
                        </span>
                    </Link>
                </div>

                {/* Main Content Card */}
                <div className={`w-full max-w-md ${className}`}>
                    <div className="bg-white rounded-2xl shadow-xl p-8">
                        {children}
                    </div>

                    {/* Back to Home Link */}
                    {showBackLink && (
                        <div className="mt-6 text-center">
                            <Link
                                href="/"
                                className="text-primary-brown hover:text-primary-olive transition-colors duration-300"
                            >
                                ← Back to Home
                            </Link>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="mt-8 text-center text-sm text-primary-brown">
                    <p>&copy; {new Date().getFullYear()} Shopping Complex. All rights reserved.</p>
                </div>
            </div>
        </>
    );
};

export default GuestLayout;
