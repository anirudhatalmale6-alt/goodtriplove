# Auth flow

Register -> Turnstile -> rate limit -> create account -> send 6-digit code -> verify email -> login -> session regenerate.

Failed logins are logged. After repeated failures, temporarily block the IP and/or account and alert Admin.

Password reset must use Laravel Password Broker plus rate limiting and generic responses to avoid account enumeration.
