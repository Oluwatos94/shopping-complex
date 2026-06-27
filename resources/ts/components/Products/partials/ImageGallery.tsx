import { useState, useRef, useCallback } from 'react';
import { ProductImage } from '@/types/product';

interface ImageGalleryProps {
    images: ProductImage[];
    productName: string;
}

export default function ImageGallery({ images, productName }: ImageGalleryProps) {
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [isZoomed, setIsZoomed] = useState(false);
    const [zoomPosition, setZoomPosition] = useState({ x: 0, y: 0 });
    const [isLightboxOpen, setIsLightboxOpen] = useState(false);
    const imageRef = useRef<HTMLDivElement>(null);

    // Sort images by order, with primary first
    const sortedImages = [...images].sort((a, b) => {
        if (a.is_primary) return -1;
        if (b.is_primary) return 1;
        return a.order - b.order;
    });

    const currentImage = sortedImages[selectedIndex] || {
        url: '/images/placeholder.png',
        alt_text: productName,
        type: undefined,
    };
    const currentIsVideo = currentImage.type === 'product_video';

    const handleMouseMove = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
        if (!imageRef.current) return;

        const rect = imageRef.current.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;

        setZoomPosition({ x, y });
    }, []);

    const handlePrevious = useCallback(() => {
        setSelectedIndex((prev) => (prev === 0 ? sortedImages.length - 1 : prev - 1));
    }, [sortedImages.length]);

    const handleNext = useCallback(() => {
        setSelectedIndex((prev) => (prev === sortedImages.length - 1 ? 0 : prev + 1));
    }, [sortedImages.length]);

    const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
        if (e.key === 'ArrowLeft') handlePrevious();
        if (e.key === 'ArrowRight') handleNext();
        if (e.key === 'Escape') setIsLightboxOpen(false);
    }, [handlePrevious, handleNext]);

    return (
        <div className="space-y-4">
            {/* Main Image Container */}
            <div className="relative">
                <div
                    ref={imageRef}
                    className={`relative aspect-square bg-gray-100 rounded-xl overflow-hidden group ${currentIsVideo ? 'cursor-default' : 'cursor-zoom-in'}`}
                    onMouseEnter={() => !currentIsVideo && setIsZoomed(true)}
                    onMouseLeave={() => setIsZoomed(false)}
                    onMouseMove={!currentIsVideo ? handleMouseMove : undefined}
                    onClick={() => !currentIsVideo && setIsLightboxOpen(true)}
                    role={currentIsVideo ? undefined : 'button'}
                    tabIndex={currentIsVideo ? undefined : 0}
                    onKeyDown={!currentIsVideo ? handleKeyDown : undefined}
                    aria-label={currentIsVideo ? undefined : 'Click to open fullscreen view'}
                >
                    {/* Main Media */}
                    {currentIsVideo ? (
                        <video
                            src={currentImage.url}
                            className="w-full h-full object-cover"
                            controls
                            playsInline
                            preload="metadata"
                        />
                    ) : (
                        <img
                            src={currentImage.url}
                            alt={currentImage.alt_text || productName}
                            className={`w-full h-full object-cover transition-transform duration-300 ${
                                isZoomed ? 'scale-150' : 'scale-100'
                            }`}
                            style={
                                isZoomed
                                    ? {
                                          transformOrigin: `${zoomPosition.x}% ${zoomPosition.y}%`,
                                      }
                                    : undefined
                            }
                        />
                    )}

                    {/* Zoom Hint — images only */}
                    {!currentIsVideo && (
                        <div className="absolute bottom-4 right-4 bg-black/60 text-white text-xs px-3 py-1.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                            Hover to zoom
                        </div>
                    )}

                    {/* Expand Icon — images only */}
                    {!currentIsVideo && (
                        <button
                            className="absolute top-4 right-4 bg-white/90 hover:bg-white p-2 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                            onClick={(e) => {
                                e.stopPropagation();
                                setIsLightboxOpen(true);
                            }}
                            aria-label="View fullscreen"
                        >
                            <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                    )}
                </div>

                {/* Navigation Arrows */}
                {sortedImages.length > 1 && (
                    <>
                        <button
                            onClick={handlePrevious}
                            className="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white p-2.5 rounded-full shadow-md transition-all hover:scale-110"
                            aria-label="Previous image"
                        >
                            <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            onClick={handleNext}
                            className="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white p-2.5 rounded-full shadow-md transition-all hover:scale-110"
                            aria-label="Next image"
                        >
                            <svg className="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </>
                )}

                {/* Image Counter */}
                {sortedImages.length > 1 && (
                    <div className="absolute bottom-4 left-4 bg-black/60 text-white text-sm px-3 py-1 rounded-full">
                        {selectedIndex + 1} / {sortedImages.length}
                    </div>
                )}
            </div>

            {/* Thumbnail Gallery */}
            {sortedImages.length > 1 && (
                <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                    {sortedImages.map((image, index) => (
                        <button
                            key={image.id}
                            onClick={() => setSelectedIndex(index)}
                            className={`relative flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 transition-all ${
                                index === selectedIndex
                                    ? 'border-brand-green ring-2 ring-brand-green/30'
                                    : 'border-transparent hover:border-gray-300'
                            }`}
                            aria-label={`View ${image.type === 'product_video' ? 'video' : 'image'} ${index + 1}`}
                            aria-current={index === selectedIndex ? 'true' : 'false'}
                        >
                            {image.type === 'product_video' ? (
                                <div className="w-full h-full bg-gray-800 flex items-center justify-center">
                                    <svg className="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </div>
                            ) : (
                                <img
                                    src={image.url}
                                    alt={image.alt_text || `${productName} - Image ${index + 1}`}
                                    className="w-full h-full object-cover"
                                />
                            )}
                        </button>
                    ))}
                </div>
            )}

            {/* Lightbox Modal */}
            {isLightboxOpen && (
                <div
                    className="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
                    onClick={() => setIsLightboxOpen(false)}
                    onKeyDown={handleKeyDown}
                    role="dialog"
                    aria-modal="true"
                    aria-label="Image lightbox"
                >
                    {/* Close Button */}
                    <button
                        onClick={() => setIsLightboxOpen(false)}
                        className="absolute top-4 right-4 text-white/80 hover:text-white p-2 z-10"
                        aria-label="Close lightbox"
                    >
                        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    {/* Lightbox Media */}
                    <div
                        className="relative max-w-5xl max-h-[90vh] mx-4"
                        onClick={(e) => e.stopPropagation()}
                    >
                        {currentIsVideo ? (
                            <video
                                src={currentImage.url}
                                className="max-w-full max-h-[90vh] rounded-lg"
                                controls
                                autoPlay
                                playsInline
                            />
                        ) : (
                            <img
                                src={currentImage.url}
                                alt={currentImage.alt_text || productName}
                                className="max-w-full max-h-[90vh] object-contain rounded-lg"
                            />
                        )}

                        {/* Lightbox Navigation */}
                        {sortedImages.length > 1 && (
                            <>
                                <button
                                    onClick={handlePrevious}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 p-3 rounded-full transition-colors"
                                    aria-label="Previous image"
                                >
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button
                                    onClick={handleNext}
                                    className="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 p-3 rounded-full transition-colors"
                                    aria-label="Next image"
                                >
                                    <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </>
                        )}
                    </div>

                    {/* Lightbox Thumbnails */}
                    {sortedImages.length > 1 && (
                        <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                            {sortedImages.map((image, index) => (
                                <button
                                    key={image.id}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setSelectedIndex(index);
                                    }}
                                    className={`w-16 h-16 rounded-md overflow-hidden border-2 transition-all ${
                                        index === selectedIndex
                                            ? 'border-white opacity-100'
                                            : 'border-transparent opacity-60 hover:opacity-100'
                                    }`}
                                    aria-label={`View image ${index + 1}`}
                                >
                                    <img
                                        src={image.url}
                                        alt={image.alt_text || `${productName} - Image ${index + 1}`}
                                        className="w-full h-full object-cover"
                                    />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
