import React, { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import { User } from '@/types';

export interface AuthenticatedLayoutProps {
    children: ReactNode;
    user: User;
    title?: string;
    description?: string;
    className?: string;
}

const AuthenticatedLayout: React.FC<AuthenticatedLayoutProps> = ({
    children,
    user,
    title = 'Shopping Complex',
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
                <nav className="bg-white shadow-sm">
                    <div className="container mx-auto px-4 py-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <h1 className="text-xl font-bold text-primary-dark">
                                    Shopping Complex
                                </h1>
                            </div>

                            {/* User Menu */}
                            <div className="flex items-center space-x-4">
                                <span className="text-sm text-gray-600">
                                    Welcome, {user.name}
                                </span>
                                {/* User dropdown menu will go here */}
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
