# VisionPath Africa Registration Refactor

## Setup

1. Run `composer install` in the project directory.
2. Apply `007_verification_code_expiry.sql` to `visionpath_db`.
3. Configure `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, and `SMTP_PORT` in Apache/PHP environment variables. Optional `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME`, and `APP_TIMEZONE` are supported.

## Required diffs

- `register.php`: generate with `random_int`, hash the code, persist a 15-minute expiry, send through `send_verification_email`, and redirect to `verify_code.php`. The code is never rendered.
- `send_mail.php`: centralize PHPMailer SMTP setup and the VisionPath Africa HTML/plain-text message. Credentials remain outside source control.
- `verify_code.php`: validate the submitted code against its hash and expiry, delete used codes, and resend through the same helper. It renders only status messages and form controls.
- `verify.php`: preserve old links by redirecting to the new verifier.
- `007_verification_code_expiry.sql`: replace plaintext storage with `code_hash` and `expires_at`.

## Response-body verification

Set `VISIONPATH_BASE_URL` if needed, then run:

```text
php verify_registration_response.php test@example.com
```

The script submits a registration request and fails if the response body contains any six-digit sequence or the old local-testing leak text. It does not print the generated code.