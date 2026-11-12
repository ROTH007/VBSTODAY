import api from '../api';

export default function DownloadButton({ importId }) {
    const handleDownload = async () => {
        const response = await api.get(`/imports/${importId}/download`, {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = `import-${importId}.xlsx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    };

    return (
        <button type="button" onClick={handleDownload}>
            Download workbook
        </button>
    );
}
