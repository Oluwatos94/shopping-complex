interface Props {
    message: string;
    type: 'success' | 'error';
}

export default function FlashBanner({ message, type }: Props) {
    return (
        <div
            className={`rounded-xl px-4 py-3 text-sm font-medium mb-6 ${
                type === 'success'
                    ? 'bg-green-50 text-green-700 border border-green-200'
                    : 'bg-red-50 text-red-700 border border-red-200'
            }`}
        >
            {message}
        </div>
    );
}
