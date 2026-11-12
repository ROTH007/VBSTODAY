import { NavLink } from 'react-router-dom';
import { Database, LayoutDashboard, UploadCloud, History, GitCompareArrows, BarChart3, Settings, SlidersHorizontal, X } from 'lucide-react';

const NAV_ITEMS = [
    { to: '/', label: 'Overview', icon: LayoutDashboard, end: true },
    { to: '/new', label: 'New Import', icon: UploadCloud },
    { to: '/history', label: 'Import History', icon: History },
    { to: '/reconciliation', label: 'Reconciliation', icon: GitCompareArrows },
    { to: '/infrastructure-filter', label: 'Infrastructure Filter', icon: SlidersHorizontal },
    { to: '/reports', label: 'Reports', icon: BarChart3 },
    { to: '/settings', label: 'Settings', icon: Settings },
];

export default function Sidebar({ onNavigate, onClose }) {
    return (
        <div className="flex h-full w-60 flex-col border-r border-slate-200 bg-white">
            <div className="flex items-center justify-between px-5 py-5">
                <div className="flex items-center gap-2">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <Database size={18} />
                    </span>
                    <span className="text-sm font-semibold text-slate-900">CFOCN Import</span>
                </div>
                {onClose && (
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-1 text-slate-400 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-600 lg:hidden"
                        aria-label="Close menu"
                    >
                        <X size={18} />
                    </button>
                )}
            </div>

            <nav className="flex-1 space-y-1 px-3">
                {NAV_ITEMS.map(({ to, label, icon: Icon, end }) => (
                    <NavLink
                        key={to}
                        to={to}
                        end={end}
                        onClick={onNavigate}
                        className={({ isActive }) =>
                            `flex items-center gap-3 rounded-lg border-l-[3px] px-3 py-2.5 text-sm font-medium transition-colors duration-200 ${
                                isActive
                                    ? 'border-blue-600 bg-blue-50 text-blue-700'
                                    : 'border-transparent text-slate-600 hover:bg-slate-50'
                            }`
                        }
                    >
                        <Icon size={18} />
                        {label}
                    </NavLink>
                ))}
            </nav>

            <div className="border-t border-slate-100 px-5 py-4 text-xs text-slate-400">
                <p className="font-medium text-slate-500">CFOCN Import v1.0.0</p>
                <p>Better data. Better decisions.</p>
            </div>
        </div>
    );
}
