import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Search, Download } from 'lucide-react';
import { getLatestCfocnFilteredRun } from '../authApi';
import { SkeletonTable } from '../components/Skeleton';
import EmptyState from '../components/EmptyState';

// Bill is not used in the Match/Not match decision for this pipeline and
// has no billing data of its own here, so it is not shown as a tab.
const TABS = [
    { key: 'match', label: 'Match' },
    { key: 'not_match', label: 'Not match' },
];

const PER_PAGE = 10;

function normalize(value) {
    return String(value ?? '').trim().toLowerCase();
}

function toCsv(columns, rows) {
    const escape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    const lines = [columns.map(escape).join(',')];
    rows.forEach((row) => lines.push(columns.map((col) => escape(row[col])).join(',')));
    return lines.join('\n');
}

function columnsFor(rows) {
    if (rows.length === 0) {
        return ['Work Order', 'Infrastructure'];
    }

    const keys = Object.keys(rows[0]);
    const ordered = ['Work Order', 'Infrastructure'].filter((k) => keys.includes(k));
    const rest = keys.filter((k) => !ordered.includes(k));

    return [...ordered, ...rest];
}

export default function Reconciliation() {
    const [params, setParams] = useSearchParams();
    const tab = TABS.some((t) => t.key === params.get('tab')) ? params.get('tab') : 'match';
    const search = params.get('q') || '';

    const [run, setRun] = useState(null);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [searchInput, setSearchInput] = useState(search);

    useEffect(() => {
        getLatestCfocnFilteredRun()
            .then((data) => setRun(data.run))
            .catch((err) => toast.error(err.message))
            .finally(() => setLoading(false));
    }, []);

    const allRows = useMemo(() => {
        if (!run) return [];

        return tab === 'match' ? run.match_rows : run.not_match_rows;
    }, [run, tab]);

    // Exact match on job number (Work Order), case-insensitive, trimmed.
    const filteredRows = useMemo(() => {
        if (!search) return allRows;

        const needle = normalize(search);

        return allRows.filter((row) => normalize(row['Work Order']) === needle);
    }, [allRows, search]);

    const columns = useMemo(() => columnsFor(filteredRows), [filteredRows]);

    const lastPage = Math.max(1, Math.ceil(filteredRows.length / PER_PAGE));
    const pageRows = filteredRows.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    const switchTab = (key) => {
        setPage(1);
        setParams({ tab: key });
        setSearchInput('');
    };

    const submitSearch = (e) => {
        e.preventDefault();
        setPage(1);
        setParams({ tab, q: searchInput });
    };

    const handleExport = () => {
        if (filteredRows.length === 0) return;
        // Exports every row in the current tab/search, not just the
        // current page (sheet rows may exceed the unique job-number count
        // when the builder file has duplicate job numbers — reference only).
        const csv = toCsv(columns, filteredRows);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `cfocn_${tab}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    if (!loading && !run) {
        return (
            <EmptyState
                title="No imports yet"
                message="Run the Infrastructure Filter first to see reconciliation results."
                action={
                    <Link to="/infrastructure-filter" className="text-sm font-medium text-blue-600 hover:underline">
                        Start a filtered import →
                    </Link>
                }
            />
        );
    }

    return (
        <div>
            <h1 className="mb-1 text-xl font-semibold text-slate-900">Reconciliation</h1>
            <p className="mb-4 text-sm text-slate-500">
                Browse the Match and Not match CFOCN job numbers from the latest infrastructure-filtered run.
            </p>

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-1 rounded-lg bg-slate-100 p-1">
                    {TABS.map((t) => (
                        <button
                            key={t.key}
                            type="button"
                            onClick={() => switchTab(t.key)}
                            className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors duration-200 ${
                                tab === t.key ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                <div className="flex items-center gap-2">
                    <form onSubmit={submitSearch} className="relative">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="search"
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            placeholder="Search job number…"
                            className="w-56 rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none transition-colors duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </form>
                    <button
                        type="button"
                        onClick={handleExport}
                        disabled={filteredRows.length === 0}
                        className="flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-50 disabled:opacity-40"
                    >
                        <Download size={14} />
                        Export
                    </button>
                </div>
            </div>

            {loading && <SkeletonTable cols={5} />}

            {!loading && pageRows.length === 0 && (
                <EmptyState title="No rows found" message="Try a different search or tab." />
            )}

            {!loading && pageRows.length > 0 && (
                <>
                    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-slate-500">
                                    {columns.map((col) => (
                                        <th key={col} className="whitespace-nowrap px-4 py-2 font-medium">
                                            {col}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {pageRows.map((row, i) => (
                                    <tr key={i} className="border-b border-slate-50 transition-colors duration-150 hover:bg-slate-50 last:border-0">
                                        {columns.map((col) => (
                                            <td key={col} className="whitespace-nowrap px-4 py-2 text-slate-600">
                                                {String(row[col] ?? '—')}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-3 flex items-center justify-between text-sm text-slate-500">
                        <span>
                            Page {page} of {lastPage} ({filteredRows.length} total)
                        </span>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                disabled={page <= 1}
                                onClick={() => setPage((p) => p - 1)}
                                className="rounded-lg px-3 py-1 font-medium transition-colors duration-200 hover:bg-slate-100 disabled:opacity-40"
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                disabled={page >= lastPage}
                                onClick={() => setPage((p) => p + 1)}
                                className="rounded-lg px-3 py-1 font-medium transition-colors duration-200 hover:bg-slate-100 disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
