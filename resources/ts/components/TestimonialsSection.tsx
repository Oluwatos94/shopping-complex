import React from 'react';

interface Testimonial {
    quote: string;
    name: string;
    role: string;
    date: string;
    rating: number;
}

const testimonials: Testimonial[] = [
    {
        quote: 'I love that I can chat with vendors on WhatsApp, no new app, no password. I found a great electronics repair guy 400m from my house in under 2 minutes',
        name: 'James Wilson',
        role: 'Software engineer, Lagos',
        date: '2 weeks ago',
        rating: 5,
    },
    {
        quote: 'Jiidaa solved my biggest problem — people walking past my store but not knowing I exist. Now I get 20+ WhatsApp inquiries weekly. Worth every kobo',
        name: 'Fatima Abdulrahman',
        role: 'Verified food vendor, Ikeja',
        date: '1 month ago',
        rating: 5,
    },
    {
        quote: "Since joining Jiidaa I've been getting 3-4 new customers every single day just from people walking nearby who found me on the map. It's genuinely changed my business",
        name: 'Adaobi Nwosu',
        role: 'Fashion Vendor, Oshodi Market',
        date: '3 months ago',
        rating: 5,
    },
    {
        quote: 'Found a tailor two streets away I never knew existed. Sent measurements over WhatsApp and picked up my outfit the same week. So convenient.',
        name: 'Chinedu Okafor',
        role: 'Shopper, Surulere',
        date: '3 weeks ago',
        rating: 5,
    },
    {
        quote: 'As a small bakery, the foot-traffic discovery is everything. People searching for cake nearby now find me first. Orders have doubled.',
        name: 'Blessing Eze',
        role: 'Verified baker, Yaba',
        date: '2 months ago',
        rating: 4,
    },
    {
        quote: 'No app to install, no sign-up stress. I just shared my location and started chatting with sellers around me. This is how shopping should work.',
        name: 'Tunde Bakare',
        role: 'Shopper, Lekki',
        date: '5 days ago',
        rating: 5,
    },
];

const Stars: React.FC<{ rating: number }> = ({ rating }) => (
    <div className="flex gap-1 text-brand-star">
        {Array.from({ length: rating }).map((_, i) => (
            <svg key={i} className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1L12 2Z" />
            </svg>
        ))}
    </div>
);

const TestimonialCard: React.FC<{ t: Testimonial }> = ({ t }) => (
    <figure className="mr-7 w-[300px] shrink-0 rounded-2xl bg-brand-surface p-7 sm:w-[380px]">
        <div className="flex items-center justify-between">
            <Stars rating={t.rating} />
            <span className="text-[13px] text-brand-muted">{t.date}</span>
        </div>
        <blockquote className="mt-6 font-serif text-[15px] italic leading-relaxed text-brand-ink/80">
            &ldquo;{t.quote}&rdquo;
        </blockquote>
        <figcaption className="mt-8 flex items-center gap-3">
            <span className="flex h-11 w-11 items-center justify-center rounded-full bg-brand-ink text-sm font-bold text-white">
                {t.name.charAt(0)}
            </span>
            <div>
                <p className="text-[15px] font-bold text-brand-ink">{t.name}</p>
                <p className="text-[13px] text-brand-muted">{t.role}</p>
            </div>
        </figcaption>
    </figure>
);

const TestimonialsSection: React.FC = () => {
    return (
        <section className="bg-white py-20 font-display text-brand-ink">
            <div className="mx-auto max-w-[1320px] px-6 lg:px-10">
                {/* Header */}
                <div className="mx-auto max-w-2xl text-center">
                    <p className="text-sm font-bold uppercase tracking-[0.18em] text-brand-ink">Testimonials</p>
                    <h2 className="mt-4 font-serif text-[48px] font-medium leading-tight text-brand-ink">
                        What Our Community Says
                    </h2>
                    <p className="mx-auto mt-4 max-w-md text-base leading-relaxed text-brand-muted">
                        Real stories from shoppers and vendors connecting on Jiidaa every day.
                    </p>
                </div>
            </div>

            {/* Auto-scrolling carousel */}
            <div className="group mt-16 overflow-hidden [mask-image:linear-gradient(to_right,transparent,#000_6%,#000_94%,transparent)]">
                <div className="flex w-max animate-marquee group-hover:[animation-play-state:paused]">
                    {[...testimonials, ...testimonials].map((t, i) => (
                        <TestimonialCard key={`${t.name}-${i}`} t={t} />
                    ))}
                </div>
            </div>
        </section>
    );
};

export default TestimonialsSection;
