export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

const TOKEN_KEY = 'qismat.admin.token';

export const session = {
  get: () => sessionStorage.getItem(TOKEN_KEY),
  set: (token) => sessionStorage.setItem(TOKEN_KEY, token),
  clear: () => sessionStorage.removeItem(TOKEN_KEY),
};

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

async function request(path, options = {}) {
  const token = options.token ?? session.get();
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload?.success) {
    const validationMessage = Object.values(payload?.errors || {}).flat()[0];
    throw new ApiError(validationMessage || payload?.message || 'The request could not be completed.', response.status, payload?.errors);
  }
  return payload;
}

export async function exchangeFirebaseToken(idToken) {
  const payload = await request('/auth/firebase', { method: 'POST', token: idToken });
  session.set(payload.data.token);
  return payload.data;
}

export const getCurrentUser = () => request('/auth/me').then(({ data }) => data);
export const getDashboard = () => request('/admin/dashboard').then(({ data }) => data);
export const getPendingProfiles = () => request('/admin/profiles?status=pending').then(({ data }) => data);
export const getPendingPhotos = () => request('/admin/photos?status=pending').then(({ data }) => data);
export const getDiscoveryDiagnostics = (query = '') => request(`/admin/discovery${query ? `?q=${encodeURIComponent(query)}` : ''}`).then(({ data }) => data);
export const getReports = (status = 'open') => request(`/admin/reports?status=${encodeURIComponent(status)}`).then(({ data }) => data);
export const getMembers = (filters = {}) => {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value));
  return request(`/admin/members?${query}`).then(({ data }) => data);
};
export const updateMember = (userId, changes) => request(`/admin/members/${userId}`, { method: 'PATCH', body: JSON.stringify(changes) }).then(({ data }) => data);
export const getAuditLogs = (action = '') => request(`/admin/audit-logs${action ? `?action=${encodeURIComponent(action)}` : ''}`).then(({ data }) => data);
export const resolveReport = (reportId, action, notes) => request(`/admin/reports/${reportId}/resolve`, {
  method: 'POST',
  body: JSON.stringify({ action, notes }),
}).then(({ data }) => data);
export const reviewProfile = (profileId, decision, reason) => request(`/admin/profiles/${profileId}/review`, {
  method: 'POST',
  body: JSON.stringify({ decision, reason: reason || null }),
}).then(({ data }) => data);
export const reviewPhoto = (photoId, decision, reason) => request(`/admin/photos/${photoId}/review`, {
  method: 'POST',
  body: JSON.stringify({ decision, reason: reason || null }),
}).then(({ data }) => data);
export async function getPhotoBlob(contentUrl) {
  const response = await fetch(contentUrl, {
    headers: { Accept: 'image/*', Authorization: `Bearer ${session.get()}` },
  });
  if (!response.ok) throw new ApiError('The photo could not be loaded.', response.status);
  return response.blob();
}

export async function logoutApi() {
  try {
    if (session.get()) await request('/auth/logout', { method: 'POST' });
  } finally {
    session.clear();
  }
}
