import React, { useMemo, useState } from 'react';
import { submitProfile, updateProfile } from './api';
import './profile.css';

const initial = {
  display_name: '', gender: '', date_of_birth: '', height_cm: '', marital_status: '',
  religion: '', community: '', mother_tongue: '', country: '', state: '', city: '',
  education: '', occupation: '', about_me: '', partner_expectations: '',
};

function profileForm(profile) {
  if (!profile) return initial;
  return {
    ...initial,
    ...Object.fromEntries(Object.keys(initial).map((key) => [key, profile[key] ?? ''])),
    date_of_birth: profile.date_of_birth?.slice(0, 10) || '',
    partner_expectations: profile.partner_expectations?.summary || '',
  };
}

export function ProfileEditor({ profile, status, onCancel, onComplete }) {
  const [form, setForm] = useState(() => profileForm(profile));
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [purposeConfirmed, setPurposeConfirmed] = useState(false);
  const maxBirthDate = useMemo(() => {
    const date = new Date(); date.setFullYear(date.getFullYear() - 18);
    return date.toISOString().slice(0, 10);
  }, []);

  function change(event) {
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
  }

  async function save(shouldSubmit) {
    setBusy(true); setMessage('');
    try {
      const payload = Object.fromEntries(Object.entries(form).map(([key, value]) => {
        const normalized = typeof value === 'string' ? value.trim() : value;
        return [key, normalized === '' ? null : normalized];
      }));
      payload.partner_expectations = payload.partner_expectations ? { summary: payload.partner_expectations } : [];
      if (payload.height_cm) payload.height_cm = Number(payload.height_cm);
      const saved = await updateProfile(payload);
      if (shouldSubmit) await submitProfile();
      await onComplete(saved, shouldSubmit ? 'Profile submitted for review.' : 'Draft saved.');
    } catch (error) { setMessage(error.message || 'Unable to save your profile.'); }
    finally { setBusy(false); }
  }

  return <main className="profile-page"><div className="profile-heading"><div><span className="kicker">Member profile</span><h1>Tell your story.</h1><p>Required fields are marked. Your profile remains private until it is approved and you choose to enter discovery.</p></div><button className="profile-back" onClick={onCancel}>Back to overview</button></div>
    {['approved', 'pending'].includes(status?.moderation_status) && <div className="profile-warning">Editing reviewed information returns the profile to draft and pauses discovery until another approval.</div>}
    {status?.moderation_status === 'rejected' && status.moderation_feedback && <div className="profile-warning rejection"><strong>Reviewer feedback</strong>{status.moderation_feedback}</div>}
    <form className="profile-form" onSubmit={(event) => { event.preventDefault(); save(true); }}>
      <section><h2>Essentials</h2><div className="form-grid">
        <label className="span-2">Display name *<input name="display_name" value={form.display_name} onChange={change} maxLength="120" required /></label>
        <label>Date of birth *<input type="date" name="date_of_birth" value={form.date_of_birth} onChange={change} max={maxBirthDate} required /></label>
        <label>Gender<select name="gender" value={form.gender} onChange={change}><option value="">Select</option><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select></label>
        <label>Marital status<select name="marital_status" value={form.marital_status} onChange={change}><option value="">Select</option><option>Never married</option><option>Divorced</option><option>Widowed</option><option>Separated</option></select></label>
        <label>Height in cm<input type="number" name="height_cm" value={form.height_cm} onChange={change} min="100" max="250" /></label>
      </div></section>
      <section><h2>Location and background</h2><div className="form-grid">
        <label>Country *<input name="country" value={form.country} onChange={change} maxLength="80" required /></label>
        <label>State or province<input name="state" value={form.state} onChange={change} maxLength="80" /></label>
        <label>City *<input name="city" value={form.city} onChange={change} maxLength="80" required /></label>
        <label>Religion<input name="religion" value={form.religion} onChange={change} maxLength="80" /></label>
        <label>Community<input name="community" value={form.community} onChange={change} maxLength="100" /></label>
        <label>Mother tongue<input name="mother_tongue" value={form.mother_tongue} onChange={change} maxLength="80" /></label>
        <label>Education<input name="education" value={form.education} onChange={change} maxLength="180" /></label>
        <label>Occupation<input name="occupation" value={form.occupation} onChange={change} maxLength="180" /></label>
      </div></section>
      <section><h2>About you</h2><div className="form-grid">
        <label className="span-2">Your story *<textarea name="about_me" value={form.about_me} onChange={change} maxLength="3000" rows="6" required /><small>{form.about_me.length}/3000</small></label>
        <label className="span-2">What you value in a partner<textarea name="partner_expectations" value={form.partner_expectations} onChange={change} rows="4" /></label>
      </div></section>
      <section className="profile-purpose"><h2>Truthful profiles protect everyone</h2><p>Qismat Connections is strictly for adults genuinely seeking marriage. False identities or information, scams, solicitation, and commercial use by marriage bureaus, agents, or businesses are prohibited. Violations may result in rejection, suspension, or removal, and suspected unlawful activity may be reported to the appropriate authorities.</p><label><input type="checkbox" checked={purposeConfirmed} onChange={(event) => setPurposeConfirmed(event.target.checked)} required /><span>I confirm that my profile is truthful and intended only for genuinely seeking marriage.</span></label></section>
      {message && <div className="auth-notice error">{message}</div>}
      <div className="profile-actions"><button type="button" className="btn profile-secondary" onClick={() => save(false)} disabled={busy}>{busy ? 'Saving…' : 'Save draft'}</button><button type="submit" className="btn btn-primary" disabled={busy}>{busy ? 'Please wait…' : 'Save and submit for review'}</button></div>
    </form>
  </main>;
}
