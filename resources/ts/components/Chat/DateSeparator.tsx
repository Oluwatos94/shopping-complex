interface Props {
    date: string;
}

export default function DateSeparator({ date }: Props) {
    return (
        <div className="flex items-center justify-center my-4">
            <span className="text-xs text-gray-400 font-medium bg-white px-4 py-1 rounded-full shadow-sm">
                {date}
            </span>
        </div>
    );
}
