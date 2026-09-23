import { getApp, getApps, initializeApp } from 'firebase/app';
import { getAuth, sendPasswordResetEmail, signInWithEmailAndPassword, signOut } from 'firebase/auth';

const config = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

export const firebaseConfigured = Boolean(config.apiKey && config.authDomain && config.projectId);
const app = firebaseConfigured ? (getApps().length ? getApp() : initializeApp(config)) : null;
export const auth = app ? getAuth(app) : null;

function readableError(error) {
  const messages = {
    'auth/invalid-credential': 'The email or password is incorrect.',
    'auth/invalid-email': 'Enter a valid email address.',
    'auth/too-many-requests': 'Too many attempts. Please wait and try again.',
    'auth/network-request-failed': 'Check your connection and try again.',
  };
  return new Error(messages[error?.code] || 'Authentication could not be completed.');
}

export async function firebaseLogin(email, password) {
  if (!auth) throw new Error('Firebase is not configured for this environment.');
  try {
    const credential = await signInWithEmailAndPassword(auth, email, password);
    return { user: credential.user, idToken: await credential.user.getIdToken(true) };
  } catch (error) { throw readableError(error); }
}

export async function firebaseResetPassword(email) {
  if (!auth) throw new Error('Firebase is not configured for this environment.');
  try { await sendPasswordResetEmail(auth, email); }
  catch (error) { throw readableError(error); }
}

export const firebaseLogout = () => auth ? signOut(auth) : Promise.resolve();
