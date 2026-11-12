import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Bell, ChevronDown, Menu } from 'lucide-react';
import { getUser, logout } from '../authApi';

const BREADCRUMB_LABELS = {
    '/': 'Overview',
    '/new': 'New Import',
    '/history': 'Import History',
    '/reconciliation': 'Reconciliation',
    '/reports': 'Reports',
    '/settings': 'Settings',
};

function breadcrumbFor(pathname) {
    if (BREADCRUMB_LABELS[pathname]) {
        return [BREADCRUMB_LABELS[pathname]];
    }

    if (pathname.startsWith('/imports/')) {
        return ['Import History', 'Latest Import'];
    }

    return ['Overview'];
}

function initials(name) {
    if (!name) return '?';
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function Topbar({ onMenuClick }) {
    const location = useLocation();
    const navigate = useNavigate();
    const user = getUser();
    const [menuOpen, setMenuOpen] = useState(false);
    const crumbs = breadcrumbFor(location.pathname);

    const handleLogout = async () => {
        try {
            await logout();
        } catch {
            // ignore — session is cleared locally regardless
        }
        toast.success('Signed out.');
        navigate('/login');
    };

    return (
        <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={onMenuClick}
                    className="rounded-lg p-1.5 text-slate-500 transition-colors duration-200 hover:bg-slate-100 lg:hidden"
                    aria-label="Open menu"
                >
                    <Menu size={20} />
                </button>
                <nav className="hidden items-center gap-1.5 text-sm text-slate-500 sm:flex">
                    <span className="h-1.5 w-1.5 rounded-full bg-slate-300" />
                    {crumbs.map((crumb, i) => (
                        <span key={crumb} className="flex items-center gap-1.5">
                            {i > 0 && <span className="text-slate-300">/</span>}
                            <span className={i === crumbs.length - 1 ? 'font-medium text-slate-700' : ''}>{crumb}</span>
                        </span>
                    ))}
                </nav>
            </div>

            <div className="flex items-center gap-3">
                <button
                    type="button"
                    className="rounded-lg p-1.5 text-slate-400 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Notifications"
                >
                    <Bell size={18} />
                </button>
                <span className="h-6 w-px bg-slate-200" />
                <div className="relative">
                    <button
                        type="button"
                        onClick={() => setMenuOpen((v) => !v)}
                        className="flex items-center gap-2 rounded-lg px-2 py-1.5 transition-colors duration-200 hover:bg-slate-50"
                    >
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                            {initials(user?.name) || 'TU'}
                        </span>
                        <span className="hidden text-sm font-medium text-slate-700 sm:inline">{user?.name || 'Test User'}</span>
                        <ChevronDown size={16} className="text-slate-400" />
                    </button>
                    {menuOpen && (
                        <div className="absolute right-0 top-full mt-2 w-40 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="w-full px-3 py-2 text-left text-sm text-slate-600 transition-colors duration-200 hover:bg-slate-50"
                            >
                                Log out
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
