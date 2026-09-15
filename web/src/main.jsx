import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

const Arrow = () => <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" /></svg>;
const Heart = () => <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" /></svg>;
const Shield = () => <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.8 6v5.2c0 4.4 3 8.5 7.2 9.8 4.2-1.3 7.2-5.4 7.2-9.8V6L12 3Z"/><path d="m8.8 12 2.1 2.1 4.4-4.5"/></svg>;
const Spark = () => <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c.5 4.9 3.1 7.6 8 8-4.9.5-7.5 3.1-8 8-.5-4.9-3.1-7.5-8-8 4.9-.4 7.5-3.1 8-8Z"/></svg>;

const steps = [
  ['01', 'Tell us about yourself', 'Create a thoughtful profile that reflects your values, story and what truly matters to you.'],
  ['02', 'Discover meaningful matches', 'Explore compatible profiles chosen around your preferences, lifestyle and future goals.'],
  ['03', 'Connect with confidence', 'Express interest and start a conversation only when the feeling is mutual.'],
];

function Brand() {
  return <a className="brand" href="#top" aria-label="Qismat Connections home"><span className="brand-mark"><Heart /></span><span>Qismat Connections</span></a>;
}

function App() {
  const [menuOpen, setMenuOpen] = useState(false);
  return <div className="site-shell">
    <header className="header">
      <Brand />
      <nav className={menuOpen ? 'main-nav open' : 'main-nav'} aria-label="Main navigation">
        <a href="#discover" onClick={() => setMenuOpen(false)}>Discover</a>
        <a href="#journey" onClick={() => setMenuOpen(false)}>How it works</a>
        <a href="#safety" onClick={() => setMenuOpen(false)}>Safety</a>
        <a href="#stories" onClick={() => setMenuOpen(false)}>Success stories</a>
        <div className="mobile-actions"><button className="btn btn-quiet">Log in</button><button className="btn btn-primary">Join free</button></div>
      </nav>
      <div className="header-actions"><button className="login-link">Log in</button><button className="btn btn-primary btn-small">Join free <Arrow /></button></div>
      <button className="menu-button" onClick={() => setMenuOpen(!menuOpen)} aria-expanded={menuOpen} aria-label="Toggle menu"><span></span><span></span></button>
    </header>

    <main id="top">
      <section className="hero-section">
        <div className="hero-copy">
          <div className="eyebrow"><span></span> India’s thoughtful matrimonial experience</div>
          <h1>Where beautiful<br />beginnings find <em>you.</em></h1>
          <p className="hero-lede">A modern, private and trusted way to meet someone who shares your values—and your vision for the future.</p>
          <div className="hero-buttons"><button className="btn btn-primary btn-large">Create your profile <Arrow /></button><a className="text-link" href="#journey">See how it works <span>↓</span></a></div>
          <div className="trust-row"><div className="member-faces" aria-hidden="true"><span>✓</span><span>♡</span><span>Q</span><span>+</span></div><div><div className="stars">★★★★★</div><small>Built for thoughtful, verified connections</small></div></div>
        </div>
        <div className="hero-visual">
          <div className="arch-frame"><img src="/assets/qismat-hero-couple.jpg" alt="A happy couple celebrating their engagement" /><div className="image-shade"></div><div className="image-caption"><span>Q</span><p><strong>Real connections.</strong><br />Rooted in shared values.</p></div></div>
          <div className="verified-card"><span><Shield /></span><p><strong>Verified profiles</strong><small>Designed for safer connections</small></p></div><span className="flourish one">✦</span><span className="flourish two">✦</span>
        </div>
      </section>

      <section className="search-wrap" id="discover"><div className="search-card">
        <div className="search-heading"><Spark /><div><span>Start your search</span><strong>Who are you looking for?</strong></div></div>
        <label>I’m looking for<select defaultValue="Bride"><option>Bride</option><option>Groom</option></select></label>
        <label>Age<div className="age-fields"><select defaultValue="24"><option>21</option><option>22</option><option>23</option><option>24</option><option>25</option><option>26</option></select><i>to</i><select defaultValue="30"><option>28</option><option>29</option><option>30</option><option>31</option><option>32</option><option>33</option></select></div></label>
        <label>Religion<select defaultValue="Select"><option disabled>Select</option><option>Hindu</option><option>Muslim</option><option>Sikh</option><option>Christian</option><option>Jain</option><option>Buddhist</option><option>Other</option></select></label>
        <button className="btn btn-dark">View matches <Arrow /></button>
      </div></section>

      <section className="promise-strip"><span>Handpicked compatibility</span><i>✦</i><span>Privacy at every step</span><i>✦</i><span>Real, verified people</span><i>✦</i><span>Made for lasting love</span></section>

      <section className="journey section" id="journey">
        <div className="section-heading"><div><span className="kicker">Your journey, made simple</span><h2>From a profile to a<br /><em>beautiful possibility.</em></h2></div><p>Finding a life partner is deeply personal. We keep every step considered, comfortable and entirely in your control.</p></div>
        <div className="steps-grid">{steps.map((step, i) => <article className="step-card" key={step[0]}><div className="step-top"><span>{step[0]}</span><span className="step-icon">{i === 0 ? '✎' : i === 1 ? '⌕' : '♡'}</span></div><h3>{step[1]}</h3><p>{step[2]}</p><a href="#top">Learn more <Arrow /></a></article>)}</div>
      </section>

      <section className="safety-section" id="safety">
        <div className="safety-art"><div className="safety-rings"><Shield /></div><div className="privacy-note"><strong>You decide</strong><small>who sees your photos</small></div></div>
        <div className="safety-copy"><span className="kicker">Private by design</span><h2>Your story belongs<br /><em>to you.</em></h2><p>Move at your own pace with controls that keep your identity, photos and conversations protected.</p><ul>
          <li><span>✓</span><div><strong>Profile verification</strong><small>Thoughtful checks help foster a genuine community.</small></div></li>
          <li><span>✓</span><div><strong>Private photo controls</strong><small>Choose exactly who gets to see your photos.</small></div></li>
          <li><span>✓</span><div><strong>Mutual-interest messaging</strong><small>Conversations begin only when you both say yes.</small></div></li>
        </ul><a className="text-link burgundy" href="#top">Explore our safety promise <Arrow /></a></div>
      </section>

      <section className="story-section section" id="stories">
        <div className="story-card"><div className="quote-mark">“</div><blockquote>You don’t need endless introductions. You need one connection that feels honest, compatible and full of possibility.</blockquote><p><strong>The Qismat promise</strong><span>Quality over quantity, always</span></p></div>
        <div className="story-side"><span className="kicker">Stories written by fate</span><h2>One introduction can<br />change <em>everything.</em></h2><p>Every Qismat story begins with two people choosing to take a meaningful first step.</p><a className="text-link burgundy" href="#top">Read success stories <Arrow /></a></div>
      </section>

      <section className="final-cta"><span className="cta-spark">✦</span><p className="kicker">Your person may be closer than you think</p><h2>Let your story<br /><em>begin.</em></h2><button className="btn btn-light btn-large">Join Qismat for free <Arrow /></button><small>It takes less than 5 minutes to create your profile.</small></section>
    </main>

    <footer><div className="footer-brand"><Brand /><p>Meaningful matches.<br />Beautiful beginnings.</p></div><div className="footer-links"><div><strong>Discover</strong><a href="#discover">Find matches</a><a href="#stories">Success stories</a><a href="#journey">How it works</a></div><div><strong>Trust</strong><a href="#safety">Safety</a><a href="#safety">Privacy</a><a href="#safety">Help centre</a></div><div><strong>Company</strong><a href="#top">About Qismat</a><a href="#top">Contact</a><a href="#top">Careers</a></div></div><div className="footer-bottom"><span>© 2026 Qismat Connections. All rights reserved.</span><span>Made with care for meaningful connections.</span></div></footer>
  </div>;
}

createRoot(document.getElementById('root')).render(<App />);
