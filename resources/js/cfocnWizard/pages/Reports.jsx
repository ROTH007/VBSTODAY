import { BarChart3 } from 'lucide-react';

export default function Reports() {
    return (
        <div>
            <h1 className="mb-1 text-xl font-semibold text-slate-900">Reports</h1>
            <p className="mb-6 text-sm text-slate-500">Scheduled and exportable reports.</p>

            <div className="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <span className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <BarChart3 size={20} />
                </span>
                <p className="text-sm font-medium text-slate-900">Coming soon</p>
                <p className="mt-1 text-sm text-slate-500">Custom reports will appear here.</p>
            </div>
        </div>
    );
}
