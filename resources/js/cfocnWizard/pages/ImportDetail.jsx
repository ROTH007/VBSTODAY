import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { getSavedImport, downloadSavedImport, updateSavedImportLabel } from '../authApi';
import { SkeletonCards } from '../components/Skeleton';
import EmptyState from '../components/EmptyState';
import EditLabelModal from '../components/EditLabelModal';

export default function ImportDetail() {
    const { id } = useParams();
    const [savedImport, setSavedImport] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notFound, setNotFound] = useState(false);
    const [editing, setEditing] = useState(false);
    const [saving, setSaving] = useState(false);
    const [downloading, setDownloading] = useState(false);

    const load = () => {
        setLoading(true);
        getSavedImport(id)
            .then(setSavedImport)
            .catch((err) => {
                if (err.status === 404) {
                    setNotFound(true);
                } else {
                    toast.error(err.message);
                }
            })
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    const handleDownload = async () => {
        setDownloading(true);
        try {
            await downloadSavedImport(id, `CFOCN_${savedImport.status || 'result'}_List.xlsx`);
        } catch (err) {
            toast.error(err.message);
        } finally {
            setDownloading(false);
        }
    };

    const handleSaveLabel = async (label) => {
        setSaving(true);
        try {
            const updated = await updateSavedImportLabel(id, label);
            setSavedImport(updated);
            toast.success('Label updated.');
            setEditing(false);
        } catch (err) {
            toast.error(err.message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <SkeletonCards />;
    }

    if (notFound) {
        return (
            <EmptyState
                title="Import not found"
                message="It may have been deleted."
                action={
                    <Link to="/history" className="text-sm font-medium text-blue-600 hover:underline">
                        Back to import history
                    </Link>
                }
            />
        );
    }

    return (
        <div>
            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        {savedImport.label || `Import #${savedImport.id}`}
                    </h1>
                    <p className="text-sm text-slate-500">
                        {savedImport.company.toUpperCase()} · {savedImport.status || 'no status'} ·{' '}
                        {new Date(savedImport.created_at).toLocaleString()}
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setEditing(true)}
                        className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100"
                    >
                        Edit label
                    </button>
                    <button
                        type="button"
                        onClick={handleDownload}
                        disabled={downloading}
                        className="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-60"
                    >
                        {downloading ? 'Downloading…' : 'Download workbook'}
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label="Bill" value={savedImport.counts.bill} color="text-blue-600" />
                <StatCard label="Match" value={savedImport.counts.match} color="text-green-600" />
                <StatCard label="Not match" value={savedImport.counts.not_match} color="text-orange-600" />
            </div>

            <EditLabelModal
                open={editing}
                onClose={() => setEditing(false)}
                initialLabel={savedImport.label}
                onSave={handleSaveLabel}
                saving={saving}
            />
        </div>
    );
}

function StatCard({ label, value, color }) {
    return (
        <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div className={`text-2xl font-bold ${color}`}>{value.toLocaleString()}</div>
            <div className="text-sm text-slate-500">{label}</div>
        </div>
    );
}
