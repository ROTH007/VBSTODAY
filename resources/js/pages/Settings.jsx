const COMPANY_HEADERS = {
    cfocn: ['Work Order', 'PORT', 'POS', 'Team Install', 'Create Time'],
    tct: ['TCT Id', 'TCT SID', 'New circuit ID', 'Status', 'Total NRC'],
    adi: [
        'AID', 'SID', 'Customer Name', 'Qty', 'Amount', 'VAT',
        'Total Amount', 'Invoice Description', 'Date Start', 'Date To',
    ],
    telecom: ['Account No', 'ID', 'Name Customer', 'Project', 'Issue Date', 'Complete Date'],
};

export default function Settings() {
    return (
        <div>
            <div className="page-header">
                <h1>Settings</h1>
            </div>

            <h2>Expected company file columns</h2>
            <p>Each company's uploaded file must contain these columns (read-only reference).</p>
            {Object.entries(COMPANY_HEADERS).map(([company, headers]) => (
                <div key={company} className="settings-block">
                    <h3>{company.toUpperCase()}</h3>
                    <p>{headers.join(', ')}</p>
                </div>
            ))}

            <h2>Status-filter rule</h2>
            <p>
                A company row with <code>Status</code> = <code>N</code> is excluded from every count
                (match, not match, and bill). Today this only applies to <strong>TCT</strong>, since
                it's the only company file with a Status column.
            </p>
        </div>
    );
}
