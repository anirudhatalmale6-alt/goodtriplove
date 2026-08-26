# GoodTripLove — Core Operations & User Features

Please integrate this module into the existing GoodTripLove Laravel project.
Do not overwrite existing Security, Growth Ops, Platform Services or Legal modules.

## Required additions

### AI correction memory
When an administrator corrects an AI classification, store:
- original prediction
- corrected country
- corrected city
- corrected place
- corrected category
- model
- confidence
- administrator
- timestamp

Future classifications should be able to use these corrections as deterministic rules/examples.

### YouTube quota manager
Admin dashboard must show:
- daily quota limit
- quota used
- quota remaining
- percentage used
- last API request
- warning threshold
- hard stop threshold

Collector must pause before exceeding the configured daily limit.

### Video import pipeline
Use explicit states:
FOUND
FETCHED
AI_ANALYSIS
REVIEW
APPROVED
PUBLISHED
REJECTED
FAILED

Every transition must be logged.

### Business claim
Restaurant, hotel, guest house or other professional can claim a place.
Require email verification and Admin approval before ownership/management permissions are granted.

### Creator profiles
Store creator/channel/source, external ID, platform URL and associated videos.
Allow correction/removal requests.

### Trip lists
Users can create private/public travel lists and add places/videos.

### User history
Store recently viewed places/videos with retention controls and privacy support.

### Sharing
Provide share actions and canonical share URL.
Generate a QR code for public place pages.

### PWA
Provide manifest, service worker, installability and offline fallback.
Do not cache authenticated/private pages.

### Push notifications
Prepare web/mobile device-token subscriptions and notification preferences.
Provider credentials must remain server-side.

### App versions
Admin can define current/minimum Android/iOS versions and whether an update is optional or mandatory.

### Maintenance mode
Super Admin can enable/disable maintenance mode while retaining authorized access.

### Feature flags
Admin can independently enable/disable major features without code deployment.

### Technical Error Center
Aggregate application errors, failed jobs and external service failures without exposing secrets.

### Automated health tests
Test public homepage, DB, queue heartbeat, scheduler heartbeat, mail configuration, YouTube configuration,
Ollama and SSL/HTTPS status as applicable.

### Admin Status page
One page should show the latest status of all monitored services.
