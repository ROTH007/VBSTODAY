import * as XLSX from 'xlsx';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Reads a sheet into { headers, rows }, rows as objects keyed by column
 * name (from the sheet's own header row). This only reads what the server
 * already computed — it never decides which sheet a row belongs to or
 * recomputes any match. If the sheet doesn't exist, returns an empty result
 * instead of throwing.
 */
function readSheet(workbook, sheetName) {
    const sheet = workbook.Sheets[sheetName];
    if (!sheet) {
        return { headers: [], rows: [] };
    }

    const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });
    const headers = rows.length > 0 ? Object.keys(rows[0]) : [];

    return { headers, rows };
}

function filenameFromContentDisposition(header) {
    if (!header) {
        return null;
    }

    // filename="CFOCN Active List.xlsx"  or  filename*=UTF-8''CFOCN%20Active%20List.xlsx
    const starMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (starMatch) {
        return decodeURIComponent(starMatch[1]);
    }

    const plainMatch = header.match(/filename="?([^";]+)"?/i);
    return plainMatch ? plainMatch[1] : null;
}

function looksLikeFileResponse(response) {
    const contentType = response.headers.get('content-type') || '';
    const disposition = response.headers.get('content-disposition') || '';

    return contentType.includes('spreadsheet') || contentType.includes('application/vnd.ms-excel') || disposition.includes('attachment');
}

// Maps the controller's session-flash keys (see redirect(...)->with(...) in
// ImportFileController) to the CSS class homePage.blade.php renders them
// with, so we can tell *which* flash fired even after fetch follows the
// redirect and all we can see is the resulting HTML page.
const FLASH_CLASS_LABELS = {
    'alert-primary': 'Invalid Builder file',
    'alert-warning': 'Invalid Company file',
    'alert-danger': 'Import failed',
};

/**
 * Submits the existing /importExecute endpoint with a multipart form, exactly
 * like the legacy Blade form does. No matching/import logic lives here —
 * this only uploads the files and reads the server's own response.
 *
 * Note: this intentionally does NOT use `redirect: 'manual'`. Per the fetch
 * spec, a manual-redirect response is opaque (status 0, no readable headers
 * or body) even for a same-origin request — there is no way to read the
 * Location header or the flashed error message from it in a browser. Using
 * `redirect: 'follow'` (the default) lets us actually read the page the
 * server redirects to and pull the real flashed message out of it.
 */
export async function submitCfocnImport({ builderFile, cfoFile, status }) {
    const formData = new FormData();
    formData.append('builder', builderFile);
    formData.append('cfo', cfoFile);
    formData.append('company', 'cfocn');
    formData.append('status', status);

    const response = await fetch('/importExecute', {
        method: 'POST',
        redirect: 'follow',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'text/html,application/xhtml+xml,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        },
        body: formData,
    });

    if (response.status === 500) {
        const text = await response.text();
        throw new Error(`Server error (HTTP 500): ${text.slice(0, 300)}`);
    }

    if (looksLikeFileResponse(response) && response.ok) {
        const filename = filenameFromContentDisposition(response.headers.get('content-disposition')) || 'CFOCN-import-result.xlsx';
        const blob = await response.blob();
        const arrayBuffer = await blob.arrayBuffer();
        const workbook = XLSX.read(arrayBuffer, { type: 'array' });

        // These are the sheets' actual titles (WithTitle::title() on each
        // sheet class) — not the array keys used internally in
        // MainSheet::sheets() ('Check'/'Pay'/'Other'), which are unrelated
        // to the tab name Excel actually ends up with.
        const bill = readSheet(workbook, 'Bill');
        const match = readSheet(workbook, 'Match');
        const notMatch = readSheet(workbook, 'Not match');

        return {
            blob,
            filename,
            counts: {
                bill: bill.rows.length,
                match: match.rows.length,
                notMatch: notMatch.rows.length,
            },
            sheets: { bill, match, notMatch },
            completedAt: new Date().toISOString(),
        };
    }

    // Not a file: either the request got redirected back to '/' with a
    // flashed session error, or something else went wrong. Try to pull the
    // real message out of the page the server actually sent back.
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const alertEl = doc.querySelector('.alert');

    if (alertEl) {
        const label = Object.entries(FLASH_CLASS_LABELS).find(([cls]) => alertEl.classList.contains(cls))?.[1];
        const text = alertEl.textContent.trim();
        throw new Error(label ? `${label}: ${text}` : text);
    }

    throw new Error(`Import request failed (HTTP ${response.status}) and no error message was returned.`);
}
