import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../api';
import CountsSummaryCards from '../components/CountsSummaryCards';
import CountsChart from '../components/CountsChart';
import RowsTable from '../components/RowsTable';
import DownloadButton from '../components/DownloadButton';

const TABS = [
    { key: 'match', label: 'Pay (Match)' },
    { key: 'not_match', label: 'Other (Not match)' },
    { key: 'bill', label: 'Check (Bill)' },
];

export default function Results() {
    const { id } = useParams();
    const [importRecord, setImportRecord] = useState(null);
    const [tab, setTab] = useState('match');

    useEffect(() => {
        api.get(`/imports/${id}`).then(({ data }) => setImportRecord(data));
    }, [id]);

    if (!importRecord) {
        return <p>Loading…</p>;
    }

    return (
        <div>
            <div className="page-header">
                <h1>Import #{importRecord.id}</h1>
                <DownloadButton importId={importRecord.id} />
            </div>

            <p>
                Company: <strong>{importRecord.company}</strong> &middot; Status filter:{' '}
                <strong>{importRecord.status_filter || '—'}</strong> &middot; Status:{' '}
                <span className={`status-badge status-${importRecord.status}`}>{importRecord.status}</span>
            </p>

            {importRecord.error_message && <div className="alert alert-error">{importRecord.error_message}</div>}

            <CountsSummaryCards counts={importRecord.counts} />
            <CountsChart counts={importRecord.counts} />

            <div className="tabs">
                {TABS.map((t) => (
                    <button
                        key={t.key}
                        className={tab === t.key ? 'tab-active' : ''}
                        onClick={() => setTab(t.key)}
                    >
                        {t.label} ({importRecord.counts[t.key]})
                    </button>
                ))}
            </div>

            <RowsTable importId={importRecord.id} type={tab} />
        </div>
    );
}
