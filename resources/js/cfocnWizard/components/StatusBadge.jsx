import { AlertTriangle, Info, CheckCircle2 } from 'lucide-react';

const VARIANTS = {
    unmatched: { cls: 'bg-orange-50 text-orange-700', icon: AlertTriangle, label: 'Unmatched' },
    review: { cls: 'bg-blue-50 text-blue-700', icon: Info, label: 'Review needed' },
    completed: { cls: 'bg-green-50 text-green-700', icon: CheckCircle2, label: 'Completed' },
};

export default function StatusBadge({ variant = 'unmatched', label }) {
    const v = VARIANTS[variant] || VARIANTS.unmatched;
    const Icon = v.icon;

    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${v.cls}`}>
            <Icon size={12} />
            {label || v.label}
        </span>
    );
}
