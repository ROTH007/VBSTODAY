import { useState } from 'react';
import Modal from './Modal';

export default function EditLabelModal({ open, onClose, initialLabel, onSave, saving }) {
    const [label, setLabel] = useState(initialLabel || '');

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(label);
    };

    return (
        <Modal open={open} onClose={onClose} title="Edit label">
            <form onSubmit={handleSubmit}>
                <input
                    type="text"
                    maxLength={255}
                    value={label}
                    onChange={(e) => setLabel(e.target.value)}
                    autoFocus
                    className="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition-colors duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    placeholder="e.g. October CFOCN run"
                />
                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={saving}
                        className="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:opacity-60"
                    >
                        {saving ? 'Saving…' : 'Save'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
