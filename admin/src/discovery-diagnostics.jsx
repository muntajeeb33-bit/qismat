import React, { useEffect, useState } from 'react';
import { getDiscoveryDiagnostics } from './api';
import './discovery-diagnostics.css';

export function DiscoveryDiagnostics() {
  const [data, setData] = useState(null);
  const [query, setQuery] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  async function load(term = query) {
    setLoading(true); setError('');
    try { setData(await getDiscoveryDiagnostics(term)); }
    catch (requestError) { setError(requestError.message); }
    finally { setLoading(false); }
  }
  useEffect(() => { load(''); }, []);

  const profiles = data?.profiles?.data || [];
  return <section className="panel discovery-diagnostics"><div className="panel-heading"><div><span className="kicker">Discovery health</span><h3>Member visibility diagnostics</h3></div><form onSubmit={(event) => { event.preventDefault(); load(); }}><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Name, email or profile code" /><button>Search</button></form></div>
    {error && <div className="notice error">{error}</div>}
    <div className="diagnostic-cards"><article><span>Approved and opted in</span><strong>{data?.summary?.approved_and_opted_in ?? '—'}</strong></article><article><span>Visible with approved photo</span><strong>{data?.summary?.visible_with_approved_photo ?? '—'}</strong></article><article><span>Missing approved primary photo</span><strong>{data?.summary?.missing_approved_primary_photo ?? '—'}</strong></article></div>
    {loading ? <p className="empty">Checking discovery eligibility…</p> : profiles.length === 0 ? <p className="empty">No profiles found.</p> : <div className="diagnostic-table"><div className="diagnostic-head"><span>Member</span><span>Moderation</span><span>Discovery</span><span>Eligibility</span></div>{profiles.map((profile) => <div className="diagnostic-row" key={profile.id}><span><b>{profile.display_name}</b><small>{profile.profile_code || 'No code'} · {profile.email}</small></span><span>{profile.moderation_status}</span><span>{profile.discovery_opt_in ? 'Opted in' : 'Paused'}</span><span className={profile.eligible ? 'eligible' : 'ineligible'}>{profile.eligible ? 'Eligible' : profile.issues.join(' · ')}</span></div>)}</div>}
  </section>;
}
