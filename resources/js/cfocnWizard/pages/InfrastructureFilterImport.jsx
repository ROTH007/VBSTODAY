import { useState } from 'react';
import toast from 'react-hot-toast';
import { CheckCircle2, AlertTriangle, FileStack, ShieldCheck, ShieldQuestion } from 'lucide-react';
import { inspectBuilderInfrastructures, runFilteredCfocnImport, importVerifiedList } from '../authApi';
import StatCard from '../components/StatCard';
import EmptyState from '../components/EmptyState';

export default function InfrastructureFilterImport() {
    const [builderFile, setBuilderFile] = useState(null);
    const [cfoFile, setCfoFile] = useState(null);
    const [infrastructures, setInfrastructures] = useState([]);
    const [selected, setSelected] = useState([]);
    const [allInfrastructures, setAllInfrastructures] = useState(false);
    const [rule, setRule] = useState('exact');
    const [inspecting, setInspecting] = useState(false);
    const [running, setRunning] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const [verifiedFile, setVerifiedFile] = useState(null);
    const [importingVerified, setImportingVerified] = useState(false);

    const handleBuilderChange = async (e) => {
        const file = e.target.files[0] ?? null;
        setBuilderFile(file);
        setInfrastructures([]);
        setSelected([]);
        setResult(null);

        if (!file) return;

        setInspecting(true);
        try {
            const data = await inspectBuilderInfrastructures(file);
            setInfrastructures(data.infrastructures);
        } catch (err) {
            toast.error(err.message);
        } finally {
            setInspecting(false);
        }
    };

    const toggleInfra = (value) => {
        setSelected((current) =>
            current.includes(value) ? current.filter((v) => v !== value) : [...current, value]
        );
    };

    const handleRun = async () => {
        setError(null);
        setRunning(true);
        try {
            const data = await runFilteredCfocnImport({
                builderFile,
                cfoFile,
                infrastructures: selected,
                allInfrastructures,
                rule,
            });
            setResult(data);
        } catch (err) {
            setError(err.message);
            toast.error(err.message);
        } finally {
            setRunning(false);
        }
    };

    const handleImportVerifiedList = async () => {
        if (!verifiedFile) return;
        setImportingVerified(true);
        try {
            const data = await importVerifiedList(verifiedFile);
            toast.success(`Imported ${data.imported} verified entr${data.imported === 1 ? 'y' : 'ies'}.`);
            setVerifiedFile(null);
        } catch (err) {
            toast.error(err.message);
        } finally {
            setImportingVerified(false);
        }
    };

    const canRun = builderFile && cfoFile && (allInfrastructures || selected.length > 0) && !running;

    return (
        <div>
            <h1 className="mb-1 text-xl font-semibold text-slate-900">Infrastructure filter</h1>
            <p className="mb-6 text-sm text-slate-500">
                Filter builder rows by infrastructure before matching. This runs separately from the main CFOCN
                wizard and never touches saved builder data.
            </p>

            <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 className="mb-3 text-sm font-semibold text-slate-900">1. Builder file</h2>
                <input
                    type="file"
                    accept=".xlsx,.xls"
                    onChange={handleBuilderChange}
                    className="block w-full text-sm text-slate-600"
                />
                {inspecting && <p className="mt-2 text-sm text-slate-400">Reading infrastructure values…</p>}

                {infrastructures.length > 0 && (
                    <div className="mt-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-700">Infrastructures</span>
                            <label className="flex items-center gap-2 text-sm text-slate-600">
                                <input
                                    type="checkbox"
                                    checked={allInfrastructures}
                                    onChange={(e) => setAllInfrastructures(e.target.checked)}
                                />
                                All infrastructures
                            </label>
                        </div>
                        <div className={`flex flex-wrap gap-2 ${allInfrastructures ? 'opacity-40' : ''}`}>
                            {infrastructures.map((value) => (
                                <label
                                    key={value}
                                    className="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600"
                                >
                                    <input
                                        type="checkbox"
                                        disabled={allInfrastructures}
                                        checked={selected.includes(value)}
                                        onChange={() => toggleInfra(value)}
                                    />
                                    {value}
                                </label>
                            ))}
                        </div>

                        <div className="mt-4 flex items-center gap-4">
                            <span className="text-sm font-medium text-slate-700">Rule</span>
                            <label className="flex items-center gap-1.5 text-sm text-slate-600">
                                <input
                                    type="radio"
                                    name="rule"
                                    checked={rule === 'exact'}
                                    onChange={() => setRule('exact')}
                                />
                                Exact (default)
                            </label>
                            <label className="flex items-center gap-1.5 text-sm text-slate-600">
                                <input
                                    type="radio"
                                    name="rule"
                                    checked={rule === 'contains'}
                                    onChange={() => setRule('contains')}
                                />
                                Contains
                            </label>
                        </div>
                    </div>
                )}
            </div>

            <div className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 className="mb-3 text-sm font-semibold text-slate-900">2. Company (CFOCN) file</h2>
                <input
                    type="file"
                    accept=".xlsx,.xls"
                    onChange={(e) => setCfoFile(e.target.files[0] ?? null)}
                    className="block w-full text-sm text-slate-600"
                />
            </div>

            {error && (
                <div className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">{error}</div>
            )}

            <button
                type="button"
                onClick={handleRun}
                disabled={!canRun}
                className="mb-6 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-40"
            >
                {running ? 'Running…' : 'Run filtered match'}
            </button>

            {result && (
                <div className="mb-6">
                    <h2 className="mb-3 text-sm font-semibold text-slate-900">Summary</h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            label="Total CFOCN work orders (unique)"
                            value={result.summary.total_cfocn_work_orders.toLocaleString()}
                            icon={FileStack}
                        />
                        <StatCard
                            label="Matched"
                            value={result.summary.matched.toLocaleString()}
                            icon={CheckCircle2}
                            variant="green"
                        />
                        <StatCard
                            label="Not match"
                            value={result.summary.not_matched.toLocaleString()}
                            icon={AlertTriangle}
                            variant="orange"
                        />
                        <StatCard
                            label="Missing/invalid infrastructure"
                            value={result.summary.missing_or_invalid_infrastructure_count.toLocaleString()}
                            icon={AlertTriangle}
                        />
                        <StatCard
                            label="Infrastructure unverified"
                            value={result.summary.infrastructure_unverified.toLocaleString()}
                            icon={ShieldQuestion}
                            variant="orange"
                        />
                    </div>

                    <p className="mt-3 text-xs text-slate-400">
                        Check: Matched + Not match = Total CFOCN —{' '}
                        {result.summary.matched_plus_not_matched_equals_total ? 'OK' : 'MISMATCH'}.
                        {' '}Not match sheet has {result.summary.not_match_sheet_row_count.toLocaleString()} row(s)
                        (reference only; {result.summary.not_matched.toLocaleString()} unique job numbers).
                    </p>

                    <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 className="mb-2 text-sm font-semibold text-slate-900">By infrastructure</h3>
                        <ul className="space-y-1 text-sm text-slate-600">
                            {Object.entries(result.summary.counts_by_infrastructure).map(([name, count]) => (
                                <li key={name} className="flex justify-between border-b border-slate-50 py-1 last:border-0">
                                    <span>{name}</span>
                                    <span className="font-medium text-slate-900">{count.toLocaleString()}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            {!result && (
                <EmptyState
                    title="No results yet"
                    message="Upload a builder file, choose infrastructures and a rule, then run the match."
                />
            )}

            <div className="mt-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="mb-2 flex items-center gap-2">
                    <ShieldCheck size={16} className="text-blue-600" />
                    <h2 className="text-sm font-semibold text-slate-900">Import verified infrastructure list</h2>
                </div>
                <p className="mb-3 text-sm text-slate-500">
                    An .xlsx with "Work Order" and "Verified Infrastructure" columns. Only entries on this list can
                    ever be marked Verified.
                </p>
                <div className="flex items-center gap-3">
                    <input
                        type="file"
                        accept=".xlsx,.xls"
                        onChange={(e) => setVerifiedFile(e.target.files[0] ?? null)}
                        className="block w-full text-sm text-slate-600"
                    />
                    <button
                        type="button"
                        onClick={handleImportVerifiedList}
                        disabled={!verifiedFile || importingVerified}
                        className="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-50 disabled:opacity-40"
                    >
                        {importingVerified ? 'Importing…' : 'Import'}
                    </button>
                </div>
            </div>
        </div>
    );
}
