# Security operations

- Provider secret keys exist only in Laravel environment configuration. `EXPO_PUBLIC_*` values are embedded in the app and are never secrets.
- Production Expo builds refuse a missing or non-HTTPS API URL.
- Never disable PayMongo signature verification. Rotate a leaked secret in PayMongo and the server environment immediately.
- Restrict production CORS to approved web origins. Native applications do not rely on CORS as an authorization control.
- Image moderation fails closed by default: an unscannable image is removed and quarantined. Set `MODERATION_FAIL_OPEN=true` only as an explicit availability tradeoff.
- Run dependency audits, PHP tests, TypeScript checks, Expo Doctor, and metadata checks before release.
- Monitor failed `webhook_events`, failed queue jobs, payment mismatches, and subscriptions stuck in `pending`.
- Database backups must include restore testing. Uploaded files and quarantine storage require separate backups and retention rules.
- Banning a user revokes all current API tokens; every API request also rejects banned users.
- Avoid storing raw credentials, authorization headers, or full personal/payment payloads in application logs.
