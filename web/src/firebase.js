import { API_BASE_URL } from './api';

export const firebaseConfigured = Boolean(API_BASE_URL);

async function firebaseRequest(path, body) {
  let response;
  try {
    response = await fetch(`${API_BASE_URL}/auth/${path}`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  } catch {
    throw new Error('Check your connection and try again.');
  }

  const payload = await response.json().catch(() => ({}));
  if (!response.ok || !payload?.success) throw new Error(payload?.message || 'Authentication could not be completed.');

  return payload.data;
}

export async function firebaseLogin(email, password) {
  const credential = await firebaseRequest('login', { email, password });
  return credential.id_token;
}

export async function firebaseRegister(name, email, password) {
  await firebaseRequest('register', { name, email, password });
}

export async function firebaseResetPassword(email) {
  await firebaseRequest('password-reset', { email });
}

export const firebaseLogout = () => Promise.resolve();
