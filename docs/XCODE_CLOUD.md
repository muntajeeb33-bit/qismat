# Qismat iOS / Xcode Cloud

Qismat uses Flutter for the shared mobile codebase and Xcode Cloud for iOS builds/releases.

## Repository layout

Flutter source lives in `mobile/`. Native iOS project files are generated from the Flutter project and will be committed before Xcode Cloud is enabled.

## Initial generation

On a macOS development environment with Flutter installed:

```bash
cd mobile
flutter create --platforms=ios --org com.qismat .
flutter pub get
open ios/Runner.xcworkspace
```

Use an Apple bundle identifier such as `com.qismat.app` after the final Apple Developer/App Store Connect identifiers are confirmed.

## Xcode Cloud workflow

1. Add the repository to App Store Connect / Xcode Cloud.
2. Select the `Runner` scheme.
3. Set the working branch to `main` for production and optionally a staging branch for TestFlight builds.
4. Configure signing through the Apple Developer team.
5. Add non-secret build configuration through Xcode Cloud environment variables.
6. Keep API keys, certificates and service credentials out of Git.
7. Run Flutter dependency/bootstrap steps in Xcode Cloud custom scripts before the Xcode build.

## Release policy

- Pull request: source validation only.
- Main branch: eligible for TestFlight build.
- App Store production: explicit manual promotion after QA.
- iOS build/version must stay aligned with `mobile/pubspec.yaml` and `docs/PROJECT_STATUS.md`.

## Pending before activation

- Apple Developer Team ID
- Final iOS bundle identifier
- App Store Connect app record
- Firebase iOS configuration for notifications
- Privacy manifest / permission descriptions
- App icons and launch assets
