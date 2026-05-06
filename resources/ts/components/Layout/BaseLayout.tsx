import React, { ReactNode } from 'react';
import { Head } from '@inertiajs/react';

export interface BaseLayoutProps {
    children: ReactNode;
    title?: string;
    description?: string;
    showHeader?: boolean;
    showFooter?: boolean;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';
    className?: string;
}

const BaseLayout: React.FC<BaseLayoutProps> = ({
    children,
    title = 'jiidaa',
    description = 'Your trusted marketplace connecting customers with quality vendors in real-time',
    showHeader = true,
    showFooter = true,
    maxWidth = 'full',
    className = '',
}) => {
    const maxWidthClasses = {
        sm: 'max-w-screen-sm',
        md: 'max-w-screen-md',
        lg: 'max-w-screen-lg',
        xl: 'max-w-screen-xl',
        '2xl': 'max-w-screen-2xl',
        full: 'max-w-full',
    };

    return (
        <>
            <Head>
                <title>{title}</title>
                <meta name="description" content={description} />
                <meta property="og:title" content={title} />
                <meta property="og:description" content={description} />
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={title} />
                <meta name="twitter:description" content={description} />
            </Head>

            <div className="min-h-screen flex flex-col bg-white">
                {/* Header section - can be conditionally rendered */}
                {showHeader && (
                    <header className="flex-shrink-0">
                        {/* Header content will be added here or passed as prop */}
                    </header>
                )}

                {/* Main content */}
                <main className={`flex-grow ${maxWidthClasses[maxWidth]} w-full mx-auto ${className}`}>
                    {children}
                </main>

                {/* Footer section - can be conditionally rendered */}
                {showFooter && (
                    <footer className="flex-shrink-0">
                        {/* Footer content will be added here or passed as prop */}
                    </footer>
                )}
            </div>
        </>
    );
};

export default BaseLayout;
