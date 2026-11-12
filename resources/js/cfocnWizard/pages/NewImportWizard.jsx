import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { submitCfocnImport } from '../api';
import { listSavedImports } from '../authApi';

const STEPS = ['Builder file', 'Company file', 'Submit'];

export default function NewImportWizard() {
    const [step, setStep] = useState(0);
    const [builderFile, setBuilderFile] = useState(null);
    const [cfoFile, setCfoFile] = useState(null);
    const [status, setStatus] = useState('active');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const navigate = useNavigate();

    const canGoNext = (step === 0 && builderFile) || (step === 1 && cfoFile);
    const progress = ((step + 1) / STEPS.length) * 100;

    const handleSubmit = async () => {
        setSubmitting(true);
        setError(null);

        try {
            await submitCfocnImport({ builderFile, cfoFile, status });
            toast.success('Import completed.');

            // The server auto-saves the run; look up its new record so we
            // can jump straight to its detail page.
            const latest = await listSavedImports({ page: 1, perPage: 1 });
            const savedImport = latest.data[0];
            navigate(savedImport ? `/imports/${savedImport.id}` : '/history');
        } catch (err) {
            setError(err.message);
            toast.error(err.message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div>
            <h1 className="mb-4 text-xl font-semibold text-slate-900">New CFOCN import</h1>

            <div className="mb-6 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                <div
                    className="h-full rounded-full bg-blue-600 transition-all duration-300"
                    style={{ width: `${progress}%` }}
                />
            </div>

            <ol className="mb-6 flex flex-wrap gap-2">
                {STEPS.map((label, i) => (
                    <li
                        key={label}
                        className={`rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200 ${
                            i === step
                                ? 'bg-blue-600 text-white'
                                : i < step
                                  ? 'bg-green-100 text-green-700'
                                  : 'bg-slate-100 text-slate-500'
                        }`}
                    >
                        {label}
                    </li>
                ))}
            </ol>

            {error && (
                <div className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100">{error}</div>
            )}

            <div className="mb-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                {step === 0 && (
                    <div>
                        <h2 className="mb-1 text-base font-medium text-slate-900">Step 1 — Builder file</h2>
                        <p className="mb-3 text-sm text-slate-500">Select the "today order" Builder file (CFOCN).</p>
                        <input
                            type="file"
                            accept=".xlsx,.xls"
                            onChange={(e) => setBuilderFile(e.target.files[0] ?? null)}
                            className="block w-full text-sm text-slate-600"
                        />
                        {builderFile && (
                            <p className="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                                {builderFile.name}
                            </p>
                        )}
                    </div>
                )}

                {step === 1 && (
                    <div>
                        <h2 className="mb-1 text-base font-medium text-slate-900">Step 2 — Company file</h2>
                        <label className="mb-3 block text-sm font-medium text-slate-700">
                            Company
                            <select
                                value="cfocn"
                                disabled
                                className="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600"
                            >
                                <option value="cfocn">CFOCN</option>
                            </select>
                        </label>
                        <p className="mb-3 text-sm text-slate-500">Select the CFOCN company file.</p>
                        <input
                            type="file"
                            accept=".xlsx,.xls"
                            onChange={(e) => setCfoFile(e.target.files[0] ?? null)}
                            className="block w-full text-sm text-slate-600"
                        />
                        {cfoFile && (
                            <p className="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                                {cfoFile.name}
                            </p>
                        )}
                        <label className="mt-4 block text-sm font-medium text-slate-700">
                            Status
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            >
                                <option value="active">Active</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </label>
                    </div>
                )}

                {step === 2 && (
                    <div>
                        <h2 className="mb-3 text-base font-medium text-slate-900">Step 3 — Submit</h2>
                        <ul className="mb-4 space-y-1 text-sm text-slate-600">
                            <li>Builder file: {builderFile?.name}</li>
                            <li>Company file: {cfoFile?.name}</li>
                            <li>Company: CFOCN</li>
                            <li>Status: {status}</li>
                        </ul>
                        <button
                            type="button"
                            onClick={handleSubmit}
                            disabled={submitting}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-60"
                        >
                            {submitting ? 'Submitting…' : 'Submit'}
                        </button>
                    </div>
                )}
            </div>

            <div className="flex justify-between">
                <button
                    type="button"
                    onClick={() => setStep((s) => s - 1)}
                    disabled={step === 0 || submitting}
                    className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100 disabled:opacity-40"
                >
                    Back
                </button>
                {step < 2 && (
                    <button
                        type="button"
                        onClick={() => setStep((s) => s + 1)}
                        disabled={!canGoNext}
                        className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-40"
                    >
                        Next
                    </button>
                )}
            </div>
        </div>
    );
}
