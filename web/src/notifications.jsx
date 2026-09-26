import React, { useEffect, useState } from 'react';
import { getNotifications, markAllNotificationsRead, markNotificationRead } from './api';
import './notifications.css';

export function Notifications({ onNavigate }) {
  const [items, setItems] = useState([]);
  const [unread, setUnread] = useState(0);
  const [error, setError] = useState('');

  async function load() {
    try {
      const result = await getNotifications();
      setItems(result.notifications?.data || []); setUnread(result.unread_count || 0); setError('');
    } catch (requestError) { setError(requestError.message); }
  }
  useEffect(() => { load(); }, []);

  async function open(item) {
    if (!item.read_at) await markNotificationRead(item.id);
    if (item.action) onNavigate(item.action);
    else await load();
  }

  async function readAll() {
    try { await markAllNotificationsRead(); await load(); }
    catch (requestError) { setError(requestError.message); }
  }

  return <main className="notifications-page"><div className="notifications-heading"><div><span className="kicker">Updates</span><h1>Notifications</h1><p>Private activity updates appear here without exposing message or profile details on a device lock screen.</p></div>{unread > 0 && <button onClick={readAll}>Mark all read</button>}</div>{error && <div className="auth-notice error">{error}</div>}{items.length === 0 ? <p className="notifications-empty">No notifications yet.</p> : <div className="notifications-list">{items.map((item) => <button key={item.id} className={item.read_at ? '' : 'unread'} onClick={() => open(item)}><span>{item.read_at ? '○' : '●'}</span><div><strong>{item.title}</strong><p>{item.body}</p><small>{new Date(item.created_at).toLocaleString()}</small></div></button>)}</div>}</main>;
}
