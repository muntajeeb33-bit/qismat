import React, { useEffect, useState } from 'react';
import { exchangeFirebaseToken, getOnboardingStatus } from './api';
import { firebaseConfigured, firebaseLogin, firebaseRegister, firebaseResetPassword } from './firebase';
import './auth.css';

export function AuthPanel({ initialMode = 'login', onClose, onAuthenticated }) {
  const [mode, setMode] = useState(initialMode);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [success, setSuccess] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true); setMessage(''); setSuccess(false);
    try {
      if (mode === 'register') {
        await firebaseRegister(name, email, password);
        setSuccess(true);
        setMessage('Account created. Check your email to verify it, then sign in.');
        setMode('login'); setPassword('');
      } else {
        const idToken = await firebaseLogin(email, password);
        const data = await exchangeFirebaseToken(idToken);
        onAuthenticated(data.user);
      }
    } catch (error) { setMessage(error.message || 'Unable to continue.'); }
    finally { setBusy(false); }
  }

  async function reset() {
    if (!email) return setMessage('Enter your email first.');
    setBusy(true); setMessage(''); setSuccess(false);
    try { await firebaseResetPassword(email); setSuccess(true); setMessage('Password reset email sent.'); }
    catch (error) { setMessage(error.message || 'Unable to send reset email.'); }
    finally { setBusy(false); }
  }

  return <div className="auth-backdrop" role="dialog" aria-modal="true" aria-label="Member access"><section className="auth-panel">
    <button className="auth-close" onClick={onClose} aria-label="Close">×</button>
    <span className="kicker">Member access</span><h2>{mode === 'register' ? 'Create your Qismat account' : 'Welcome back'}</h2>
    <p>{mode === 'register' ? 'Start with a verified email. Your profile remains private until you complete it and opt into discovery.' : 'Sign in to continue your profile and connections.'}</p>
    {!firebaseConfigured && <div className="auth-notice error">Sign-in is not configured for this environment.</div>}
    <form onSubmit={submit}>
      {mode === 'register' && <label>Full name<input value={name} onChange={(event) => setName(event.target.value)} autoComplete="name" required /></label>}
      <label>Email<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} autoComplete="email" required /></label>
      <label>Password<input type="password" minLength="8" value={password} onChange={(event) => setPassword(event.target.value)} autoComplete={mode === 'register' ? 'new-password' : 'current-password'} required /></label>
      {message && <div className={`auth-notice ${success ? 'success' : 'error'}`}>{message}</div>}
      <button className="btn btn-primary auth-submit" disabled={busy || !firebaseConfigured}>{busy ? 'Please wait…' : mode === 'register' ? 'Create account' : 'Sign in'}</button>
    </form>
    <div className="auth-links"><button onClick={() => { setMode(mode === 'register' ? 'login' : 'register'); setMessage(''); }}>{mode === 'register' ? 'Already registered? Sign in' : 'New to Qismat? Create account'}</button>{mode === 'login' && <button onClick={reset}>Forgot password?</button>}</div>
  </section></div>;
}

export function MemberHome({ user, onLogout }) {
  const [status, setStatus] = useState(null);
  const [error, setError] = useState('');
  useEffect(() => { getOnboardingStatus().then(setStatus).catch((requestError) => setError(requestError.message)); }, []);
  const state = status?.moderation_status || 'draft';
  return <div className="member-shell"><header className="member-header"><strong>Qismat Connections</strong><div><span>{user.name}</span><button onClick={onLogout}>Sign out</button></div></header><main className="member-main">
    <section><span className="kicker">Your membership</span><h1>Welcome, {user.name.split(' ')[0]}.</h1><p>Your secure account is connected. Complete your profile to enter the moderation and discovery flow.</p>{error && <div className="auth-notice error">{error}</div>}<button className="btn btn-primary">{status?.required_fields_complete ? 'Review your profile' : 'Complete your profile'}</button></section>
    <aside className="member-status"><span>Profile status</span><strong>{state}</strong><ul><li className="done">Email verified</li><li className={status?.required_fields_complete ? 'done' : ''}>Required details complete</li><li className={state === 'approved' ? 'done' : ''}>Admin moderation approved</li><li className={status?.discoverable ? 'done' : ''}>Visible in discovery</li></ul></aside>
  </main></div>;
}
