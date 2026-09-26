import React, { useEffect, useState } from 'react';
import { getReports, resolveReport } from './api';
import './report-queue.css';

const labels = {
  fake_identity: 'Fake identity', commercial_use: 'Commercial use', scam: 'Scam or financial request',
  harassment: 'Harassment', inappropriate_content: 'Inappropriate content', underage_concern: 'Underage concern', other: 'Other',
};

export function ReportQueue({ onCountChange }) {
  const [reports, setReports] = useState([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(null);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true); setError('');
    try { const page = await getReports(); setReports(page.data || []); onCountChange?.(page.total || 0); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(); }, []);

  async function resolve(report, action) {
    const notes = window.prompt(action === 'suspended' ? 'Record why this member is being suspended:' : 'Record the investigation outcome:');
    if (!notes?.trim()) return;
    setBusy(report.id); setError('');
    try { await resolveReport(report.id, action, notes.trim()); await load(); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  return <section className="panel report-panel" id="reports"><div className="panel-heading"><div><span className="kicker">Trust and safety</span><h3>Open member reports</h3></div><button onClick={load} disabled={loading}>Refresh</button></div>
    {error && <div className="notice error">{error}</div>}
    {loading ? <p className="empty">Loading safety reports…</p> : reports.length === 0 ? <p className="empty">No open reports require review.</p> : <div className="report-list">{reports.map((report) => <article key={report.id}>
      <div><span className="report-reason">{labels[report.reason] || report.reason}</span><h4>{report.reported_user?.profile?.display_name || report.reported_user?.name}</h4><p>{report.details || 'No additional details were supplied.'}</p><small>Reported by {report.reporter?.profile?.profile_code || report.reporter?.name} · {new Date(report.created_at).toLocaleString()}</small></div>
      <div className="report-actions"><button disabled={busy === report.id} onClick={() => resolve(report, 'dismissed')}>Dismiss</button><button disabled={busy === report.id} onClick={() => resolve(report, 'resolved')}>Resolve</button><button className="suspend" disabled={busy === report.id} onClick={() => resolve(report, 'suspended')}>Suspend member</button></div>
    </article>)}</div>}
  </section>;
}
