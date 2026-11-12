export default function CountsSummaryCards({ counts }) {
    const items = [
        { label: 'Match', value: counts.match, className: 'card-match' },
        { label: 'Not match', value: counts.not_match, className: 'card-not-match' },
        { label: 'Bill', value: counts.bill, className: 'card-bill' },
    ];

    return (
        <div className="summary-cards">
            {items.map((item) => (
                <div key={item.label} className={`summary-card ${item.className}`}>
                    <div className="summary-card-value">{item.value}</div>
                    <div className="summary-card-label">{item.label}</div>
                </div>
            ))}
        </div>
    );
}
