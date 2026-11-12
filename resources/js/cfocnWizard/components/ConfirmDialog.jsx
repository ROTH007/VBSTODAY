import Modal from './Modal';

export default function ConfirmDialog({ open, onClose, onConfirm, title, message, confirming }) {
    return (
        <Modal open={open} onClose={onClose} title={title}>
            <p className="mb-5 text-sm text-slate-600">{message}</p>
            <div className="flex justify-end gap-2">
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    onClick={onConfirm}
                    disabled={confirming}
                    className="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700 disabled:opacity-60"
                >
                    {confirming ? 'Deleting…' : 'Delete'}
                </button>
            </div>
        </Modal>
    );
}
