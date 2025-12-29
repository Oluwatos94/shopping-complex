import React from 'react';
import { HowItWorksStep } from '@/types/landing';

const HowItWorksSection: React.FC = () => {
    const steps: HowItWorksStep[] = [
        {
            step: 1,
            title: 'Browse & Select',
            description: 'Browse through categories or search for specific products you need. View vendor profiles, ratings, and offerings.',
            icon: '🔍',
        },
        {
            step: 2,
            title: 'Connect Instantly',
            description: 'Get matched with available vendors in real-time. Like Uber, see nearby vendors ready to serve you immediately.',
            icon: '⚡',
        },
        {
            step: 3,
            title: 'Chat & Negotiate',
            description: 'Communicate directly with vendors through our real-time chat. Discuss details, negotiate prices, and customize your order.',
            icon: '💬',
        },
        {
            step: 4,
            title: 'Track & Receive',
            description: 'Track your order in real-time from preparation to delivery. Get notified at every step until it reaches you.',
            icon: '📦',
        },
    ];

    return (
        <section className="bg-gradient-to-b from-white to-primary-light py-16 lg:py-24">
            <div className="container mx-auto px-4">
                <div className="text-center mb-16">
                    <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                        How It Works
                    </h2>
                    <p className="text-lg text-primary-brown max-w-3xl mx-auto">
                        Experience seamless shopping with real-time vendor connections. Just like calling a ride,
                        connect with vendors instantly and get what you need delivered to your doorstep.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                    {steps.map((step) => (
                        <div
                            key={step.step}
                            className="relative bg-white rounded-xl p-8 shadow-lg hover:shadow-2xl transition-shadow duration-300"
                        >
                            <div className="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-primary-olive text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                                {step.step}
                            </div>
                            <div className="text-6xl mb-6 text-center mt-4">
                                {step.icon}
                            </div>
                            <h3 className="text-xl font-bold text-primary-dark mb-3 text-center">
                                {step.title}
                            </h3>
                            <p className="text-primary-brown text-center leading-relaxed">
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="bg-gradient-to-r from-primary-dark to-primary-brown rounded-2xl p-8 lg:p-12 shadow-2xl">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                        <div className="text-white">
                            <h3 className="text-3xl font-bold mb-4">
                                Real-Time Connection Like Never Before
                            </h3>
                            <p className="text-primary-light text-lg mb-6 leading-relaxed">
                                Our platform uses cutting-edge technology to connect you with vendors in real-time.
                                See who's available, their current location, estimated delivery time, and ratings—all before you make a choice.
                            </p>
                            <ul className="space-y-3">
                                <li className="flex items-start">
                                    <svg className="w-6 h-6 text-primary-peach mr-3 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    <span className="text-primary-light">Live vendor availability tracking</span>
                                </li>
                                <li className="flex items-start">
                                    <svg className="w-6 h-6 text-primary-peach mr-3 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    <span className="text-primary-light">Instant messaging with vendors</span>
                                </li>
                                <li className="flex items-start">
                                    <svg className="w-6 h-6 text-primary-peach mr-3 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    <span className="text-primary-light">Real-time order tracking and updates</span>
                                </li>
                            </ul>
                        </div>
                        <div className="relative">
                            <img
                                src="/images/shopping-image2.jpg"
                                alt="Real-time shopping experience"
                                className="rounded-lg shadow-2xl w-full h-auto"
                            />
                            <div className="absolute -bottom-4 -right-4 bg-primary-peach text-white px-6 py-3 rounded-lg shadow-lg font-bold">
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
