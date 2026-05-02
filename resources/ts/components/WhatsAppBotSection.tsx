import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';

interface PageProps {
    [key: string]: unknown;
    platformWhatsApp?: string;
}

type Message = { from: 'bot' | 'user'; lines: string[]; time: string };

const conversations: Message[][] = [
    [
        { from: 'bot',  lines: ['Hi! What are you looking for today? 👋'], time: '3:52' },
        { from: 'user', lines: ['Nike sneakers near me'], time: '3:53' },
        { from: 'bot',  lines: ['Found 2 vendors near you 👟', '1. Crystal Wears — 0.8km', '2. Kicks & More — 1.5km'], time: '3:53' },
        { from: 'bot',  lines: ['Reply BACK to see other vendors or MENU to start a new search.'], time: '3:53' },
    ],
    [
        { from: 'bot',  lines: ['Crystal Wears', 'Nike sneakers', '₦20,000', '', 'Type CONTACT for their WhatsApp, NEXT/PREV to browse products, or BACK for other vendors.'], time: '3:54' },
        { from: 'user', lines: ['Contact'], time: '3:55' },
        { from: 'bot',  lines: ['Here is Crystal Wears\'s WhatsApp:', 'wa.me/816600XXXX', '', 'Tap the link to chat directly. Type MENU to search again.'], time: '3:55' },
    ],
];

const WhatsAppBotSection: React.FC = () => {
    const { platformWhatsApp } = usePage<PageProps>().props;
    const whatsAppHref = platformWhatsApp
        ? `https://wa.me/${platformWhatsApp.replace(/[^0-9]/g, '')}?text=Hi`
        : 'https://wa.me/';

    const [activeIndex, setActiveIndex] = useState(0);
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        const timer = setInterval(() => {
            setVisible(false);
            setTimeout(() => {
                setActiveIndex(prev => (prev + 1) % conversations.length);
                setVisible(true);
            }, 500);
        }, 5000);
        return () => clearInterval(timer);
    }, []);

    const messages: Message[] = conversations[activeIndex] ?? conversations[0] ?? [];

    return (
        <section className="bg-white pt-10 pb-14 border-t border-gray-100">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center max-w-5xl mx-auto">

                    {/* Left: copy + CTA */}
                    <div className="lg:pl-6">
                        <h2 className="text-3xl md:text-4xl font-serif font-bold text-primary-dark mb-4">
                            Or just ask on WhatsApp
                        </h2>
                        <p className="text-base text-primary-brown max-w-md mb-8 leading-relaxed">
                            Tell our bot what you're looking for and it'll find the right vendors near you — no app download, no sign-up needed.
                        </p>
                        <a
                            href={whatsAppHref}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2.5 bg-primary-dark text-white hover:bg-primary-brown font-semibold px-5 py-3 rounded-xl transition-colors duration-200 shadow-lg shadow-primary-dark/20"
                        >
                            <svg className="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                            </svg>
                            Click here
                        </a>
                    </div>

                    {/* Right: animated WhatsApp chat */}
                    <div className="flex justify-center">
                        <div className="w-full max-w-[340px] bg-[#e5ddd5] rounded-3xl overflow-hidden shadow-xl">

                            {/* Chat header */}
                            <div className="bg-[#075E54] px-4 py-3 flex items-center gap-3">
                                <div className="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <svg className="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.526 5.845L.057 23.882l6.198-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.68.965.982-3.59-.234-.369A9.818 9.818 0 1112 21.818z"/>
                                    </svg>
                                </div>
                                <div className="text-left">
                                    <div className="text-white font-semibold text-sm">Shopping Complex Bot</div>
                                    <div className="text-green-200 text-xs">Online</div>
                                </div>

                                {/* Slide dots */}
                                <div className="ml-auto flex items-center gap-1.5">
                                    {conversations.map((_, i) => (
                                        <span
                                            key={i}
                                            className={`w-1.5 h-1.5 rounded-full transition-colors duration-300 ${i === activeIndex ? 'bg-white' : 'bg-white/30'}`}
                                        />
                                    ))}
                                </div>
                            </div>

                            {/* Messages — fade animation */}
                            <div
                                className="px-3 py-4 space-y-2.5 min-h-[220px]"
                                style={{ opacity: visible ? 1 : 0, transition: 'opacity 0.5s ease' }}
                            >
                                {messages.map((msg, i) => (
                                    <div key={i} className={`flex ${msg.from === 'user' ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`rounded-2xl px-3 py-2 max-w-[85%] shadow-sm text-xs leading-relaxed ${
                                            msg.from === 'user'
                                                ? 'bg-[#dcf8c6] rounded-tr-sm text-gray-800'
                                                : 'bg-white rounded-tl-sm text-gray-800'
                                        }`}>
                                            {msg.lines.map((line, j) =>
                                                line === '' ? <br key={j} /> : <p key={j}>{line}</p>
                                            )}
                                            <p className={`text-[10px] mt-1 text-gray-400 ${msg.from === 'user' ? 'text-right' : 'text-right'}`}>
                                                {msg.time}{msg.from === 'user' ? ' ✓✓' : ''}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Input bar */}
                            <div className="bg-[#f0f2f5] px-3 py-2.5 flex items-center gap-2">
                                <div className="flex-1 bg-white rounded-full px-4 py-2 text-xs text-gray-400 border border-gray-100">
                                    Type a message...
                                </div>
                                <div className="w-9 h-9 rounded-full bg-[#075E54] flex items-center justify-center flex-shrink-0">
                                    <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    );
};

export default WhatsAppBotSection;
