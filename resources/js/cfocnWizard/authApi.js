const TOKEN_KEY = 'cfocn_wizard_token';
const USER_KEY = 'cfocn_wizard_user';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function getUser() {
    try {
        return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch {
        return null;
    }
}

export function setSession(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
}

async function api(path, options = {}) {
    const response = await fetch(`/api${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
            ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
            ...options.headers,
        },
    });

    if (response.status === 401) {
        clearSession();
        throw new Error('Session expired. Please log in again.');
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || `Request failed (HTTP ${response.status}).`);
        error.status = response.status;
        error.errors = data.errors;
        throw error;
    }

    return data;
}

export async function login(email, password) {
    const data = await api('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
    });
    setSession(data.token, data.user);
    return data.user;
}

export async function logout() {
    try {
        await api('/logout', { method: 'POST' });
    } finally {
        clearSession();
    }
}

export function listSavedImports({ page = 1, perPage = 10, search = '' } = {}) {
    const params = new URLSearchParams({ page, per_page: perPage });
    if (search) {
        params.set('search', search);
    }
    return api(`/saved-imports?${params.toString()}`);
}

export function getSavedImport(id) {
    return api(`/saved-imports/${id}`);
}

export function updateSavedImportLabel(id, label) {
    return api(`/saved-imports/${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ label }),
    });
}

export function deleteSavedImport(id) {
    return api(`/saved-imports/${id}`, { method: 'DELETE' });
}

export function getSavedImportRows(id, { sheet = 'not_match', page = 1, perPage = 10, search = '' } = {}) {
    const params = new URLSearchParams({ sheet, page, per_page: perPage });
    if (search) {
        params.set('search', search);
    }
    return api(`/saved-imports/${id}/rows?${params.toString()}`);
}

export function inspectBuilderInfrastructures(builderFile) {
    const body = new FormData();
    body.append('builder', builderFile);
    return api('/cfocn-filter/inspect-builder', { method: 'POST', body });
}

export function runFilteredCfocnImport({ builderFile, cfoFile, infrastructures, allInfrastructures, rule }) {
    const body = new FormData();
    body.append('builder', builderFile);
    body.append('cfo', cfoFile);
    body.append('rule', rule);
    body.append('all_infrastructures', allInfrastructures ? '1' : '0');
    infrastructures.forEach((value) => body.append('infrastructures[]', value));
    return api('/cfocn-filter/run', { method: 'POST', body });
}

export function importVerifiedList(file) {
    const body = new FormData();
    body.append('file', file);
    return api('/cfocn-filter/verified-list', { method: 'POST', body });
}

export function getLatestCfocnFilteredRun() {
    return api('/cfocn-filter/latest');
}

export async function downloadSavedImport(id, filename) {
    const response = await fetch(`/api/saved-imports/${id}/download`, {
        headers: { Authorization: `Bearer ${getToken()}` },
    });

    if (!response.ok) {
        throw new Error(`Download failed (HTTP ${response.status}).`);
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || `saved-import-${id}.xlsx`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}
