export default function EmptyState({ title, message, action }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200">
            <p className="text-sm font-medium text-slate-900">{title}</p>
            {message && <p className="mt-1 text-sm text-slate-500">{message}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
