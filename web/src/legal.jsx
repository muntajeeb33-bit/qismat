import React from 'react';
import './legal.css';

const pages = {
  privacy: {
    title: 'Privacy policy',
    intro: 'This policy explains what Qismat Connections collects, why we use it, and the choices available to members.',
    sections: [
      ['Information we collect', 'We collect account details, profile and partner-preference information, photos, messages, interests, favourites, reports, moderation records, device and security information, and service activity. Sensitive profile details are optional unless clearly marked as required.'],
      ['How we use information', 'We use information to authenticate members, operate matching and discovery, provide private messaging, moderate profiles and photos, prevent fraud and abuse, respond to support requests, and maintain service reliability.'],
      ['Visibility and sharing', 'Your profile is not discoverable until it is approved and you opt in. Visibility and photo controls apply alongside blocking and moderation rules. We do not sell member personal information. Service providers may process limited information to host, secure, authenticate, or support the service.'],
      ['Retention and deletion', 'Members can delete their Qismat account from Settings. Profile content, photos, preferences, connections, messages, and active sessions are removed. Limited safety, fraud, audit, backup, or legal records may be retained where reasonably necessary and then removed under the applicable retention schedule.'],
      ['Your choices', 'You can edit your profile, pause discovery, change notification preferences, manage blocked members, sign out every session, or delete your account. Contact support for an access, correction, or privacy request.'],
      ['Security and children', 'Qismat uses access controls, private media storage, moderation, and audit records to protect the service. Qismat is only for adults aged 18 or older.'],
    ],
  },
  terms: {
    title: 'Terms of use',
    intro: 'By creating or using a Qismat account, you agree to these rules for a safe marriage-focused community.',
    sections: [
      ['Eligibility and purpose', 'You must be at least 18 and use Qismat only for genuinely seeking marriage. Commercial use by marriage bureaus, agents, recruiters, advertisers, or other businesses is prohibited.'],
      ['Truthful conduct', 'Provide accurate information and use only photos you have the right to share. Impersonation, falsified details, scams, financial solicitation, harassment, threats, explicit content, scraping, and attempts to bypass privacy or security controls are prohibited.'],
      ['Member responsibility', 'Use care before sharing personal information, meeting, or sending money. Qismat does not guarantee identity, compatibility, conduct, or marriage outcomes. Report suspicious behavior and contact emergency services when immediate safety is at risk.'],
      ['Moderation and enforcement', 'Qismat may review, reject, restrict, suspend, or remove content or accounts that violate these terms or create safety, legal, or operational risk. Serious suspected fraud, threats, exploitation, or unlawful activity may be reported to appropriate authorities.'],
      ['Service changes', 'Features may change as the service develops. Material changes to these terms will be presented with an updated effective date. Continued use after notice means you accept the updated terms.'],
    ],
  },
  safety: {
    title: 'Safety centre',
    intro: 'Move carefully, keep conversations on Qismat while trust develops, and use the safety controls whenever something feels wrong.',
    sections: [
      ['Protect your information', 'Do not share passwords, verification codes, banking details, government identity numbers, or your home or workplace address. Avoid moving immediately to private messaging services.'],
      ['Watch for warning signs', 'Be cautious of requests for money, investments, travel costs, gifts, immigration help, urgency, secrecy, inconsistent stories, or refusal to video call or meet safely.'],
      ['Meet safely', 'Tell someone you trust, meet in a public place, control your own transport, keep your phone available, and leave whenever you feel uncomfortable. Never let pressure override your judgment.'],
      ['Block and report', 'Blocking immediately stops discovery and communication between both members. Reports are confidential and reviewed by administrators. For immediate danger, contact local emergency services first.'],
    ],
  },
  support: {
    title: 'Help and account deletion',
    intro: 'Member controls are available inside your account. Sign in and open Settings to manage privacy, notifications, blocked members, sessions, or deletion.',
    sections: [
      ['Account access', 'Use Forgot password on the sign-in panel for password recovery. Verification links are sent after registration. Check your spam folder before requesting additional help.'],
      ['Profile and moderation', 'Your profile and primary photo must be approved before discovery. Reviewer feedback appears in your member home so you can correct and resubmit information.'],
      ['Delete your account', 'Open Settings, enter DELETE in the confirmation field, and select Permanently delete my account. This signs out all sessions and removes your Qismat profile data. If you cannot sign in, email support@qismatconnections.com and include only the email associated with your account.'],
      ['Reporting', 'Use the Report control on a profile, interest, or conversation for safety concerns. Do not include passwords, bank details, or unrelated sensitive information in a report.'],
    ],
  },
};

export function LegalPage({ page, onClose }) {
  const content = pages[page];
  if (!content) return null;
  return <div className="legal-shell"><header><a href="#top" onClick={onClose}><img src="/assets/qismat-connections-logo.png" alt="Qismat Connections" /></a><button onClick={onClose}>Back to Qismat</button></header><main><span className="kicker">Qismat Connections</span><h1>{content.title}</h1><p className="legal-intro">{content.intro}</p><p className="legal-date">Effective 26 September 2026</p>{content.sections.map(([title, body]) => <section key={title}><h2>{title}</h2><p>{body}</p></section>)}</main></div>;
}
