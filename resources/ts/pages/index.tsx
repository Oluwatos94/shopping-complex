import React from 'react';
import Header from '@/components/Header';
import HeroSection from '@/components/HeroSection';
import TrustBar from '@/components/TrustBar';
import HowItWorksSection from '@/components/HowItWorksSection';
import CoreFeaturesSection from '@/components/CoreFeaturesSection';
import VendorsSection from '@/components/VendorsSection';
import TestimonialsSection from '@/components/TestimonialsSection';
import FAQSection from '@/components/FAQSection';
import FinalCTASection from '@/components/FinalCTASection';
import Footer from '@/components/Footer';

const Index: React.FC = () => {
    return (
        <div className="min-h-screen bg-white">
            <Header />
            <main>
                <HeroSection />
                <TrustBar />
                <HowItWorksSection />
                <CoreFeaturesSection />
                <VendorsSection />
                <TestimonialsSection />
                <FAQSection />
                <FinalCTASection />
            </main>
            <Footer />
        </div>
    );
};

export default Index;
