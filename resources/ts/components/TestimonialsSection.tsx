import React from 'react';
import { Testimonial } from '@/types/landing';

const TestimonialsSection: React.FC = () => {
    const testimonials: Testimonial[] = [
        {
            id: 1,
            customerName: 'Sarah Johnson',
            rating: 5,
            comment: 'Shopping Complex has completely changed how I shop! The real-time connection with vendors is incredible. I found exactly what I needed within minutes, and the delivery was super fast. Highly recommend!',
            date: '2 days ago',
        },
        {
            id: 2,
            customerName: 'Michael Chen',
            rating: 5,
            comment: 'As a busy professional, this platform saves me so much time. I can chat with vendors, negotiate, and track my orders all in one place. The vendor quality is consistently excellent!',
            date: '1 week ago',
        },
        {
            id: 3,
            customerName: 'Aisha Okonkwo',
            rating: 5,
            comment: 'I love supporting local vendors, and Shopping Complex makes it so easy. The real-time tracking feature is just like Uber—I always know when my order will arrive. Great platform!',
            date: '2 weeks ago',
        },
        {
            id: 4,
            customerName: 'David Martinez',
            rating: 4,
            comment: 'Excellent service and wide variety of products. The instant messaging with vendors is a game-changer. I can ask questions and get answers immediately before making a purchase.',
            date: '3 weeks ago',
        },
        {
            id: 5,
            customerName: 'Fatima Al-Rahman',
            rating: 5,
            comment: 'The best shopping experience I have had online! The vendors are professional, responsive, and the platform is so easy to use. I have made multiple purchases and never been disappointed.',
            date: '1 month ago',
        },
        {
            id: 6,
            customerName: 'James Wilson',
            rating: 5,
            comment: 'Shopping Complex connects me with quality vendors near me. The categorization makes it easy to find what I need, and the real-time updates keep me informed throughout the entire process.',
            date: '1 month ago',
        },
    ];

    const renderStars = (rating: number) => {
        return (
            <div className="flex gap-1">
                {[...Array(5)].map((_, index) => (
                    <svg
                        key={index}
                        className={`w-5 h-5 ${
                            index < rating ? 'text-yellow-400' : 'text-gray-300'
                        }`}
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                ))}
            </div>
        );
    };

    return (
        <section className="bg-white py-16 lg:py-24">
            <div className="container mx-auto px-4">
                <div className="text-center mb-16">
                    <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                        What Our Customers Say
                    </h2>
                    <p className="text-lg text-primary-brown max-w-2xl mx-auto">
                        Join thousands of satisfied customers who trust Shopping Complex for their daily needs.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {testimonials.map((testimonial) => (
                        <div
                            key={testimonial.id}
                            className="bg-gradient-to-br from-primary-light to-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-shadow duration-300 border border-primary-olive/20"
                        >
                            <div className="flex items-center justify-between mb-4">
                                {renderStars(testimonial.rating)}
                                <span className="text-sm text-primary-brown">
                                    {testimonial.date}
                                </span>
                            </div>

                            <p className="text-primary-dark mb-6 leading-relaxed italic">
                                "{testimonial.comment}"
                            </p>

                            <div className="flex items-center pt-4 border-t border-primary-olive/20">
                                <div className="bg-primary-olive text-white w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg mr-4">
                                    {testimonial.customerName.charAt(0)}
                                </div>
                                <div>
                                    <div className="font-semibold text-primary-dark">
                                        {testimonial.customerName}
                                    </div>
                                    <div className="text-sm text-primary-brown">
                                        Verified Customer
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-16 text-center bg-gradient-to-r from-primary-olive to-primary-brown rounded-2xl p-12 text-white">
                    <h3 className="text-3xl font-bold mb-4">Join Our Growing Community</h3>
                    <p className="text-primary-light text-lg mb-8 max-w-2xl mx-auto">
                        Over 10,000 customers trust Shopping Complex for their shopping needs.
                        Experience the future of shopping today.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">4.8/5</div>
                            <div className="text-sm text-primary-light">Average Rating</div>
                        </div>
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">98%</div>
                            <div className="text-sm text-primary-light">Satisfaction Rate</div>
                        </div>
                        <div className="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-4">
                            <div className="text-3xl font-bold">24/7</div>
                            <div className="text-sm text-primary-light">Customer Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default TestimonialsSection;
