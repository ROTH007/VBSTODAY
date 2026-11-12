import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import RequireAuth from './components/RequireAuth';
import Layout from './components/Layout';
import Login from './pages/Login';
import Overview from './pages/Overview';
import NewImportWizard from './pages/NewImportWizard';
import ImportHistory from './pages/ImportHistory';
import Reconciliation from './pages/Reconciliation';
import InfrastructureFilterImport from './pages/InfrastructureFilterImport';
import Reports from './pages/Reports';
import Settings from './pages/Settings';
import ImportDetail from './pages/ImportDetail';

export default function RootApp() {
    return (
        <BrowserRouter basename="/cfocn-wizard">
            <Toaster position="top-right" toastOptions={{ duration: 3000 }} />
            <Routes>
                <Route path="/login" element={<Login />} />
                <Route
                    path="/"
                    element={
                        <RequireAuth>
                            <Layout />
                        </RequireAuth>
                    }
                >
                    <Route index element={<Overview />} />
                    <Route path="new" element={<NewImportWizard />} />
                    <Route path="history" element={<ImportHistory />} />
                    <Route path="reconciliation" element={<Reconciliation />} />
                    <Route path="infrastructure-filter" element={<InfrastructureFilterImport />} />
                    <Route path="reports" element={<Reports />} />
                    <Route path="settings" element={<Settings />} />
                    <Route path="imports/:id" element={<ImportDetail />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
