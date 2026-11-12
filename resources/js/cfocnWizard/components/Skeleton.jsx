export function SkeletonLine({ className = '' }) {
    return <div className={`animate-pulse rounded bg-slate-200 ${className}`} />;
}

export function SkeletonCards({ count = 3 }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <SkeletonLine className="mb-2 h-7 w-16" />
                    <SkeletonLine className="h-4 w-20" />
                </div>
            ))}
        </div>
    );
}

export function SkeletonTable({ rows = 5, cols = 4 }) {
    return (
        <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            {Array.from({ length: rows }).map((_, r) => (
                <div key={r} className="flex gap-4 border-b border-slate-100 px-4 py-3 last:border-0">
                    {Array.from({ length: cols }).map((_, c) => (
                        <SkeletonLine key={c} className="h-4 flex-1" />
                    ))}
                </div>
            ))}
        </div>
    );
}
