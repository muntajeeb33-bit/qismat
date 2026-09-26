import React, { useEffect, useState } from 'react';
import { deleteAccount, getBlockedMembers, getNotificationPreferences, logoutAllApi, session, unblockMember, updateNotificationPreferences } from './api';
import { firebaseLogout } from './firebase';
import './account.css';

const labels = {
  email_new_interest: 'New interests by email',
  email_interest_accepted: 'Accepted interests by email',
  email_new_message: 'New messages by email',
  email_moderation_updates: 'Profile and photo decisions by email',
  email_product_updates: 'Occasional product news by email',
  push_new_interest: 'New interests by push notification',
  push_interest_accepted: 'Accepted interests by push notification',
  push_new_message: 'New messages by push notification',
  push_moderation_updates: 'Profile and photo decisions by push notification',
};

export function AccountSettings({ onSignedOut }) {
  const [preferences, setPreferences] = useState(null);
  const [blocks, setBlocks] = useState([]);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [deleteText, setDeleteText] = useState('');

  async function load() {
    setError('');
    try {
      const [nextPreferences, blocked] = await Promise.all([getNotificationPreferences(), getBlockedMembers()]);
      setPreferences(nextPreferences);
      setBlocks(blocked.data || []);
    } catch (requestError) { setError(requestError.message); }
  }

  useEffect(() => { load(); }, []);

  async function save() {
    setBusy(true); setError(''); setMessage('');
    try { setPreferences(await updateNotificationPreferences(preferences)); setMessage('Notification preferences saved.'); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(false); }
  }

  async function removeBlock(member) {
    setBusy(true); setError('');
    try { await unblockMember(member.user_id); setBlocks((items) => items.filter((item) => item.user_id !== member.user_id)); setMessage(`${member.display_name} was unblocked.`); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(false); }
  }

  async function logoutEverywhere() {
    setBusy(true); setError('');
    try { await logoutAllApi(); session.clear(); await firebaseLogout(); onSignedOut(); }
    catch (requestError) { setError(requestError.message); setBusy(false); }
  }

  async function removeAccount() {
    if (deleteText !== 'DELETE') return;
    setBusy(true); setError('');
    try { await deleteAccount(); session.clear(); await firebaseLogout(); onSignedOut(); }
    catch (requestError) { setError(requestError.message); setBusy(false); }
  }

  return <main className="account-page">
    <div className="account-heading"><span className="kicker">Privacy and account</span><h1>Your settings.</h1><p>Choose how Qismat contacts you and manage access to your account.</p></div>
    {message && <div className="auth-notice success">{message}</div>}{error && <div className="auth-notice error">{error}</div>}
    <section><h2>Notifications</h2><p>Safety and account security messages may still be sent when necessary.</p>{!preferences ? <p>Loading preferences…</p> : <div className="preference-list">{Object.entries(labels).map(([key, label]) => <label key={key}><span>{label}</span><input type="checkbox" checked={Boolean(preferences[key])} onChange={(event) => setPreferences({ ...preferences, [key]: event.target.checked })} /></label>)}</div>}<button className="btn btn-primary" onClick={save} disabled={busy || !preferences}>Save preferences</button></section>
    <section><h2>Blocked members</h2><p>Blocked members cannot discover, contact, or interact with you.</p>{blocks.length === 0 ? <div className="account-empty">You have no blocked members.</div> : <div className="blocked-list">{blocks.map((member) => <div key={member.user_id}><span><strong>{member.display_name}</strong><small>{member.profile_code || 'Profile unavailable'}</small></span><button onClick={() => removeBlock(member)} disabled={busy}>Unblock</button></div>)}</div>}</section>
    <section><h2>Sessions</h2><p>Use this if you signed in on a shared device or believe someone else accessed your account.</p><button className="btn account-secondary" onClick={logoutEverywhere} disabled={busy}>Sign out on every device</button></section>
    <section className="danger-zone"><h2>Delete account</h2><p>This removes your member profile, photos, preferences, connections, messages, and active sessions. Safety records may be retained where required to protect members or meet legal obligations.</p><label>Type <strong>DELETE</strong> to confirm<input value={deleteText} onChange={(event) => setDeleteText(event.target.value)} autoComplete="off" /></label><button onClick={removeAccount} disabled={busy || deleteText !== 'DELETE'}>Permanently delete my account</button></section>
  </main>;
}
