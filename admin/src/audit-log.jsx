import React, { useEffect, useState } from 'react';
import { getAuditLogs } from './api';
import './audit-log.css';

export function AuditLog() {
  const [logs, setLogs] = useState([]);
  const [action, setAction] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true); setError('');
    try { const page = await getAuditLogs(action.trim()); setLogs(page.data || []); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(); }, []);

  return <section className="panel audit-log" id="audit"><div className="panel-heading"><div><span className="kicker">Accountability</span><h3>Administrator audit log</h3></div><form onSubmit={(event) => { event.preventDefault(); load(); }}><input value={action} onChange={(event) => setAction(event.target.value)} placeholder="Filter action" /><button disabled={loading}>Filter</button></form></div>{error && <div className="notice error">{error}</div>}{loading ? <p className="empty">Loading audit history…</p> : logs.length === 0 ? <p className="empty">No audit records match this filter.</p> : <div className="audit-table">{logs.map((log) => <article key={log.id}><div><strong>{log.action.replaceAll('.', ' ')}</strong><small>{log.admin?.name || 'Administrator'} · {new Date(log.created_at).toLocaleString()}</small></div><span>{log.target_type || 'record'} #{log.target_id || '—'}</span><p>{log.new_values?.reason || log.new_values?.resolution_notes || log.new_values?.moderation_feedback || 'Recorded administrative change'}</p></article>)}</div>}</section>;
}
