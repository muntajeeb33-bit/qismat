import { getApp, getApps, initializeApp } from 'firebase/app';
import { createUserWithEmailAndPassword, getAuth, sendEmailVerification, sendPasswordResetEmail, signInWithEmailAndPassword, signOut, updateProfile } from 'firebase/auth';

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
    'auth/email-already-in-use': 'An account already exists for this email.',
    'auth/operation-not-allowed': 'Email and password registration is not enabled.',
    'auth/unauthorized-domain': 'This website domain is not authorized for Firebase sign-in.',
    'auth/user-disabled': 'This account has been disabled.',
    'auth/invalid-credential': 'The email or password is incorrect.',
    'auth/invalid-login-credentials': 'The email or password is incorrect.',
    'auth/user-not-found': 'The email or password is incorrect.',
    'auth/wrong-password': 'The email or password is incorrect.',
    'auth/invalid-email': 'Enter a valid email address.',
    'auth/missing-password': 'Enter your password.',
    'auth/weak-password': 'Choose a stronger password with at least eight characters.',
    'auth/too-many-requests': 'Too many attempts. Please wait and try again.',
    'auth/network-request-failed': 'Check your connection and try again.',
    'auth/api-key-not-valid': 'Firebase configuration is invalid. Please contact support.',
    'auth/invalid-api-key': 'Firebase configuration is invalid. Please contact support.',
    'auth/internal-error': 'Firebase encountered an internal error. Please try again.',
  };
  const code = error?.code || 'unknown-error';
  return new Error(messages[code] || `Authentication could not be completed (${code}).`);
}

function requireAuth() {
  if (!auth) throw new Error('Sign-in is not configured for this environment.');
}

export async function firebaseLogin(email, password) {
  requireAuth();
  try {
    const credential = await signInWithEmailAndPassword(auth, email, password);
    if (!credential.user.emailVerified) {
      await sendEmailVerification(credential.user);
      await signOut(auth);
      throw new Error('Verify your email before signing in. We sent a new verification link.');
    }
    return credential.user.getIdToken(true);
  } catch (error) {
    if (error?.message?.startsWith('Verify your email')) throw error;
    throw readableError(error);
  }
}

export async function firebaseRegister(name, email, password) {
  requireAuth();
  try {
    const credential = await createUserWithEmailAndPassword(auth, email, password);
    await updateProfile(credential.user, { displayName: name });
    await sendEmailVerification(credential.user);
    await signOut(auth);
  } catch (error) { throw readableError(error); }
}

export async function firebaseResetPassword(email) {
  requireAuth();
  try { await sendPasswordResetEmail(auth, email); }
  catch (error) { throw readableError(error); }
}

export const firebaseLogout = () => auth ? signOut(auth) : Promise.resolve();
