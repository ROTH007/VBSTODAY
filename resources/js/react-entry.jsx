import { createRoot } from 'react-dom/client';
import App from './RootApp';
import './react-app.css';

const container = document.getElementById('app');

if (container) {
    createRoot(container).render(<App />);
}
