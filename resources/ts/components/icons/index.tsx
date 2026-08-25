import { IconProps } from '@/types';

/**
 * Heroicons-style outline set. Every icon is decorative — callers pair it with
 * a text label or an aria-label — so the svg is hidden from assistive tech.
 */
function StrokeIcon({ d, className = 'w-5 h-5', strokeWidth = 1.75 }: IconProps & { d: string[] }) {
    return (
        <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            {d.map((path) => (
                <path key={path} strokeLinecap="round" strokeLinejoin="round" strokeWidth={strokeWidth} d={path} />
            ))}
        </svg>
    );
}

export const GridIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z']} />
);

export const BagIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z']} />
);

export const CubeIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4']} />
);

export const CardIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z']} />
);

export const BarsIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z']} />
);

export const TrophyIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M8 21h8m-4-4v4m6.5-17H21v2a4 4 0 01-4 4m-10-6H3v2a4 4 0 004 4m10-8v6a5 5 0 01-10 0V3h10z']} />
);

export const CogIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z']} />
);

export const UserIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z']} />
);

export const UsersIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z']} />
);

export const LinkIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m4.5-4.5l1.5-1.5a4 4 0 015.656 5.656l-3 3']} />
);

export const ClipboardIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m2 4h2a2 2 0 012 2v3']} />
);

export const ShareIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M8.684 13.342a3 3 0 100-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684zm0-12.632a3 3 0 105.368-2.684 3 3 0 00-5.368 2.684z']} />
);

export const EyeIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z']} />
);

export const SparkleIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z']} />
);

export const BoltIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M13 10V3L4 14h7v7l9-11h-7z']} />
);

export const SignOutIcon = (props: IconProps) => (
    <StrokeIcon {...props} d={['M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1']} />
);

export const SearchIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z']} />
);

export const AlertIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z']} />
);

export const CheckIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M5 13l4 4L19 7']} />
);

export const ChevronLeftIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M15 19l-7-7 7-7']} />
);

export const ChevronDownIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M19 9l-7 7-7-7']} />
);

export const MenuIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M4 6h16M4 12h16M4 18h16']} />
);

export const CloseIcon = ({ strokeWidth = 2, ...props }: IconProps) => (
    <StrokeIcon {...props} strokeWidth={strokeWidth} d={['M6 18L18 6M6 6l12 12']} />
);

export const WhatsAppIcon = ({ className = 'w-5 h-5' }: IconProps) => (
    <svg className={className} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
    </svg>
);
