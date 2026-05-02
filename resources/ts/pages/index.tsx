import React from 'react';
import Header from '@/components/Header';
import HeroSection from '@/components/HeroSection';
import WhatsAppBotSection from '@/components/WhatsAppBotSection';
import VendorCallToAction from '@/components/VendorCallToAction';
import CategoriesSection from '@/components/CategoriesSection';
import BuyerCallToAction from '@/components/BuyerCallToAction';
import HowItWorksSection from '@/components/HowItWorksSection';
import TestimonialsSection from '@/components/TestimonialsSection';
import FAQSection from '@/components/FAQSection';
import Footer from '@/components/Footer';

const Index: React.FC = () => {
    return (
        <div className="min-h-screen bg-white">
            <Header />
            <main>
                <HeroSection />
                <WhatsAppBotSection />
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
