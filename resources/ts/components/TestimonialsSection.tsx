import React, { useState, useEffect, useCallback, useRef } from 'react';
import { Testimonial } from '@/types/landing';

const testimonials: Testimonial[] = [
    {
        id: 1,
        customerName: 'Sarah Johnson',
        rating: 5,
        comment: 'jiidaa has completely changed how I shop! The real-time connection with vendors is incredible. I found exactly what I needed within minutes, and the delivery was super fast. Highly recommend!',
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
        comment: 'I love supporting local vendors, and jiidaa makes it so easy. The real-time tracking feature is just like Uber—I always know when my order will arrive. Great platform!',
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
        comment: 'jiidaa connects me with quality vendors near me. The categorization makes it easy to find what I need, and the real-time updates keep me informed throughout the entire process.',
        date: '1 month ago',
    },
];

function getVisibleCount(): number {
    if (typeof window === 'undefined') return 3;
    if (window.innerWidth >= 1024) return 3;
    if (window.innerWidth >= 768) return 2;
    return 1;
}

function renderStars(rating: number) {
    return (
        <div className="flex gap-1">
            {[...Array(5)].map((_, i) => (
                <svg
                    key={i}
                    className={`w-4 h-4 ${i < rating ? 'text-yellow-400' : 'text-gray-300'}`}
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

const TestimonialsSection: React.FC = () => {
    const [current, setCurrent] = useState(0);
    const [visibleCount, setVisibleCount] = useState(getVisibleCount);
    const [paused, setPaused] = useState(false);
    const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const total = testimonials.length;
    const maxIndex = total - visibleCount;

    // Update visible count on resize
    useEffect(() => {
        const handleResize = () => {
            const next = getVisibleCount();
            setVisibleCount(next);
            setCurrent((c) => Math.min(c, total - next));
        };
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, [total]);

    const goNext = useCallback(() => {
        setCurrent((c) => (c >= maxIndex ? 0 : c + 1));
    }, [maxIndex]);

    const goPrev = useCallback(() => {
        setCurrent((c) => (c <= 0 ? maxIndex : c - 1));
    }, [maxIndex]);

    // Auto-advance
    useEffect(() => {
        if (paused) return;
        timerRef.current = setInterval(goNext, 4000);
        return () => {
            if (timerRef.current) clearInterval(timerRef.current);
        };
    }, [goNext, paused]);

    const cardWidthPercent = 100 / visibleCount;
    const translateX = -(current * cardWidthPercent);

    return (
        <section className="bg-white pt-16 lg:pt-24 pb-10 lg:pb-12">
            <div className="container mx-auto px-4">
                {/* Heading */}
                <div className="text-center mb-12">
                    <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-primary-dark mb-4">
                        What Our Customers Say
                    </h2>
                    <p className="text-lg text-primary-brown max-w-2xl mx-auto">
                        Join thousands of satisfied customers who trust jiidaa for their daily needs.
                    </p>
                </div>

                {/* Carousel */}
                <div
                    className="relative"
                    onMouseEnter={() => setPaused(true)}
                    onMouseLeave={() => setPaused(false)}
                >
                    {/* Sliding track */}
                    <div className="overflow-hidden">
                        <div
                            className="flex transition-transform duration-500 ease-in-out"
                            style={{ transform: `translateX(${translateX}%)` }}
                        >
                            {testimonials.map((testimonial) => (
                                <div
                                    key={testimonial.id}
                                    className="flex-shrink-0 px-3"
                                    style={{ width: `${cardWidthPercent}%` }}
                                >
                                    <div className="bg-gradient-to-br from-primary-light to-white rounded-xl p-6 shadow-lg border border-primary-olive/20 h-full flex flex-col">
                                        <div className="flex items-center justify-between mb-4">
                                            {renderStars(testimonial.rating)}
                                            <span className="text-sm text-primary-brown">{testimonial.date}</span>
                                        </div>

                                        <p className="text-primary-dark mb-6 leading-relaxed italic flex-1">
                                            "{testimonial.comment}"
                                        </p>

                                        <div className="flex items-center pt-4 border-t border-primary-olive/20">
                                            <div className="bg-primary-olive text-white w-11 h-11 rounded-full flex items-center justify-center font-bold text-lg mr-3 shrink-0">
                                                {testimonial.customerName.charAt(0)}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-primary-dark">{testimonial.customerName}</div>
                                                <div className="text-sm text-primary-brown">Verified Customer</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Prev arrow */}
                    <button
                        onClick={goPrev}
                        className="absolute -left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white shadow-lg border border-gray-100 flex items-center justify-center text-primary-dark hover:bg-primary-olive hover:text-white transition-colors duration-200 z-10"
                        aria-label="Previous"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    {/* Next arrow */}
                    <button
                        onClick={goNext}
                        className="absolute -right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white shadow-lg border border-gray-100 flex items-center justify-center text-primary-dark hover:bg-primary-olive hover:text-white transition-colors duration-200 z-10"
                        aria-label="Next"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {/* Dot indicators */}
                <div className="flex justify-center gap-2 mt-8">
                    {Array.from({ length: maxIndex + 1 }).map((_, idx) => (
                        <button
                            key={idx}
                            onClick={() => setCurrent(idx)}
                            className={`rounded-full transition-all duration-300 ${
                                idx === current
                                    ? 'w-6 h-2.5 bg-primary-olive'
                                    : 'w-2.5 h-2.5 bg-gray-300 hover:bg-primary-olive/50'
                            }`}
                            aria-label={`Go to slide ${idx + 1}`}
                        />
                    ))}
                </div>

            </div>
        </section>
    );
};

export default TestimonialsSection;
