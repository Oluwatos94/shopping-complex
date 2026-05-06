import React from 'react';

const steps = [
    {
        step: 1,
        title: 'Browse & Select',
        description: 'Browse through categories or search for specific products you need. View vendor profiles, ratings, and offerings.',
        icon: (
            <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        ),
    },
    {
        step: 2,
        title: 'Connect Instantly',
        description: 'Get matched with available vendors in real-time. See nearby vendors ready to serve you immediately.',
        icon: (
            <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        ),
    },
    {
        step: 3,
        title: 'Chat & Negotiate',
        description: 'Communicate directly with vendors through our real-time chat or WhatsApp. Discuss details, negotiate prices, and customise your order.',
        icon: (
            <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        ),
    },
];

const HowItWorksSection: React.FC = () => {
    return (
        <section className="bg-gradient-to-b from-white to-primary-light py-16 lg:py-24">
            <div className="container mx-auto px-4">

                {/* Heading */}
                <div className="text-center mb-16">
                    <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                        How It Works
                    </h2>
                    <p className="text-lg text-primary-brown max-w-2xl mx-auto">
                        Three simple steps to connect with local vendors and get what you need fast.
                    </p>
                </div>

                {/* Steps */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 mb-16 relative">

                    {/* Connector line — desktop only */}
                    <div className="hidden md:block absolute top-12 left-[calc(16.67%+1rem)] right-[calc(16.67%+1rem)] h-px bg-primary-olive/20 z-0" />

                    {steps.map((step, i) => (
                        <div
                            key={step.step}
                            className="relative bg-white rounded-2xl p-8 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col items-center text-center z-10"
                        >
                            {/* Step number badge */}
                            <div className="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-primary-dark text-white text-xs font-bold flex items-center justify-center shadow-md">
                                {step.step}
                            </div>

                            {/* Icon circle */}
                            <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mb-5 mt-2 ${
                                i === 0 ? 'bg-primary-olive/10 text-primary-olive' :
                                i === 1 ? 'bg-primary-peach/15 text-primary-brown' :
                                'bg-primary-dark/8 text-primary-dark'
                            }`}>
                                {step.icon}
                            </div>

                            <h3 className="text-lg font-bold text-primary-dark mb-3">
                                {step.title}
                            </h3>
                            <p className="text-primary-brown text-sm leading-relaxed">
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>

                {/* Real-Time Connection feature block */}
                <div className="bg-primary-dark rounded-2xl p-8 lg:p-12 shadow-2xl overflow-hidden relative">
                    <div className="absolute -top-16 -right-16 w-64 h-64 bg-primary-olive/10 rounded-full pointer-events-none" />
                    <div className="absolute -bottom-12 -left-12 w-48 h-48 bg-primary-peach/10 rounded-full pointer-events-none" />

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center relative z-10">
                        <div className="text-white">
                            <span className="inline-block bg-primary-olive/20 text-primary-light text-xs font-semibold uppercase tracking-widest px-3 py-1 rounded-full mb-4">
                                Platform Feature
                            </span>
                            <h3 className="text-2xl md:text-3xl font-bold mb-4">
                                Real-Time Connection Like Never Before
                            </h3>
                            <p className="text-primary-light text-base mb-6 leading-relaxed">
                                Connect with vendors in real-time. See who's available, their location,
                                estimated delivery time, and ratings — all before you decide.
                            </p>
                            <ul className="space-y-3">
                                {[
                                    'Live vendor availability tracking',
                                    'Instant messaging with vendors',
                                    'Real-time order tracking and updates',
                                ].map((item) => (
                                    <li key={item} className="flex items-center gap-3">
                                        <span className="flex-shrink-0 w-5 h-5 rounded-full bg-primary-olive/30 flex items-center justify-center">
                                            <svg className="w-3 h-3 text-primary-peach" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                            </svg>
                                        </span>
                                        <span className="text-primary-light text-sm">{item}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="relative">
                            <img
                                src="https://pub-c54b269b978445a983e2a4569f9b4dce.r2.dev/images/shopping-image2.jpg"
                                alt="Real-time shopping experience"
                                className="rounded-xl shadow-2xl w-full h-auto object-cover"
                            />
                            <div className="absolute -bottom-3 -right-3 bg-primary-olive text-white px-5 py-2.5 rounded-lg shadow-lg font-semibold text-sm">
                                Real-Time Updates
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    );
};

export default HowItWorksSection;
