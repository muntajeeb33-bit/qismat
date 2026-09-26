import React, { useEffect, useState } from 'react';
import { getMembers, updateMember } from './api';
import './member-directory.css';

export function MemberDirectory() {
  const [members, setMembers] = useState([]);
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(null);
  const [error, setError] = useState('');

  async function load() {
    setLoading(true); setError('');
    try { const page = await getMembers({ q: query.trim(), status }); setMembers(page.data || []); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(); }, []);

  async function change(member, changes, label) {
    const reason = window.prompt(`Reason for ${label}:`);
    if (!reason?.trim()) return;
    setBusy(member.id); setError('');
    try { await updateMember(member.id, { ...changes, reason: reason.trim() }); await load(); }
    catch (requestError) { setError(requestError.message); }
    finally { setBusy(null); }
  }

  return <section className="panel member-directory" id="members"><div className="panel-heading"><div><span className="kicker">Member operations</span><h3>Member directory</h3></div><form onSubmit={(event) => { event.preventDefault(); load(); }}><input aria-label="Search members" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Name, email or profile code" /><select aria-label="Account status" value={status} onChange={(event) => setStatus(event.target.value)}><option value="">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option><option value="deleted">Deleted</option></select><button disabled={loading}>Search</button></form></div>
    {error && <div className="notice error">{error}</div>}
    {loading ? <p className="empty">Loading members…</p> : members.length === 0 ? <p className="empty">No members match this search.</p> : <div className="member-table">{members.map((member) => <article key={member.id}><div className="member-identity"><strong>{member.profile?.display_name || member.name}</strong><small>{member.email}</small><small>{member.profile?.profile_code || 'Profile not started'} · {member.profile?.city || 'Location unavailable'}{member.profile?.country ? `, ${member.profile.country}` : ''}</small></div><div className="member-badges"><span className={`status ${member.status}`}>{member.status}</span><span>{member.profile?.moderation_status || 'no profile'}</span><span>{member.profile?.verification_status || 'unverified'}</span><small>{member.reports_received_count || 0} reports received</small></div><div className="member-actions">{member.status === 'active' ? <button className="danger" disabled={busy === member.id} onClick={() => change(member, { status: 'suspended' }, 'suspension')}>Suspend</button> : member.status === 'suspended' ? <button disabled={busy === member.id} onClick={() => change(member, { status: 'active' }, 'reactivation')}>Reactivate</button> : null}{member.profile && <select aria-label={`Verification for ${member.name}`} value={member.profile.verification_status || 'unverified'} disabled={busy === member.id || member.status === 'deleted'} onChange={(event) => change(member, { verification_status: event.target.value }, 'verification change')}><option value="unverified">Unverified</option><option value="reviewed">Reviewed</option><option value="identity_verified">Identity verified</option></select>}</div></article>)}</div>}
  </section>;
}
