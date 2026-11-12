import { BarChart, Bar, Cell, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts';

const COLORS = {
    match: '#2f9e44',
    not_match: '#e8590c',
    bill: '#1971c2',
};

export default function CountsChart({ counts }) {
    const data = [
        { name: 'Match', value: counts.match, fill: COLORS.match },
        { name: 'Not match', value: counts.not_match, fill: COLORS.not_match },
        { name: 'Bill', value: counts.bill, fill: COLORS.bill },
    ];

    return (
        <ResponsiveContainer width="100%" height={220}>
            <BarChart data={data}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="name" />
                <YAxis allowDecimals={false} />
                <Tooltip />
                <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                    {data.map((entry) => (
                        <Cell key={entry.name} fill={entry.fill} />
                    ))}
                </Bar>
            </BarChart>
        </ResponsiveContainer>
    );
}
