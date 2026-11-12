import { Navigate } from 'react-router-dom';
import { getToken } from '../authApi';

export default function RequireAuth({ children }) {
    if (!getToken()) {
        return <Navigate to="/login" replace />;
    }

    return children;
}
