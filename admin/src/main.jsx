import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { exchangeFirebaseToken, getCurrentUser, getDashboard, getPendingPhotos, getPendingProfiles, logoutApi, reviewPhoto, reviewProfile, session } from './api';
import { firebaseConfigured, firebaseLogin, firebaseLogout, firebaseResetPassword } from './firebase';
import { PhotoQueue } from './photo-queue';
import { DiscoveryDiagnostics } from './discovery-diagnostics';
import { ReportQueue } from './report-queue';
import { MemberDirectory } from './member-directory';
import { AuditLog } from './audit-log';
import './styles.css';
import './branding.css';

const emptyStats = { registered_users: 0, active_profiles: 0, pending_verification: 0, pending_photos: 0, open_reports: 0 };

function Login({ onAuthenticated }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');

  async function submit(event) {
    event.preventDefault();
    setBusy(true); setMessage('');
    try {
      const { idToken } = await firebaseLogin(email, password);
      const data = await exchangeFirebaseToken(idToken);
      if (data.user.role !== 'admin') {
        await logoutApi();
        throw new Error('This account does not have administrator access.');
      }
      onAuthenticated(data.user);
    } catch (error) {
      setMessage(error.message || 'Unable to sign in.');
    } finally { setBusy(false); }
  }

  async function resetPassword() {
    if (!email) return setMessage('Enter your admin email first.');
    setBusy(true); setMessage('');
    try {
      await firebaseResetPassword(email);
      setMessage('Password reset email sent.');
    } catch (error) { setMessage(error.message || 'Unable to send reset email.'); }
    finally { setBusy(false); }
  }

  return <main className="login-page"><section className="login-card">
    <img className="login-logo" src="/assets/qismat-connections-logo.png" alt="Qismat Connections" /><span className="kicker">Qismat operations</span><h1>Admin sign in</h1>
    <p>Use an active Qismat administrator account. Every moderation decision is recorded.</p>
    {!firebaseConfigured && <div className="notice error">Firebase environment settings are missing.</div>}
    <form onSubmit={submit}>
      <label>Email<input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required /></label>
      <label>Password<input type="password" value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" required /></label>
      {message && <div className={`notice ${message.includes('sent') ? 'success' : 'error'}`}>{message}</div>}
      <button className="primary" disabled={busy || !firebaseConfigured}>{busy ? 'Please wait…' : 'Sign in securely'}</button>
      <button className="text-button" type="button" onClick={resetPassword} disabled={busy}>Forgot password?</button>
    </form>
  </section></main>;
}

function Dashboard({ user, onLogout }) {
  const [stats, setStats] = useState(emptyStats);
  const [profiles, setProfiles] = useState([]);
  const [photos, setPhotos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [reviewing, setReviewing] = useState(null);
  const [reviewingPhoto, setReviewingPhoto] = useState(null);

  async function refresh() {
    setLoading(true); setError('');
    try {
      const [dashboard, queue, photoQueue] = await Promise.all([getDashboard(), getPendingProfiles(), getPendingPhotos()]);
      setStats(dashboard); setProfiles(queue.data || []); setPhotos(photoQueue.data || []);
    } catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { refresh(); }, []);

  async function review(profile, decision) {
    const reason = decision === 'rejected' ? window.prompt('Give the member a clear reason for rejection:') : '';
    if (decision === 'rejected' && !reason?.trim()) return;
    setReviewing(profile.id); setError('');
    try { await reviewProfile(profile.id, decision, reason); await refresh(); }
    catch (requestError) { setError(requestError.message); }
    finally { setReviewing(null); }
  }

  async function reviewPendingPhoto(photo, decision) {
    const reason = decision === 'rejected' ? window.prompt('Give the member a clear reason for rejecting this photo:') : '';
    if (decision === 'rejected' && !reason?.trim()) return;
    setReviewingPhoto(photo.id); setError('');
    try { await reviewPhoto(photo.id, decision, reason); await refresh(); }
    catch (requestError) { setError(requestError.message); }
    finally { setReviewingPhoto(null); }
  }

  const cards = [['Registered users', stats.registered_users], ['Active profiles', stats.active_profiles], ['Pending profiles', stats.pending_verification], ['Pending photos', stats.pending_photos], ['Open reports', stats.open_reports]];
  return <div className="shell">
    <aside className="sidebar"><div><h2>Qismat</h2><b>Admin</b></div><nav><a className="active" href="#overview">Overview</a><a href="#members">Members</a><a href="#photos">Verification</a><a href="#reports">Reports</a><a href="#audit">Audit log</a></nav><small>Secure operations console</small></aside>
    <main className="workspace">
      <header id="overview"><div><span className="kicker">Operations</span><h1>Dashboard</h1></div><div className="account"><div><b>{user.name}</b><small>{user.email}</small></div><button onClick={onLogout}>Sign out</button></div></header>
      {error && <div className="notice error">{error}</div>}
      <section className="cards">{cards.map(([label, value]) => <article key={label}><span>{label}</span><strong>{loading ? '—' : value}</strong></article>)}</section>
      <section className="panel"><div className="panel-heading"><div><span className="kicker">Moderation queue</span><h3>Profiles awaiting review</h3></div><button onClick={refresh} disabled={loading}>Refresh</button></div>
        {loading ? <p className="empty">Loading moderation queue…</p> : profiles.length === 0 ? <p className="empty">No profiles are waiting for review.</p> : <div className="profile-list">{profiles.map((profile) => <article className="profile-row" key={profile.id}>
          <div className="avatar">{(profile.display_name || profile.user.name || '?').slice(0, 1).toUpperCase()}</div>
          <div className="profile-main"><h4>{profile.display_name || profile.user.name}</h4><p>{profile.city || 'City not provided'}{profile.country ? `, ${profile.country}` : ''} · {profile.occupation || 'Occupation not provided'}</p><p>{[profile.religion, profile.denomination, profile.community, profile.sub_community, profile.ethnicity].filter(Boolean).join(' · ') || 'Faith and background not provided'}</p><small>{profile.profile_code || 'No profile code'} · Submitted {profile.submitted_at ? new Date(profile.submitted_at).toLocaleDateString() : 'recently'}</small></div>
          <div className="profile-actions"><button className="reject" onClick={() => review(profile, 'rejected')} disabled={reviewing === profile.id}>Reject</button><button className="approve" onClick={() => review(profile, 'approved')} disabled={reviewing === profile.id}>Approve</button></div>
        </article>)}</div>}
      </section>
      <section className="panel" id="photos"><div className="panel-heading"><div><span className="kicker">Photo safety</span><h3>Photos awaiting review</h3></div><span className="queue-count">{photos.length} pending</span></div>
        {loading ? <p className="empty">Loading photo queue…</p> : <PhotoQueue photos={photos} reviewing={reviewingPhoto} onReview={reviewPendingPhoto} />}
      </section>
      <ReportQueue onCountChange={(count) => setStats((current) => ({ ...current, open_reports: count }))} />
      <DiscoveryDiagnostics />
      <MemberDirectory />
      <AuditLog />
    </main>
  </div>;
}

function App() {
  const [user, setUser] = useState(null);
  const [restoring, setRestoring] = useState(Boolean(session.get()));
  useEffect(() => {
    if (!session.get()) return;
    getCurrentUser().then((value) => {
      if (value.role !== 'admin') throw new Error('Administrator access is required.');
      setUser(value);
    }).catch(() => session.clear()).finally(() => setRestoring(false));
  }, []);
  async function logout() { await Promise.allSettled([logoutApi(), firebaseLogout()]); setUser(null); }
  if (restoring) return <main className="login-page"><div className="loading">Restoring secure session…</div></main>;
  return user ? <Dashboard user={user} onLogout={logout} /> : <Login onAuthenticated={setUser} />;
}

createRoot(document.getElementById('root')).render(<App />);
