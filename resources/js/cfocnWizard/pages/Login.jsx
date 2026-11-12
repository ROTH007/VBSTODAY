import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { login } from '../authApi';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await login(email, password);
            toast.success('Signed in.');
            navigate('/');
        } catch (err) {
            toast.error(err.message || 'Login failed.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
            <form
                onSubmit={handleSubmit}
                className="w-full max-w-sm rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition-shadow duration-200 hover:shadow-md"
            >
                <h1 className="mb-1 text-xl font-semibold text-slate-900">CFOCN Import</h1>
                <p className="mb-6 text-sm text-slate-500">Sign in to continue.</p>

                <label className="mb-3 block text-sm font-medium text-slate-700">
                    Email
                    <input
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition-colors duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                </label>

                <label className="mb-5 block text-sm font-medium text-slate-700">
                    Password
                    <input
                        type="password"
                        required
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition-colors duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                </label>

                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded-lg bg-blue-600 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-60"
                >
                    {submitting ? 'Signing in…' : 'Sign in'}
                </button>
            </form>
        </div>
    );
}
