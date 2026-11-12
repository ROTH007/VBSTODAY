import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Search } from 'lucide-react';
import { listSavedImports, updateSavedImportLabel, deleteSavedImport } from '../authApi';
import { SkeletonTable } from '../components/Skeleton';
import EmptyState from '../components/EmptyState';
import EditLabelModal from '../components/EditLabelModal';
import ConfirmDialog from '../components/ConfirmDialog';

const COLUMNS = [
    { key: 'id', label: 'ID' },
    { key: 'label', label: 'Label' },
    { key: 'company', label: 'Company' },
    { key: 'match', label: 'Match' },
    { key: 'not_match', label: 'Not match' },
    { key: 'bill', label: 'Bill' },
    { key: 'created_at', label: 'Created' },
];

function cellValue(row, key) {
    if (key === 'match' || key === 'not_match' || key === 'bill') {
        return row.counts[key];
    }
    return row[key];
}

export default function ImportHistory() {
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState({ total: 0, per_page: 10 });
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [sort, setSort] = useState({ key: 'id', dir: 'desc' });
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [saving, setSaving] = useState(false);

    const load = () => {
        setLoading(true);
        listSavedImports({ page, perPage: 10, search })
            .then((data) => {
                setRows(data.data);
                setMeta(data.meta);
            })
            .catch((err) => toast.error(err.message))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [page, search]);

    const sortedRows = useMemo(() => {
        const copy = [...rows];
        copy.sort((a, b) => {
            const av = cellValue(a, sort.key) ?? '';
            const bv = cellValue(b, sort.key) ?? '';
            const cmp = typeof av === 'number' ? av - bv : String(av).localeCompare(String(bv));
            return sort.dir === 'asc' ? cmp : -cmp;
        });
        return copy;
    }, [rows, sort]);

    const toggleSort = (key) => {
        setSort((s) => (s.key === key ? { key, dir: s.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'asc' }));
    };

    const handleSaveLabel = async (label) => {
        setSaving(true);
        try {
            await updateSavedImportLabel(editing.id, label);
            toast.success('Label updated.');
            setEditing(null);
            load();
        } catch (err) {
            toast.error(err.message);
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        setSaving(true);
        try {
            await deleteSavedImport(deleting.id);
            toast.success('Import deleted.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(err.message);
        } finally {
            setSaving(false);
        }
    };

    const lastPage = Math.max(1, Math.ceil(meta.total / meta.per_page));

    return (
        <div>
            <h1 className="mb-1 text-xl font-semibold text-slate-900">Import history</h1>
            <p className="mb-4 text-sm text-slate-500">Every saved CFOCN run, newest first.</p>

            <div className="relative mb-4 max-w-sm">
                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    value={search}
                    onChange={(e) => {
                        setPage(1);
                        setSearch(e.target.value);
                    }}
                    placeholder="Search by label or company…"
                    className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none transition-colors duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </div>

            {loading && <SkeletonTable />}

            {!loading && rows.length === 0 && (
                <EmptyState title="No imports found" message="Try a different search, or run a new import." />
            )}

            {!loading && rows.length > 0 && (
                <>
                    <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-left text-slate-500">
                                    {COLUMNS.map((col) => (
                                        <th
                                            key={col.key}
                                            onClick={() => toggleSort(col.key)}
                                            className="cursor-pointer select-none px-4 py-2 font-medium transition-colors duration-200 hover:text-slate-700"
                                        >
                                            {col.label} {sort.key === col.key ? (sort.dir === 'asc' ? '↑' : '↓') : ''}
                                        </th>
                                    ))}
                                    <th className="px-4 py-2" />
                                </tr>
                            </thead>
                            <tbody>
                                {sortedRows.map((row) => (
                                    <tr key={row.id} className="border-b border-slate-50 transition-colors duration-150 hover:bg-slate-50 last:border-0">
                                        <td className="px-4 py-2">
                                            <Link to={`/imports/${row.id}`} className="font-medium text-blue-600 hover:underline">
                                                #{row.id}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-2 text-slate-600">{row.label || '—'}</td>
                                        <td className="px-4 py-2 text-slate-600">{row.company}</td>
                                        <td className="px-4 py-2 text-slate-600">{row.counts.match.toLocaleString()}</td>
                                        <td className="px-4 py-2 text-slate-600">{row.counts.not_match.toLocaleString()}</td>
                                        <td className="px-4 py-2 text-slate-600">{row.counts.bill.toLocaleString()}</td>
                                        <td className="px-4 py-2 text-slate-500">{new Date(row.created_at).toLocaleDateString()}</td>
                                        <td className="px-4 py-2 text-right">
                                            <button
                                                type="button"
                                                onClick={() => setEditing(row)}
                                                className="mr-2 rounded-lg px-2 py-1 text-xs font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100"
                                            >
                                                Edit label
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleting(row)}
                                                className="rounded-lg px-2 py-1 text-xs font-medium text-red-600 transition-colors duration-200 hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-3 flex items-center justify-between text-sm text-slate-500">
                        <span>
                            Page {meta.page} of {lastPage} ({meta.total} total)
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

            <EditLabelModal
                open={!!editing}
                onClose={() => setEditing(null)}
                initialLabel={editing?.label}
                onSave={handleSaveLabel}
                saving={saving}
            />

            <ConfirmDialog
                open={!!deleting}
                onClose={() => setDeleting(null)}
                onConfirm={handleDelete}
                title="Delete import?"
                message={`This will remove "${deleting?.label || `Import #${deleting?.id}`}" from the list. This can't be undone from the UI.`}
                confirming={saving}
            />
        </div>
    );
}
