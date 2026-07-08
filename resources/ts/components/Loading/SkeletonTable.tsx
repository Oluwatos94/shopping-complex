import Skeleton from './Skeleton';

interface SkeletonTableProps {
    rows?: number;
    cols?: number;
}

export default function SkeletonTable({ rows = 5, cols = 4 }: SkeletonTableProps) {
    return (
        <table className="w-full text-left">
            <tbody className="divide-y divide-gray-50">
                {Array.from({ length: rows }).map((_, r) => (
                    <tr key={r}>
                        {Array.from({ length: cols }).map((_, c) => (
                            <td key={c} className="px-6 py-4">
                                <Skeleton className={`h-4 ${c === 0 ? 'w-3/4' : 'w-1/2'}`} />
                            </td>
                        ))}
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
