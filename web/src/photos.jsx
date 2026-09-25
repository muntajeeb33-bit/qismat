import React, { useEffect, useRef, useState } from 'react';
import {
  deleteProfilePhoto,
  getProfilePhotoBlob,
  getProfilePhotos,
  reorderProfilePhotos,
  updateProfilePhoto,
  uploadProfilePhoto,
} from './api';
import './photos.css';

export function PhotoManager() {
  const [photos, setPhotos] = useState([]);
  const [previews, setPreviews] = useState({});
  const [visibility, setVisibility] = useState('members');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [failed, setFailed] = useState(false);
  const previewRef = useRef({});

  function replacePreviews(next) {
    Object.values(previewRef.current).forEach((url) => URL.revokeObjectURL(url));
    previewRef.current = next;
    setPreviews(next);
  }

  async function refresh(active = () => true) {
    const nextPhotos = await getProfilePhotos();
    const entries = await Promise.all(nextPhotos.map(async (photo) => {
      const blob = await getProfilePhotoBlob(photo.content_url);
      return [photo.id, URL.createObjectURL(blob)];
    }));
    if (!active()) {
      entries.forEach(([, url]) => URL.revokeObjectURL(url));
      return;
    }
    setPhotos(nextPhotos);
    replacePreviews(Object.fromEntries(entries));
  }

  useEffect(() => {
    let mounted = true;
    refresh(() => mounted).catch((error) => {
      setFailed(true);
      setMessage(error.message);
    });
    return () => {
      mounted = false;
      Object.values(previewRef.current).forEach((url) => URL.revokeObjectURL(url));
      previewRef.current = {};
    };
  }, []);

  async function run(action, successMessage) {
    setBusy(true); setMessage(''); setFailed(false);
    try {
      await action();
      await refresh();
      setMessage(successMessage);
    } catch (error) {
      setFailed(true);
      setMessage(error.message || 'The photo change could not be completed.');
    } finally {
      setBusy(false);
    }
  }

  async function upload(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;
    await run(() => uploadProfilePhoto(file, visibility), 'Photo uploaded and waiting for review.');
  }

  function move(photoId, direction) {
    const index = photos.findIndex((photo) => photo.id === photoId);
    const target = index + direction;
    if (index < 0 || target < 0 || target >= photos.length) return;
    const ids = photos.map((photo) => photo.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];
    run(() => reorderProfilePhotos(ids), 'Photo order updated.');
  }

  function remove(photo) {
    if (!window.confirm('Delete this photo permanently?')) return;
    run(() => deleteProfilePhoto(photo.id), 'Photo deleted.');
  }

  return <section className="photo-manager">
    <div className="section-intro"><div><h2>Your photos</h2><p>Add up to six clear, recent photos. Qismat removes embedded metadata and keeps every file private until an authorized request is approved.</p></div><span>{photos.length}/6 photos</span></div>
    <div className="photo-upload">
      <label>Who may see this photo?<select value={visibility} onChange={(event) => setVisibility(event.target.value)} disabled={busy}>
        <option value="members">Eligible Qismat members</option>
        <option value="matches">Accepted connections only</option>
        <option value="private">Only me and moderators</option>
        <option value="hidden">Hidden</option>
      </select></label>
      <label className={'photo-upload-button ' + (busy || photos.length >= 6 ? 'disabled' : '')}>
        {busy ? 'Please wait…' : 'Choose photo'}
        <input type="file" accept="image/jpeg,image/png,image/webp" onChange={upload} disabled={busy || photos.length >= 6} />
      </label>
      <small>JPEG, PNG or WebP · 400–4096 px · maximum 8 MB</small>
    </div>
    {photos.length === 0 ? <p className="photo-empty">No photos yet. Your first upload becomes your primary photo and is sent for moderation.</p> : <div className="photo-grid">
      {photos.map((photo, index) => <article className="photo-card" key={photo.id}>
        <div className="photo-preview">{previews[photo.id] ? <img src={previews[photo.id]} alt={'Profile photo ' + (index + 1)} /> : <span>Loading…</span>}{photo.is_primary && <b>Primary</b>}</div>
        <div className="photo-meta"><strong className={'photo-status ' + photo.moderation_status}>{photo.moderation_status}</strong><span>{photo.width} × {photo.height}</span></div>
        {photo.moderation_feedback && <p className="photo-feedback">{photo.moderation_feedback}</p>}
        <label className="photo-visibility">Visibility<select value={photo.visibility} onChange={(event) => run(() => updateProfilePhoto(photo.id, { visibility: event.target.value }), 'Photo visibility updated.')} disabled={busy}>
          <option value="members">Members</option><option value="matches">Accepted connections</option><option value="private">Private</option><option value="hidden">Hidden</option>
        </select></label>
        <div className="photo-controls">
          <button type="button" onClick={() => move(photo.id, -1)} disabled={busy || index === 0} aria-label="Move photo earlier">←</button>
          <button type="button" onClick={() => move(photo.id, 1)} disabled={busy || index === photos.length - 1} aria-label="Move photo later">→</button>
          {!photo.is_primary && <button type="button" onClick={() => run(() => updateProfilePhoto(photo.id, { is_primary: true }), 'Primary photo updated.')} disabled={busy}>Make primary</button>}
          <button type="button" className="photo-delete" onClick={() => remove(photo)} disabled={busy}>Delete</button>
        </div>
      </article>)}
    </div>}
    {message && <div className={failed ? 'auth-notice error' : 'auth-notice success'}>{message}</div>}
  </section>;
}
