import { Link } from 'react-router-dom';

export default function ImportsTable({ imports }) {
    if (imports.length === 0) {
        return <p className="empty-state">No imports yet.</p>;
    }

    return (
        <table className="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Match</th>
                    <th>Not match</th>
                    <th>Bill</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                {imports.map((imp) => (
                    <tr key={imp.id}>
                        <td>
                            <Link to={`/imports/${imp.id}`}>#{imp.id}</Link>
                        </td>
                        <td>{imp.company}</td>
                        <td>
                            <span className={`status-badge status-${imp.status}`}>{imp.status}</span>
                        </td>
                        <td>{imp.counts.match}</td>
                        <td>{imp.counts.not_match}</td>
                        <td>{imp.counts.bill}</td>
                        <td>{new Date(imp.created_at).toLocaleString()}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
