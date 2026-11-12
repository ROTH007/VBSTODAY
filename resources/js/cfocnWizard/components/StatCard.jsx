const VARIANTS = {
    default: { card: 'bg-white', icon: 'bg-blue-50 text-blue-600' },
    green: { card: 'bg-green-50', icon: 'bg-green-100 text-green-700' },
    orange: { card: 'bg-orange-50', icon: 'bg-orange-100 text-orange-700' },
    blue: { card: 'bg-blue-50', icon: 'bg-blue-100 text-blue-700' },
};

export default function StatCard({ label, value, sublabel, icon: Icon, variant = 'default' }) {
    const styles = VARIANTS[variant] || VARIANTS.default;

    return (
        <div
            className={`rounded-xl border border-slate-200 p-4 shadow-sm transition-shadow duration-200 hover:shadow-md ${styles.card}`}
        >
            <div className="flex items-start justify-between">
                <div>
                    <div className="text-2xl font-bold text-slate-900">{value}</div>
                    <div className="mt-1 text-sm font-medium text-slate-600">{label}</div>
                    {sublabel && <div className="text-xs text-slate-400">{sublabel}</div>}
                </div>
                {Icon && (
                    <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${styles.icon}`}>
                        <Icon size={18} />
                    </span>
                )}
            </div>
        </div>
    );
}
