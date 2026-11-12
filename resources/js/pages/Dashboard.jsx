import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';
import ImportsTable from '../components/ImportsTable';

export default function Dashboard() {
    const [imports, setImports] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        api.get('/imports').then(({ data }) => {
            setImports(data.data);
            setLoading(false);
        });
    }, []);

    const totals = imports.reduce(
        (acc, imp) => ({
            match: acc.match + imp.counts.match,
            not_match: acc.not_match + imp.counts.not_match,
            bill: acc.bill + imp.counts.bill,
        }),
        { match: 0, not_match: 0, bill: 0 }
    );

    return (
        <div>
            <div className="page-header">
                <h1>Dashboard</h1>
                <Link to="/upload" className="btn-link">
                    New import
                </Link>
            </div>

            {!loading && (
                <div className="summary-cards">
                    <div className="summary-card card-match">
                        <div className="summary-card-value">{totals.match}</div>
                        <div className="summary-card-label">Total match</div>
                    </div>
                    <div className="summary-card card-not-match">
                        <div className="summary-card-value">{totals.not_match}</div>
                        <div className="summary-card-label">Total not match</div>
                    </div>
                    <div className="summary-card card-bill">
                        <div className="summary-card-value">{totals.bill}</div>
                        <div className="summary-card-label">Total bill</div>
                    </div>
                </div>
            )}

            <h2>Recent imports</h2>
            {loading ? <p>Loading…</p> : <ImportsTable imports={imports} />}
        </div>
    );
}
