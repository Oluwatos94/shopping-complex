import { SyntheticEvent, useState } from 'react';

interface Props {
    src: string;
    className?: string;
    poster?: string | null;
    controls?: boolean;
}

export default function VideoThumbnail({ src, className = 'h-full w-full object-cover', poster, controls = false }: Props) {
    const [failedSrc, setFailedSrc] = useState<string | null>(null);

    const withFrameHint = src.includes('#') ? src : `${src}#t=0.1`;

    const seekToFirstFrame = (e: SyntheticEvent<HTMLVideoElement>) => {
        const video = e.currentTarget;
        if (video.currentTime === 0 && video.duration > 0) {
            try {
                video.currentTime = Math.min(0.1, video.duration / 2);
            } catch {
                // Seeking before the range is buffered throws on some browsers.
            }
        }
    };

    if (failedSrc === src) {
        return <div className={`${className} bg-gray-200`} role="img" aria-label="Video unavailable" />;
    }

    return (
        <video
            src={withFrameHint}
            poster={poster ?? undefined}
            className={className}
            controls={controls}
            muted={!controls}
            playsInline
            disablePictureInPicture={!controls}
            preload="metadata"
            onLoadedMetadata={seekToFirstFrame}
            onError={() => setFailedSrc(src)}
        />
    );
}
