import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import api from '../api';

export default function Layout() {
    const navigate = useNavigate();
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    const handleLogout = async () => {
        try {
            await api.post('/logout');
        } finally {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            navigate('/login');
        }
    };

    return (
        <div className="app-shell">
            <header className="app-header">
                <Link to="/" className="app-title">
                    TODAY | VBS
                </Link>
                <nav>
                    <NavLink to="/" end>
                        Dashboard
                    </NavLink>
                    <NavLink to="/upload">Upload</NavLink>
                    <NavLink to="/settings">Settings</NavLink>
                </nav>
                <div className="app-header-user">
                    {user && <span>{user.name}</span>}
                    <button onClick={handleLogout}>Logout</button>
                </div>
            </header>
            <main className="app-main">
                <Outlet />
            </main>
        </div>
    );
}
