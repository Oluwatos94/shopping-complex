import React from 'react';
import { usePage } from '@inertiajs/react';
import Header from '@/components/Header';
import HeroSection from '@/components/HeroSection';
import VendorCallToAction from '@/components/VendorCallToAction';
import CategoriesSection from '@/components/CategoriesSection';
import BuyerCallToAction from '@/components/BuyerCallToAction';
import HowItWorksSection from '@/components/HowItWorksSection';
import TestimonialsSection from '@/components/TestimonialsSection';
import FAQSection from '@/components/FAQSection';
import Footer from '@/components/Footer';

interface PageProps {
    [key: string]: unknown;
    platformWhatsApp?: string;
}

const Index: React.FC = () => {
    const { platformWhatsApp } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-white">
            <Header />
            <main>
                <HeroSection platformWhatsApp={platformWhatsApp ?? ''} />
                <VendorCallToAction />
                <CategoriesSection />
                <BuyerCallToAction />
                <HowItWorksSection />
                <TestimonialsSection />
                <FAQSection />
            </main>
            <Footer />
        </div>
    );
};

export default Index;
