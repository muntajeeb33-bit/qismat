import React, { useMemo, useState } from 'react';
import { submitProfile, updatePartnerPreferences, updateProfile } from './api';
import { PhotoManager } from './photos';
import './profile.css';

const initial = {
  display_name: '', gender: '', date_of_birth: '', height_cm: '', marital_status: '',
  religion: '', community: '', mother_tongue: '', country: '', state: '', city: '',
  education: '', occupation: '', company: '', annual_income: '', about_me: '',
  partner_expectations: '', visibility: 'members', family_type: '', family_values: '',
  father_occupation: '', mother_occupation: '', siblings: '', family_location: '', family_summary: '',
};

const preferenceInitial = {
  min_age: '', max_age: '', min_height_cm: '', max_height_cm: '', marital_statuses: '',
  religions: '', communities: '', mother_tongues: '', countries: '', cities: '',
  education_preferences: '', occupation_preferences: '', open_to_relocation: '', summary: '',
};

function profileForm(profile) {
  if (!profile) return initial;
  const family = profile.family_details || {};
  return {
    ...initial,
    ...Object.fromEntries(Object.keys(initial).map((key) => [key, profile[key] ?? ''])),
    date_of_birth: profile.date_of_birth?.slice(0, 10) || '',
    partner_expectations: profile.partner_expectations?.summary || '',
    family_type: family.family_type || '',
    family_values: family.family_values || '',
    father_occupation: family.father_occupation || '',
    mother_occupation: family.mother_occupation || '',
    siblings: family.siblings ?? '',
    family_location: family.family_location || '',
    family_summary: family.summary || '',
  };
}

function preferenceForm(preferences) {
  if (!preferences) return preferenceInitial;
  return Object.fromEntries(Object.keys(preferenceInitial).map((key) => {
    const value = preferences[key];
    if (key === 'open_to_relocation') return [key, value === null || value === undefined ? '' : value ? 'yes' : 'no'];
    return [key, Array.isArray(value) ? value.join(', ') : value ?? ''];
  }));
}

const optionalNumber = (value) => value === '' || value === null ? null : Number(value);
const commaList = (value) => value.split(',').map((item) => item.trim()).filter(Boolean);

export function ProfileEditor({ profile, preferences, status, onCancel, onComplete }) {
  const [form, setForm] = useState(() => profileForm(profile));
  const [preferenceFormState, setPreferenceFormState] = useState(() => preferenceForm(preferences));
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

  function changePreference(event) {
    setPreferenceFormState((current) => ({ ...current, [event.target.name]: event.target.value }));
  }

  async function save(shouldSubmit) {
    setBusy(true); setMessage('');
    try {
      const text = (value) => typeof value === 'string' ? value.trim() : value;
      const familyDetails = {
        family_type: text(form.family_type) || null,
        family_values: text(form.family_values) || null,
        father_occupation: text(form.father_occupation) || null,
        mother_occupation: text(form.mother_occupation) || null,
        siblings: optionalNumber(form.siblings),
        family_location: text(form.family_location) || null,
        summary: text(form.family_summary) || null,
      };
      const excluded = new Set(['family_type', 'family_values', 'father_occupation', 'mother_occupation', 'siblings', 'family_location', 'family_summary']);
      const payload = Object.fromEntries(Object.entries(form).filter(([key]) => !excluded.has(key)).map(([key, value]) => {
        const normalized = text(value);
        return [key, normalized === '' ? null : normalized];
      }));
      payload.partner_expectations = payload.partner_expectations ? { summary: payload.partner_expectations } : [];
      payload.family_details = familyDetails;
      payload.height_cm = optionalNumber(payload.height_cm);
      payload.annual_income = optionalNumber(payload.annual_income);

      const listFields = ['marital_statuses', 'religions', 'communities', 'mother_tongues', 'countries', 'cities', 'education_preferences', 'occupation_preferences'];
      const preferencePayload = Object.fromEntries(Object.entries(preferenceFormState).map(([key, value]) => {
        if (listFields.includes(key)) return [key, commaList(value)];
        if (['min_age', 'max_age', 'min_height_cm', 'max_height_cm'].includes(key)) return [key, optionalNumber(value)];
        if (key === 'open_to_relocation') return [key, value === '' ? null : value === 'yes'];
        return [key, text(value) || null];
      }));

      const [saved] = await Promise.all([updateProfile(payload), updatePartnerPreferences(preferencePayload)]);
      if (shouldSubmit) await submitProfile();
      await onComplete(saved, shouldSubmit ? 'Profile submitted for review.' : 'Profile and preferences saved.');
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
      </div></section>
      <section><h2>Education and career</h2><div className="form-grid">
        <label>Education<input name="education" value={form.education} onChange={change} maxLength="180" /></label>
        <label>Occupation<input name="occupation" value={form.occupation} onChange={change} maxLength="180" /></label>
        <label>Company or organization<input name="company" value={form.company} onChange={change} maxLength="180" /></label>
        <label>Annual income<input type="number" name="annual_income" value={form.annual_income} onChange={change} min="0" step="1" /></label>
      </div></section>
      <section><h2>Family</h2><div className="form-grid">
        <label>Family type<select name="family_type" value={form.family_type} onChange={change}><option value="">Select</option><option value="nuclear">Nuclear</option><option value="joint">Joint</option><option value="extended">Extended</option><option value="other">Other</option></select></label>
        <label>Family values<select name="family_values" value={form.family_values} onChange={change}><option value="">Select</option><option value="traditional">Traditional</option><option value="moderate">Moderate</option><option value="liberal">Liberal</option></select></label>
        <label>Father's occupation<input name="father_occupation" value={form.father_occupation} onChange={change} maxLength="180" /></label>
        <label>Mother's occupation<input name="mother_occupation" value={form.mother_occupation} onChange={change} maxLength="180" /></label>
        <label>Number of siblings<input type="number" name="siblings" value={form.siblings} onChange={change} min="0" max="20" /></label>
        <label>Family location<input name="family_location" value={form.family_location} onChange={change} maxLength="180" /></label>
        <label className="span-2">About your family<textarea name="family_summary" value={form.family_summary} onChange={change} maxLength="2000" rows="4" /><small>{form.family_summary.length}/2000</small></label>
      </div></section>
      <section><h2>About you</h2><div className="form-grid">
        <label className="span-2">Your story *<textarea name="about_me" value={form.about_me} onChange={change} maxLength="3000" rows="6" required /><small>{form.about_me.length}/3000</small></label>
        <label className="span-2">What you value in a partner<textarea name="partner_expectations" value={form.partner_expectations} onChange={change} maxLength="2000" rows="4" /></label>
      </div></section>
      <section><div className="section-intro"><div><h2>Partner preferences</h2><p>These details will help Qismat build relevant discovery and recommendations. Separate multiple choices with commas.</p></div><span>Private matching criteria</span></div><div className="form-grid">
        <label>Minimum age<input type="number" name="min_age" value={preferenceFormState.min_age} onChange={changePreference} min="18" max="100" /></label>
        <label>Maximum age<input type="number" name="max_age" value={preferenceFormState.max_age} onChange={changePreference} min="18" max="100" /></label>
        <label>Minimum height in cm<input type="number" name="min_height_cm" value={preferenceFormState.min_height_cm} onChange={changePreference} min="100" max="250" /></label>
        <label>Maximum height in cm<input type="number" name="max_height_cm" value={preferenceFormState.max_height_cm} onChange={changePreference} min="100" max="250" /></label>
        <label>Marital statuses<input name="marital_statuses" value={preferenceFormState.marital_statuses} onChange={changePreference} placeholder="Never married, Divorced" /></label>
        <label>Religions<input name="religions" value={preferenceFormState.religions} onChange={changePreference} placeholder="Islam" /></label>
        <label>Communities<input name="communities" value={preferenceFormState.communities} onChange={changePreference} /></label>
        <label>Mother tongues<input name="mother_tongues" value={preferenceFormState.mother_tongues} onChange={changePreference} /></label>
        <label>Countries<input name="countries" value={preferenceFormState.countries} onChange={changePreference} placeholder="United Kingdom, Pakistan" /></label>
        <label>Cities<input name="cities" value={preferenceFormState.cities} onChange={changePreference} /></label>
        <label>Education preferences<input name="education_preferences" value={preferenceFormState.education_preferences} onChange={changePreference} /></label>
        <label>Occupation preferences<input name="occupation_preferences" value={preferenceFormState.occupation_preferences} onChange={changePreference} /></label>
        <label>Open to relocation<select name="open_to_relocation" value={preferenceFormState.open_to_relocation} onChange={changePreference}><option value="">Prefer not to say</option><option value="yes">Yes</option><option value="no">No</option></select></label>
        <label className="span-2">Anything else that matters<textarea name="summary" value={preferenceFormState.summary} onChange={changePreference} maxLength="2000" rows="4" /></label>
      </div></section>
      <section><h2>Privacy</h2><div className="form-grid"><label className="span-2">Profile visibility<select name="visibility" value={form.visibility} onChange={change}><option value="members">Visible to eligible members after approval and discovery opt-in</option><option value="private">Private until you change this setting</option><option value="hidden">Hidden from discovery</option></select><small>Approval alone never makes your profile discoverable. You must also enter discovery.</small></label></div></section>
      <PhotoManager />
      <section className="profile-purpose"><h2>Truthful profiles protect everyone</h2><p>Qismat Connections is strictly for adults genuinely seeking marriage. False identities or information, scams, solicitation, and commercial use by marriage bureaus, agents, or businesses are prohibited. Violations may result in rejection, suspension, or removal, and suspected unlawful activity may be reported to the appropriate authorities.</p><label><input type="checkbox" checked={purposeConfirmed} onChange={(event) => setPurposeConfirmed(event.target.checked)} required /><span>I confirm that my profile is truthful and intended only for genuinely seeking marriage.</span></label></section>
      {message && <div className="auth-notice error">{message}</div>}
      <div className="profile-actions"><button type="button" className="btn profile-secondary" onClick={() => save(false)} disabled={busy}>{busy ? 'Saving…' : 'Save draft'}</button><button type="submit" className="btn btn-primary" disabled={busy}>{busy ? 'Please wait…' : 'Save and submit for review'}</button></div>
    </form>
  </main>;
}
