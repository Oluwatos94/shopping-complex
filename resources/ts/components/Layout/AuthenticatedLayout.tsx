import React, { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { User } from '@/types';

export interface AuthenticatedLayoutProps {
    children: ReactNode;
    user?: User | null;
    title?: string;
    description?: string;
    className?: string;
}

const AuthenticatedLayout: React.FC<AuthenticatedLayoutProps> = ({
    children,
    user,
    title = 'jiidaa',
    description = 'Your trusted marketplace connecting customers with quality vendors in real-time',
    className = '',
}) => {
    return (
        <>
            <Head>
                <title>{title}</title>
                <meta name="description" content={description} />
            </Head>

            <div className="min-h-screen bg-gray-100">
                {/* Top Navigation Bar */}
                <nav className="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
                    <div className="container mx-auto px-4 py-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                {/* Logo */}
                                <img
                                    src="/logo/jiidaa.jpeg"
                                    alt="jiidaa"
                                    className="h-10 w-auto"
                                />
                                <h1 className="text-xl font-bold text-gray-900">
                                    jiidaa
                                </h1>
                            </div>

                            {/* User Menu */}
                            <div className="flex items-center space-x-4">
                                {user ? (
                                    <>
                                        <span className="text-sm text-gray-600">
                                            Welcome, <span className="font-medium text-gray-900">{user.name}</span>
                                        </span>
                                        {/* User dropdown menu will go here */}
                                    </>
                                ) : (
                                    <a
                                        href="/login"
                                        className="text-sm font-semibold px-4 py-2 bg-[#272518] hover:bg-[#3a3520] text-white rounded-lg transition-colors"
                                    >
                                        Sign In
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Main Content Area */}
                <main className={`container mx-auto px-4 py-8 ${className}`}>
                    {children}
                </main>
            </div>
        </>
    );
};

export default AuthenticatedLayout;
