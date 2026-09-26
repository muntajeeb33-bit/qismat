# Firebase custom authentication domain

The member website remains hosted on cPanel. Firebase Hosting serves only the OAuth helper endpoints for `auth.qismatconnections.com`, allowing Google consent screens to display a Qismat Connections domain instead of `qismatconnections-20db8.firebaseapp.com`.

## Target configuration

- Firebase project: `qismatconnections-20db8`
- Member website: `https://www.qismatconnections.com`
- Authentication domain: `auth.qismatconnections.com`
- OAuth callback: `https://auth.qismatconnections.com/__/auth/handler`

## Activation checklist

1. Deploy the callback-only Firebase Hosting configuration in this repository with `firebase deploy --only hosting --project qismatconnections-20db8`.
2. In Firebase Hosting, connect `auth.qismatconnections.com` as a custom domain.
3. Add the exact DNS records displayed by Firebase to the authoritative DNS zone, then wait for Firebase to report the domain and SSL certificate as connected.
4. Add `auth.qismatconnections.com` under Firebase Authentication → Settings → Authorized domains.
5. Add `https://auth.qismatconnections.com/__/auth/handler` to the Google OAuth client's authorized redirect URIs.
6. Add the non-sensitive GitHub Actions repository variable `FIREBASE_AUTH_DOMAIN` with value `auth.qismatconnections.com`.
7. Redeploy the member website and verify Google sign-in, the Laravel/Sanctum exchange, session restoration and sign-out.

Do not switch the deployed member website to the custom auth domain before Firebase Hosting, DNS, SSL, the authorized domain and the OAuth redirect URI are all ready. The cPanel deployment workflow retains the Firebase-provided domain as a safe fallback until the repository variable is set.
