import { API_BASE_URL } from './api';
import { getApp, getApps, initializeApp } from 'firebase/app';
import { getAuth, GoogleAuthProvider, OAuthProvider, signInWithPopup, signOut } from 'firebase/auth';

export const firebaseConfigured = Boolean(API_BASE_URL);
const socialConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY?.trim(),
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN?.trim(),
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID?.trim(),
};
export const socialLoginConfigured = Boolean(socialConfig.apiKey && socialConfig.authDomain && socialConfig.projectId);
const socialApp = socialLoginConfigured
  ? (getApps().length ? getApp() : initializeApp(socialConfig))
  : null;
const socialAuth = socialApp ? getAuth(socialApp) : null;

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

export async function firebaseSocialLogin(providerName) {
  if (!socialAuth) throw new Error('Social sign-in is not configured for this environment.');

  const provider = providerName === 'apple'
    ? new OAuthProvider('apple.com')
    : new GoogleAuthProvider();

  if (providerName === 'apple') {
    provider.addScope('email');
    provider.addScope('name');
  } else {
    provider.setCustomParameters({ prompt: 'select_account' });
  }

  try {
    const credential = await signInWithPopup(socialAuth, provider);
    return credential.user.getIdToken(true);
  } catch (error) {
    const messages = {
      'auth/operation-not-allowed': `${providerName === 'apple' ? 'Apple' : 'Google'} sign-in is not enabled in Firebase yet.`,
      'auth/unauthorized-domain': 'This website domain is not authorized for social sign-in.',
      'auth/popup-blocked': 'Your browser blocked the sign-in window. Allow pop-ups and try again.',
      'auth/popup-closed-by-user': 'The sign-in window was closed before completion.',
      'auth/cancelled-popup-request': 'The sign-in request was cancelled. Please try again.',
      'auth/account-exists-with-different-credential': 'An account already exists for this email using another sign-in method.',
      'auth/api-key-not-valid.-please-pass-a-valid-api-key.': 'Firebase configuration is invalid. Please contact support.',
    };
    throw new Error(messages[error?.code] || `Social sign-in could not be completed (${error?.code || 'unknown error'}).`);
  }
}

export const firebaseLogout = () => socialAuth ? signOut(socialAuth) : Promise.resolve();
