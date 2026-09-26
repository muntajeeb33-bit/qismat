import React, { useEffect, useState } from 'react';
import { blockMember, cancelInterest, getInterests, reportMember, respondToInterest } from './api';
import './interests.css';

export function Interests() {
  const [direction, setDirection] = useState('received');
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [reporting, setReporting] = useState(null);
  const [reportReason, setReportReason] = useState('harassment');
  const [reportDetails, setReportDetails] = useState('');

  async function load(nextDirection = direction) {
    setLoading(true); setError('');
    try { const page = await getInterests(nextDirection); setItems(page.data || []); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }

  useEffect(() => { load(direction); }, [direction]);

  async function act(item, action) {
    setBusy(item.id); setError(''); setMessage('');
    try {
      if (action === 'cancel') await cancelInterest(item.id);
      else await respondToInterest(item.id, action);
      setMessage(action === 'accepted' ? `You and ${item.member.display_name} can now connect.` : `Interest ${action}.`);
      await load();
    } catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  async function block(item) {
    if (!window.confirm(`Block ${item.member.display_name}? You will no longer see or contact each other.`)) return;
    setBusy(item.id); setError(''); setMessage('');
    try { await blockMember(item.member.user_id, 'Blocked from interests'); setMessage('Member blocked.'); await load(); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  async function report(item) {
    setBusy(item.id); setError(''); setMessage('');
    try { await reportMember(item.member.user_id, reportReason, reportDetails.trim() || null); setMessage('Your confidential report was submitted.'); setReporting(null); setReportDetails(''); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  return <main className="interests-page">
    <div className="interests-heading"><div><span className="kicker">Connections</span><h1>Your interests</h1></div><p>Review requests thoughtfully. Communication becomes available only after an interest is accepted.</p></div>
    <div className="interest-tabs"><button className={direction === 'received' ? 'active' : ''} onClick={() => setDirection('received')}>Received</button><button className={direction === 'sent' ? 'active' : ''} onClick={() => setDirection('sent')}>Sent</button></div>
    {message && <div className="auth-notice success">{message}</div>}{error && <div className="auth-notice error">{error}</div>}
    {loading ? <p className="interest-empty">Loading interests…</p> : items.length === 0 ? <p className="interest-empty">No {direction} interests yet.</p> : <div className="interest-list">{items.map((item) => <article key={item.id}>
      <div className="interest-avatar">{(item.member.display_name || '?').slice(0, 1).toUpperCase()}</div>
      <div className="interest-copy"><span className={`interest-status ${item.status}`}>{item.status}</span><h2>{item.member.display_name}</h2><p>{[item.member.city, item.member.country, item.member.occupation].filter(Boolean).join(' · ') || item.member.profile_code}</p>{item.message && <blockquote>{item.message}</blockquote>}<small>{new Date(item.created_at).toLocaleDateString()}</small></div>
      <div className="interest-actions">{item.direction === 'received' && item.status === 'pending' && <><button className="accept" disabled={busy === item.id} onClick={() => act(item, 'accepted')}>Accept</button><button disabled={busy === item.id} onClick={() => act(item, 'declined')}>Decline</button></>}{item.direction === 'sent' && item.status === 'pending' && <button disabled={busy === item.id} onClick={() => act(item, 'cancel')}>Cancel request</button>}<button disabled={busy === item.id} onClick={() => setReporting(reporting === item.id ? null : item.id)}>Report</button><button className="danger" disabled={busy === item.id} onClick={() => block(item)}>Block</button></div>
      {reporting === item.id && <div className="interest-report"><strong>Confidential report</strong><label>Reason<select value={reportReason} onChange={(event) => setReportReason(event.target.value)}><option value="fake_identity">Fake identity</option><option value="commercial_use">Marriage bureau or commercial use</option><option value="scam">Scam or financial request</option><option value="harassment">Harassment</option><option value="inappropriate_content">Inappropriate content</option><option value="underage_concern">Underage concern</option><option value="other">Other safety concern</option></select></label><label>Details<textarea maxLength="1000" value={reportDetails} onChange={(event) => setReportDetails(event.target.value)} placeholder="What happened?" /></label><button className="accept" disabled={busy === item.id} onClick={() => report(item)}>Submit report</button></div>}
    </article>)}</div>}
  </main>;
}
