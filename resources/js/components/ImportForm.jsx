import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api';

const COMPANIES = [
    { value: 'tct', label: 'TCT FTTH' },
    { value: 'telecom', label: 'Telecom Cambodia' },
    { value: 'adi', label: 'ADI' },
    { value: 'cfocn', label: 'CFOCN' },
];

export default function ImportForm() {
    const [builder, setBuilder] = useState(null);
    const [cfo, setCfo] = useState(null);
    const [company, setCompany] = useState('cfocn');
    const [status, setStatus] = useState('Active');
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!builder || !cfo) {
            setError('Both files are required.');
            return;
        }

        setSubmitting(true);
        setError(null);

        const formData = new FormData();
        formData.append('builder', builder);
        formData.append('cfo', cfo);
        formData.append('company', company);
        formData.append('status', status);

        try {
            const { data } = await api.post('/imports', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            navigate(`/imports/${data.id}`);
        } catch (err) {
            setError(err.response?.data?.message || 'Upload failed.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="import-form">
            {error && <div className="alert alert-error">{error}</div>}

            <label>
                Today order file (Builder)
                <input type="file" required onChange={(e) => setBuilder(e.target.files[0])} />
            </label>

            <label>
                Infrastructure type file (Company)
                <input type="file" required onChange={(e) => setCfo(e.target.files[0])} />
            </label>

            <label>
                Company
                <select value={company} onChange={(e) => setCompany(e.target.value)}>
                    {COMPANIES.map((c) => (
                        <option key={c.value} value={c.value}>
                            {c.label}
                        </option>
                    ))}
                </select>
            </label>

            <label>
                Status
                <select value={status} onChange={(e) => setStatus(e.target.value)}>
                    <option value="Active">Active</option>
                    <option value="Terminated">Terminated</option>
                </select>
            </label>

            <button type="submit" disabled={submitting}>
                {submitting ? 'Processing…' : 'Submit file'}
            </button>
        </form>
    );
}
