import ImportForm from '../components/ImportForm';

export default function Upload() {
    return (
        <div>
            <div className="page-header">
                <h1>Upload</h1>
            </div>
            <p>Select the builder (today order) file and the company infrastructure file, then submit.</p>
            <ImportForm />
        </div>
    );
}
