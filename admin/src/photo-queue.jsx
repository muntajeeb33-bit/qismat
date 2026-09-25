import React, { useEffect, useRef, useState } from 'react';
import { getPhotoBlob } from './api';
import './photo-queue.css';

export function PhotoQueue({ photos, reviewing, onReview }) {
  const [previews, setPreviews] = useState({});
  const previewRef = useRef({});

  useEffect(() => {
    let mounted = true;
    Promise.all(photos.map(async (photo) => {
      const blob = await getPhotoBlob(photo.content_url);
      return [photo.id, URL.createObjectURL(blob)];
    })).then((entries) => {
      if (!mounted) {
        entries.forEach(([, url]) => URL.revokeObjectURL(url));
        return;
      }
      Object.values(previewRef.current).forEach((url) => URL.revokeObjectURL(url));
      previewRef.current = Object.fromEntries(entries);
      setPreviews(previewRef.current);
    }).catch(() => setPreviews({}));

    return () => {
      mounted = false;
      Object.values(previewRef.current).forEach((url) => URL.revokeObjectURL(url));
      previewRef.current = {};
    };
  }, [photos]);

  if (photos.length === 0) return <p className="empty">No photos are waiting for review.</p>;

  return <div className="photo-review-grid">{photos.map((photo) => <article className="photo-review-card" key={photo.id}>
    <div className="review-photo">{previews[photo.id] ? <img src={previews[photo.id]} alt="Member profile submission" /> : <span>Loading photo…</span>}{photo.is_primary && <b>Primary</b>}</div>
    <div className="review-photo-copy">
      <h4>{photo.user.profile?.display_name || photo.user.name}</h4>
      <p>{photo.user.profile?.profile_code || photo.user.email}</p>
      <small>{photo.width} × {photo.height} · {photo.visibility} · uploaded {new Date(photo.created_at).toLocaleDateString()}</small>
    </div>
    <div className="profile-actions"><button className="reject" onClick={() => onReview(photo, 'rejected')} disabled={reviewing === photo.id}>Reject</button><button className="approve" onClick={() => onReview(photo, 'approved')} disabled={reviewing === photo.id}>Approve</button></div>
  </article>)}</div>;
}
