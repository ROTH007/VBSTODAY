import { useEffect, useState } from 'react';
import api from '../api';

export default function RowsTable({ importId, type }) {
    const [rows, setRows] = useState([]);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState({ total: 0, per_page: 25 });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);
        api.get(`/imports/${importId}/rows`, { params: { type, page } })
            .then(({ data }) => {
                setRows(data.data);
                setMeta(data.meta);
            })
            .finally(() => setLoading(false));
    }, [importId, type, page]);

    if (loading) {
        return <p>Loading…</p>;
    }

    if (rows.length === 0) {
        return <p className="empty-state">No rows.</p>;
    }

    const columns = Object.keys(rows[0]);
    const lastPage = Math.max(1, Math.ceil(meta.total / meta.per_page));

    return (
        <div>
            <table className="data-table">
                <thead>
                    <tr>
                        {columns.map((col) => (
                            <th key={col}>{col}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, i) => (
                        <tr key={i}>
                            {columns.map((col) => (
                                <td key={col}>{String(row[col] ?? '')}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            <div className="pagination">
                <button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                    Previous
                </button>
                <span>
                    Page {page} of {lastPage} ({meta.total} rows)
                </span>
                <button disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)}>
                    Next
                </button>
            </div>
        </div>
    );
}
