interface FormInputProps {
    label: string;
    type?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    placeholder?: string;
}

export function FormInput({
    label,
    type = "text",
    value,
    onChange,
    error,
    placeholder,
}: FormInputProps) {
    return (
        <div>
            <label className="block text-white text-lg font-serif font-medium mb-2 text-left">
                {label}
            </label>
            <input
                type={type}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className={`w-full px-4 py-3 bg-transparent border rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 transition-all ${
                    error
                        ? "border-red-400 focus:ring-red-400/50"
                        : "border-white focus:ring-white/50"
                }`}
            />
            {error && (
                <p className="text-red-400 text-sm mt-1 flex items-center gap-1">
                    <svg
                        className="w-4 h-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fillRule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clipRule="evenodd"
                        />
                    </svg>
                    {error}
                </p>
            )}
        </div>
    );
}
