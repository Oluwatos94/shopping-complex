interface Step {
    number: number;
    label: string;
}

interface StepIndicatorProps {
    currentStep: number;
    steps: Step[];
    onStepClick?: (step: number) => void;
}

export default function StepIndicator({ currentStep, steps, onStepClick }: StepIndicatorProps) {
    return (
        <div className="w-full py-6">
            <div className="flex items-center justify-between relative">
                {/* Progress Line */}
                <div className="absolute top-5 left-0 right-0 h-0.5 bg-gray-200 -z-10" />
                <div
                    className="absolute top-5 left-0 h-0.5 bg-primary-olive transition-all duration-500 -z-10"
                    style={{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }}
                />

                {steps.map((step) => {
                    const isCompleted = step.number < currentStep;
                    const isCurrent = step.number === currentStep;
                    const isClickable = onStepClick && step.number <= currentStep;

                    return (
                        <div
                            key={step.number}
                            className="flex flex-col items-center"
                        >
                            <button
                                type="button"
                                onClick={() => isClickable && onStepClick(step.number)}
                                disabled={!isClickable}
                                className={`
                                    w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold
                                    transition-all duration-300
                                    ${isCompleted
                                        ? 'bg-primary-olive text-white'
                                        : isCurrent
                                            ? 'bg-primary-olive text-white ring-4 ring-primary-olive/20'
                                            : 'bg-gray-200 text-gray-500'
                                    }
                                    ${isClickable ? 'cursor-pointer hover:ring-4 hover:ring-primary-olive/20' : 'cursor-default'}
                                `}
                            >
                                {isCompleted ? (
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                ) : (
                                    step.number
                                )}
                            </button>
                            <span
                                className={`
                                    mt-2 text-sm font-medium whitespace-nowrap
                                    ${isCurrent ? 'text-primary-olive' : isCompleted ? 'text-gray-700' : 'text-gray-400'}
                                `}
                            >
                                {step.label}
                            </span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
