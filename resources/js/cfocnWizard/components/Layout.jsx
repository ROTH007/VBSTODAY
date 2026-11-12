import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

export default function Layout() {
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="min-h-screen bg-[#F4F7FB]">
            <div className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:block">
                <Sidebar />
            </div>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div
                        className="absolute inset-0 bg-slate-900/40 transition-opacity duration-200"
                        onClick={() => setMobileOpen(false)}
                    />
                    <div className="absolute inset-y-0 left-0 w-60 transition-transform duration-200">
                        <Sidebar onNavigate={() => setMobileOpen(false)} onClose={() => setMobileOpen(false)} />
                    </div>
                </div>
            )}

            <div className="lg:pl-60">
                <Topbar onMenuClick={() => setMobileOpen(true)} />
                <main className="px-4 py-6 sm:px-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
