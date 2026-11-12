import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Calendar, FileStack, CheckCircle2, AlertTriangle, ArrowRight } from 'lucide-react';
import { getLatestCfocnFilteredRun } from '../authApi';
import { SkeletonCards, SkeletonTable } from '../components/Skeleton';
import EmptyState from '../components/EmptyState';
import StatCard from '../components/StatCard';
import StatusBadge from '../components/StatusBadge';
import CountsChart from '../components/CountsChart';

export default function Overview() {
    const navigate = useNavigate();
    const [run, setRun] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getLatestCfocnFilteredRun()
            .then((data) => setRun(data.run))
            .catch((err) => toast.error(err.message))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div>
                <SkeletonCards count={4} />
            </div>
        );
    }

    if (!run) {
        return (
            <EmptyState
                title="No imports yet"
                message="Run an infrastructure-filtered CFOCN import to see results here."
                action={
                    <Link to="/infrastructure-filter" className="text-sm font-medium text-blue-600 hover:underline">
                        Start a filtered import →
                    </Link>
                }
            />
        );
    }

    const { total_cfocn_work_orders: total, matched, not_matched: notMatched, matched_plus_not_matched_equals_total: checksOut } = run.summary;
    const attentionRows = run.not_match_rows.slice(0, 4);

    const goToRow = (row) => {
        navigate(`/reconciliation?tab=not_match&q=${encodeURIComponent(row['Work Order'] ?? '')}`);
    };

    return (
        <div>
            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">Import overview</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        CFOCN work orders from your latest infrastructure-filtered run.
                    </p>
                </div>
                <Link
                    to="/infrastructure-filter"
                    className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700"
                >
                    + New import
                </Link>
            </div>

            <div className="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <Calendar size={16} className="text-slate-400" />
                <span className="text-sm text-slate-600">
                    Latest run: {new Date(run.created_at).toLocaleString()}
                </span>
                <StatusBadge variant="completed" />
            </div>

            <div className="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total CFOCN work orders (unique)"
                    value={total.toLocaleString()}
                    icon={FileStack}
                    variant="default"
                />
                <StatCard label="Matched" value={matched.toLocaleString()} icon={CheckCircle2} variant="green" />
                <StatCard label="Not matched" value={notMatched.toLocaleString()} icon={AlertTriangle} variant="orange" />
                <StatCard
                    label="Check"
                    value={checksOut ? 'OK' : 'MISMATCH'}
                    sublabel="Matched + Not match = Total"
                    icon={CheckCircle2}
                    variant={checksOut ? 'blue' : 'orange'}
                />
            </div>

            <div className="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 className="mb-3 text-sm font-semibold text-slate-900">Reconciliation results</h2>
                <CountsChart counts={{ matched, notMatched }} />
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="mb-3 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <AlertTriangle size={16} className="text-orange-500" />
                        <h2 className="text-sm font-semibold text-slate-900">Needs attention</h2>
                    </div>
                    <Link
                        to="/reconciliation?tab=not_match"
                        className="flex items-center gap-1 text-sm font-medium text-blue-600 transition-colors duration-200 hover:underline"
                    >
                        View all unmatched <ArrowRight size={14} />
                    </Link>
                </div>

                {attentionRows.length === 0 && (
                    <p className="py-6 text-center text-sm text-slate-400">No unmatched rows in the latest run.</p>
                )}

                {attentionRows.length > 0 && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-slate-500">
                                    <th className="px-3 py-2 font-medium">Work order</th>
                                    <th className="px-3 py-2 font-medium">Infrastructure</th>
                                    <th className="px-3 py-2 font-medium">Status</th>
                                    <th className="px-3 py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {attentionRows.map((row, i) => (
                                    <tr
                                        key={i}
                                        onClick={() => goToRow(row)}
                                        className="cursor-pointer border-b border-slate-50 transition-colors duration-150 hover:bg-slate-50 last:border-0"
                                    >
                                        <td className="px-3 py-2.5 font-medium text-slate-700">
                                            {row['Work Order'] ?? '—'}
                                        </td>
                                        <td className="px-3 py-2.5 text-slate-500">{row.Infrastructure ?? 'CFOCN'}</td>
                                        <td className="px-3 py-2.5">
                                            <StatusBadge variant="unmatched" />
                                        </td>
                                        <td className="px-3 py-2.5 text-right text-slate-300">
                                            <ArrowRight size={14} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
