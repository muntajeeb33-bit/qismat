import React, { useEffect, useState } from 'react';
import { blockMember, deleteMessage, getConversations, getMessages, markConversationRead, reportMember, sendMessage } from './api';
import './messages.css';

export function Messages() {
  const [conversations, setConversations] = useState([]);
  const [selected, setSelected] = useState(null);
  const [messages, setMessages] = useState([]);
  const [body, setBody] = useState('');
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [reportOpen, setReportOpen] = useState(false);
  const [reportReason, setReportReason] = useState('harassment');
  const [reportDetails, setReportDetails] = useState('');

  async function loadConversations() {
    try { setError(''); setConversations(await getConversations()); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }

  async function open(conversation, quiet = false) {
    if (!quiet) { setSelected(conversation); setReportOpen(false); setNotice(''); }
    try {
      const page = await getMessages(conversation.id);
      setMessages([...(page.data || [])].reverse());
      await markConversationRead(conversation.id);
      await loadConversations();
    } catch (requestError) { setError(requestError.message); if (requestError.message.includes('not found')) setSelected(null); }
  }

  useEffect(() => { loadConversations(); }, []);
  useEffect(() => {
    if (!selected) return undefined;
    const timer = window.setInterval(() => open(selected, true), 15000);
    return () => window.clearInterval(timer);
  }, [selected?.id]);

  async function send(event) {
    event.preventDefault();
    if (!body.trim() || !selected) return;
    setBusy(true); setError('');
    try { await sendMessage(selected.id, body.trim()); setBody(''); await open(selected, true); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(false); }
  }

  async function remove(message) {
    if (!window.confirm('Delete this message for both members?')) return;
    try { await deleteMessage(message.id); await open(selected, true); }
    catch (requestError) { setError(requestError.message); }
  }

  async function block() {
    if (!window.confirm(`Block ${selected.member.display_name}? This conversation will close immediately.`)) return;
    try { await blockMember(selected.member.user_id, 'Blocked from conversation'); setSelected(null); setMessages([]); setNotice('Member blocked and conversation closed.'); await loadConversations(); }
    catch (requestError) { setError(requestError.message); }
  }

  async function report() {
    setBusy(true); setError('');
    try { await reportMember(selected.member.user_id, reportReason, reportDetails.trim() || null); setNotice('Your confidential report was submitted.'); setReportOpen(false); setReportDetails(''); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(false); }
  }

  return <main className="messages-page"><div className="messages-heading"><div><span className="kicker">Mutual connections</span><h1>Messages</h1></div><p>Conversations are available only after an interest is accepted. Never send money or share financial credentials.</p></div>
    {notice && <div className="auth-notice success">{notice}</div>}{error && <div className="auth-notice error">{error}</div>}
    <div className="messenger"><aside className="conversation-list">{loading ? <p>Loading…</p> : conversations.length === 0 ? <p>No mutual connections yet.</p> : conversations.map((conversation) => <button key={conversation.id} className={selected?.id === conversation.id ? 'active' : ''} onClick={() => open(conversation)}><span>{(conversation.member.display_name || '?').slice(0, 1)}</span><div><strong>{conversation.member.display_name}</strong><small>{conversation.latest_message?.deleted ? 'Message deleted' : conversation.latest_message?.body || 'You can now start a conversation.'}</small></div>{conversation.unread_count > 0 && <b>{conversation.unread_count}</b>}</button>)}</aside>
      <section className="message-thread">{!selected ? <div className="thread-empty">Choose a mutual connection to begin.</div> : <><header><div><strong>{selected.member.display_name}</strong><small>{selected.member.profile_code}</small></div><div><button onClick={() => setReportOpen(!reportOpen)}>Report</button><button className="danger" onClick={block}>Block</button></div></header>{reportOpen && <div className="thread-report"><label>Reason<select value={reportReason} onChange={(event) => setReportReason(event.target.value)}><option value="fake_identity">Fake identity</option><option value="commercial_use">Commercial use</option><option value="scam">Scam or financial request</option><option value="harassment">Harassment</option><option value="inappropriate_content">Inappropriate content</option><option value="underage_concern">Underage concern</option><option value="other">Other concern</option></select></label><label>Details<textarea maxLength="1000" value={reportDetails} onChange={(event) => setReportDetails(event.target.value)} /></label><button onClick={report} disabled={busy}>Submit confidential report</button></div>}<div className="message-list">{messages.length === 0 ? <p>Send a respectful first message when you are ready.</p> : messages.map((message) => <article key={message.id} className={message.mine ? 'mine' : 'theirs'}><div>{message.deleted ? <em>Message deleted</em> : message.body}</div><small>{new Date(message.created_at).toLocaleString()}{message.mine && message.read_at ? ' · Read' : ''}</small>{message.mine && !message.deleted && <button onClick={() => remove(message)}>Delete</button>}</article>)}</div><form className="message-compose" onSubmit={send}><textarea value={body} onChange={(event) => setBody(event.target.value)} maxLength="1000" placeholder="Write a respectful message…" required /><button disabled={busy || !body.trim()}>{busy ? 'Sending…' : 'Send'}</button></form></>}</section>
    </div>
  </main>;
}
