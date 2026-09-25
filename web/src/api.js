export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'https://admin.qismatconnections.com/api/v1';

const TOKEN_KEY = 'qismat.member.token';
export const session = {
  get: () => sessionStorage.getItem(TOKEN_KEY),
  set: (token) => sessionStorage.setItem(TOKEN_KEY, token),
  clear: () => sessionStorage.removeItem(TOKEN_KEY),
};

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
    throw new Error(validationMessage || payload?.message || 'The request could not be completed.');
  }
  return payload;
}

export async function exchangeFirebaseToken(idToken) {
  const payload = await request('/auth/firebase', { method: 'POST', token: idToken });
  session.set(payload.data.token);
  return payload.data;
}

export const getCurrentUser = () => request('/auth/me').then(({ data }) => data);
export const getProfile = () => request('/profile').then(({ data }) => data);
export const getPartnerPreferences = () => request('/profile/partner-preferences').then(({ data }) => data);
export const getOnboardingStatus = () => request('/profile/onboarding-status').then(({ data }) => data);
export const updateProfile = (profile) => request('/profile', { method: 'PUT', body: JSON.stringify(profile) }).then(({ data }) => data);
export const updatePartnerPreferences = (preferences) => request('/profile/partner-preferences', { method: 'PUT', body: JSON.stringify(preferences) }).then(({ data }) => data);
export const submitProfile = () => request('/profile/submit', { method: 'POST' }).then(({ data }) => data);
export const updateDiscovery = (enabled) => request('/profile/discovery', { method: 'PUT', body: JSON.stringify({ enabled }) }).then(({ data }) => data);
export async function logoutApi() {
  try { if (session.get()) await request('/auth/logout', { method: 'POST' }); }
  finally { session.clear(); }
}
