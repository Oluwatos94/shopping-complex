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
    title = 'jiidaa',
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
                <div className="mb-8">
                    <Link href="/">
                        <img src="/logo/light.svg" alt="jiidaa" className="h-16 w-auto" />
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
                    <p>&copy; {new Date().getFullYear()} jiidaa. All rights reserved.</p>
                </div>
            </div>
        </>
    );
};

export default GuestLayout;
