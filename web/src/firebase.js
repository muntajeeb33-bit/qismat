const config = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY?.trim(),
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN?.trim(),
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID?.trim(),
};

export const firebaseConfigured = Boolean(config.apiKey && config.authDomain && config.projectId);

const messages = {
  EMAIL_EXISTS: 'An account already exists for this email.',
  OPERATION_NOT_ALLOWED: 'Email and password registration is not enabled.',
  USER_DISABLED: 'This account has been disabled.',
  EMAIL_NOT_FOUND: 'The email or password is incorrect.',
  INVALID_PASSWORD: 'The email or password is incorrect.',
  INVALID_LOGIN_CREDENTIALS: 'The email or password is incorrect.',
  INVALID_EMAIL: 'Enter a valid email address.',
  MISSING_PASSWORD: 'Enter your password.',
  WEAK_PASSWORD: 'Choose a stronger password with at least eight characters.',
  TOO_MANY_ATTEMPTS_TRY_LATER: 'Too many attempts. Please wait and try again.',
  API_KEY_NOT_VALID: 'Firebase configuration is invalid. Please contact support.',
};

function requireAuth() {
  if (!firebaseConfigured) throw new Error('Sign-in is not configured for this environment.');
}

async function firebaseRequest(action, body) {
  requireAuth();

  let response;
  try {
    response = await fetch(`https://identitytoolkit.googleapis.com/v1/accounts:${action}?key=${encodeURIComponent(config.apiKey)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  } catch {
    throw new Error('Check your connection and try again.');
  }

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    const rawCode = payload?.error?.message || `HTTP_${response.status}`;
    const code = rawCode.split(' : ')[0];
    throw new Error(messages[code] || `Authentication could not be completed (${code}).`);
  }

  return payload;
}

export async function firebaseLogin(email, password) {
  const credential = await firebaseRequest('signInWithPassword', { email, password, returnSecureToken: true });
  const account = await firebaseRequest('lookup', { idToken: credential.idToken });

  if (!account.users?.[0]?.emailVerified) {
    await firebaseRequest('sendOobCode', { requestType: 'VERIFY_EMAIL', idToken: credential.idToken });
    throw new Error('Verify your email before signing in. We sent a new verification link.');
  }

  return credential.idToken;
}

export async function firebaseRegister(name, email, password) {
  const credential = await firebaseRequest('signUp', { email, password, returnSecureToken: true });
  const updated = await firebaseRequest('update', {
    idToken: credential.idToken,
    displayName: name,
    returnSecureToken: true,
  });
  await firebaseRequest('sendOobCode', {
    requestType: 'VERIFY_EMAIL',
    idToken: updated.idToken || credential.idToken,
  });
}

export async function firebaseResetPassword(email) {
  await firebaseRequest('sendOobCode', { requestType: 'PASSWORD_RESET', email });
}

export const firebaseLogout = () => Promise.resolve();
