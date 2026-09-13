import { SyntheticEvent, useState } from 'react';

interface Props {
    src: string;
    className?: string;
    poster?: string | null;
    controls?: boolean;
}

export default function VideoThumbnail({ src, className = 'h-full w-full object-cover', poster, controls = false }: Props) {
    const [failed, setFailed] = useState(false);

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

    if (failed) {
        return <div className={`${className} bg-gray-200`} aria-hidden="true" />;
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
            onError={() => setFailed(true)}
        />
    );
}
