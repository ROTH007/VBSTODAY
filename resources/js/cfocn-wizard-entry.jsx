import { createRoot } from 'react-dom/client';
import RootApp from './cfocnWizard/RootApp';
import './cfocnWizard/tailwind.css';

const container = document.getElementById('cfocn-wizard-app');

if (container) {
    createRoot(container).render(<RootApp />);
}
