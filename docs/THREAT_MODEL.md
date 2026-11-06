# Threat Model - Nastiia's Gallery

## Document Version
- **Version:** 1.0
- **Date:** 2026-11-06
- **Author:** Security Team
- **Status:** Draft

---

## 1. System Overview

### 1.1 Application Description
Nastiia's Gallery is a Laravel-based web application for showcasing and managing digital artwork. The application features:
- **Public Gallery:** React/Inertia frontend for viewing artwork
- **Admin Panel:** Filament-based CMS for managing artwork, categories, comments
- **File Uploads:** Spatie MediaLibrary for artwork image storage
- **Authentication:** Laravel Fortify with 2FA and Passkeys
- **Database:** SQLite (development) / PostgreSQL (production)

### 1.2 Technology Stack
| Layer | Technology |
|-------|------------|
| Backend | Laravel 13.x (PHP 8.3) |
| Frontend | React 19 + Inertia.js |
| Admin Panel | Filament 5.x |
| File Storage | Spatie Laravel MediaLibrary |
| Auth | Laravel Fortify + Sanctum |
| Testing | Pest PHP |
| Static Analysis | PHPStan Level 7 + Security Rules |
| CI/CD | GitHub Actions |

---

## 2. Assets & Trust Boundaries

### 2.1 Assets (What We Protect)

| Asset | Classification | Description |
|-------|----------------|-------------|
| **User PII** | High | Display name, email, password hashes, 2FA secrets, passkeys |
| **Auth Tokens** | Critical | Session cookies, remember tokens, Sanctum API tokens |
| **Artwork Data** | Medium | Titles, descriptions, metadata, view/like counts |
| **Media Files** | High | Original artwork images (potential copyright/IP) |
| **Admin Actions** | High | Audit trail of deletions, modifications, user management |
| **Application Secrets** | Critical | APP_KEY, DB credentials, third-party API keys |

### 2.2 Trust Boundaries

```
┌─────────────────────────────────────────────────────────────────┐
│                        INTERNET                                  │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTPS
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    WEB SERVER (Nginx)                            │
│  - TLS Termination                                              │
│  - Rate Limiting                                                │
│  - Security Headers                                             │
└──────────────────────────┬──────────────────────────────────────┘
                           │
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
    ┌────────────┐  ┌────────────┐  ┌────────────┐
    │  PUBLIC    │  │   ADMIN    │  │   API      │
    │  FRONTEND  │  │  PANEL     │  │  (Future)  │
    │  (React)   │  │ (Filament) │  │            │
    └─────┬──────┘  └─────┬──────┘  └─────┬──────┘
          │               │               │
          └───────────────┼───────────────┘
                          ▼
               ┌─────────────────────┐
               │  LARAVEL APPLICATION │
               │  - Controllers       │
               │  - Policies          │
               │  - Middleware        │
               └──────────┬──────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
    ┌────────────┐  ┌────────────┐  ┌────────────┐
    │  DATABASE  │  │ FILE STORE │  │   QUEUE    │
    │  (SQLite/  │  │ (Local/S3) │  │  (Redis/   │
    │  Postgres) │  │            │  │  Database) │
    └────────────┘  └────────────┘  └────────────┘
```

---

## 3. STRIDE Analysis by Component

### 3.1 Authentication System (Laravel Fortify)

| Threat | Type | Risk | Mitigation | Security Story |
|--------|------|------|------------|----------------|
| Credential stuffing | Spoofing | High | Rate limiting (throttle:6,1), 2FA, Passkeys | SEC-01 |
| Session fixation | Spoofing | Medium | Session regeneration on login | SEC-01 |
| Password brute force | Spoofing | High | Throttle middleware, strong password policy | SEC-01 |
| 2FA bypass | Tampering | High | Confirm password before 2FA changes | SEC-01 |
| Passkey cloning | Spoofing | Low | WebAuthn standard, device-bound keys | SEC-01 |
| Timing attacks | Information Disclosure | Low | Constant-time comparison (hashed passwords) | SEC-01 |

### 3.2 Artworks CRUD (Filament Admin Panel)

| Threat | Type | Risk | Mitigation | Security Story |
|--------|------|------|------------|----------------|
| IDOR (Insecure Direct Object Reference) | Spoofing | High | Admin panel restricted to Admin/SuperAdmin roles; policies on resources | SEC-01 |
| Privilege escalation | Elevation of Privilege | High | Role-based access control, `canAccessPanel()` check | SEC-01 |
| Mass assignment | Tampering | Medium | `$fillable` array, no `role` in fillable | SEC-02 |
| XSS in title/description | Tampering | High | Output encoding (`e()`), CSP headers | SEC-02, SEC-05 |
| SQL Injection | Tampering | High | Eloquent ORM (parameterized queries) | SEC-02 |
| CSRF | Spoofing | Medium | CSRF tokens on all forms | Built-in |

### 3.3 File Upload (Spatie MediaLibrary)

| Threat | Type | Risk | Mitigation | Security Story |
|--------|------|------|------------|----------------|
| Malicious file upload (RCE) | Tampering | Critical | `ValidArtworkImage` rule: finfo + getimagesize(), MIME validation, extension check | SEC-06 |
| Polyglot files | Tampering | High | Structural validation via `getimagesize()` | SEC-06 |
| Image decompression bombs | DoS | Medium | Max dimension check (8000px) | SEC-06 |
| Path traversal | Tampering | High | MediaLibrary handles storage, no user-controlled paths | SEC-06 |
| Oversized files | DoS | Medium | Max size 10MB enforced | SEC-06 |
| SVG XSS | Tampering | High | SVG not in accepted MIME types | SEC-06 |
| Stored XSS via EXIF | Tampering | Low | Images reprocessed (conversions strip metadata) | SEC-06 |

### 3.4 Admin Panel (Filament)

| Threat | Type | Risk | Mitigation | Security Story |
|--------|------|------|------------|----------------|
| Unauthorized access | Spoofing | Critical | `EnsureUserHasAdminRole` middleware, `canAccessPanel()` | SEC-01 |
| Admin impersonation | Spoofing | High | No impersonation feature implemented | SEC-01 |
| Action injection | Tampering | Medium | Filament action authorization | SEC-01 |
| Information disclosure | Info Disclosure | Low | 404 instead of 403 for unauthorized | SEC-01 |

### 3.5 Design Test Page (Public React Page)

| Threat | Type | Risk | Mitigation | Security Story |
|--------|------|------|------------|----------------|
| XSS via props | Tampering | Medium | React auto-escapes, CSP | SEC-02, SEC-05 |
| Client-side injection | Tampering | Low | No user input on design test page | SEC-02 |
| Information disclosure | Info Disclosure | Low | No sensitive data on page | N/A |

---

## 4. Attack Trees

### 4.1 Attack Tree: Compromise Admin Account

```
Compromise Admin Account
├── Credential Theft
│   ├── Phishing (mitigated by 2FA/Passkeys)
│   ├── Keylogger (mitigated by Passkeys)
│   └── Session hijacking (mitigated by HTTPS, Secure cookies, HSTS)
├── Brute Force
│   ├── Password spray (mitigated by rate limiting)
│   └── Credential stuffing (mitigated by 2FA)
├── Privilege Escalation
│   ├── Regular user → Admin (mitigated by role checks, no mass assignment)
│   └── Admin → SuperAdmin (mitigated by no UI for role change)
└── Session Fixation
    └── Pre-login session (mitigated by session regeneration)
```

### 4.2 Attack Tree: Upload Malicious File

```
Upload Malicious File
├── Bypass Extension Check
│   ├── Double extension (test.php.jpg) → Blocked by finfo MIME check
│   └── Null byte injection → Blocked by PHP 8.3+ and validation
├── Bypass MIME Check
│   ├── Magic bytes spoofing → Blocked by getimagesize() structural check
│   └── Polyglot files → Blocked by dimension/type consistency check
├── Exploit Image Processing
│   ├── Decompression bomb → Blocked by max dimension (8000px)
│   └── Memory exhaustion → Blocked by size limit (10MB)
└── Stored XSS
    ├── SVG upload → Blocked (SVG not allowed)
    └── EXIF payload → Stripped by conversions
```

### 4.3 Attack Tree: Access Other User's Artwork

```
Access Other User's Artwork (IDOR)
├── Direct URL Access
│   ├── /admin/artworks/{id}/edit → Blocked (admin only, but admins see all)
│   └── Public gallery view → Intended (public)
├── API Manipulation
│   └── No public API for artwork CRUD
└── Database Direct Access
    └── Not accessible from web
```

---

## 5. Risk Matrix

| ID | Threat | Likelihood | Impact | Risk Level | Mitigation Status |
|----|--------|------------|--------|------------|-------------------|
| T1 | Malicious file upload → RCE | Low | Critical | **High** | ✅ SEC-06 implemented |
| T2 | IDOR on artwork admin | Medium | High | **High** | ✅ SEC-01 (admin only) |
| T3 | XSS in artwork description | Medium | High | **High** | ✅ SEC-02 + SEC-05 |
| T4 | SQL Injection | Low | Critical | **High** | ✅ Eloquent ORM |
| T5 | Admin panel unauthorized access | Low | Critical | **High** | ✅ SEC-01 |
| T6 | Session hijacking | Low | High | **Medium** | ✅ HTTPS, Secure cookies, HSTS |
| T7 | Credential stuffing | Medium | High | **High** | ✅ 2FA, Passkeys, Rate limiting |
| T8 | Path traversal in upload | Low | High | **Medium** | ✅ MediaLibrary handles paths |
| T9 | Information disclosure via errors | Medium | Low | **Low** | ✅ Debug disabled in prod |
| T10 | Dependency vulnerability | Medium | Medium | **Medium** | ✅ SEC-03 (SCA in CI) |

---

## 6. Security Stories Mapping

| Story | Title | Components Protected | Tests |
|-------|-------|---------------------|-------|
| **SEC-01** | Users may only view/edit/delete own artworks | Auth, Artworks CRUD, Admin Panel | `AuthenticationTest`, `ArtworkAuthorizationTest` |
| **SEC-02** | All input validated and sanitized | Artworks CRUD, File Upload, Forms | `InputValidationTest` |
| **SEC-03** | No critical/high vulnerabilities in dependencies | All (supply chain) | `DependencyTest`, CI SCA |
| **SEC-04** | No secrets in code | All | Semgrep secrets scan (CI + pre-commit) |
| **SEC-05** | Security headers (CSP, HSTS, X-Frame-Options) | All HTTP responses | `HeadersTest`, `SecurityHeaders` middleware |
| **SEC-06** | File uploads secure (MIME, size, no executables) | File Upload (MediaLibrary) | `FileUploadTest`, `ValidArtworkImage` rule |

---

## 7. Mitigations Implemented

### 7.1 Code-Level
- [x] `ValidArtworkImage` rule with finfo + getimagesize validation
- [x] `$fillable` arrays on all models (no mass assignment vulns)
- [x] Eloquent ORM (parameterized queries, no raw SQL)
- [x] Output encoding with `e()` in Filament resource
- [x] Role-based access control (`canAccessPanel`, middleware)
- [x] CSRF protection on all forms
- [x] Password hashing (bcrypt via Laravel)

### 7.2 Infrastructure
- [x] HTTPS enforced (HSTS header)
- [x] Security headers middleware (CSP, X-Frame-Options, etc.)
- [x] Secure session cookies (HttpOnly, Secure, SameSite)
- [x] Rate limiting on auth endpoints

### 7.3 CI/CD Pipeline
- [x] Composer Audit (SCA) - weekly + on PR
- [x] NPM Audit (SCA) - weekly + on PR
- [x] PHPStan Level 7 + Security Rules (SAST)
- [x] Semgrep (Laravel, Secrets, OWASP Top 10)
- [x] Security test suite in CI

### 7.4 Developer Workflow
- [x] Pre-commit hooks (PHPStan, Pint, Composer/NPM audit, Semgrep secrets)
- [x] Dependabot for automated dependency updates

---

## 8. Residual Risks

| Risk | Description | Acceptance Criteria |
|------|-------------|---------------------|
| CSP `unsafe-inline` | Required for Inertia/Vite in production | Monitor CSP violations, plan nonce-based CSP |
| Admin users see all artworks | By design for CMS workflow | Audit logging for admin actions (ActivityLog) |
| No WAF | Relies on application-level security | Cloudflare/proxy WAF in production |
| SQLite in development | Not production-grade | PostgreSQL in production |

---

## 9. Monitoring & Incident Response

### 9.1 Logging
- Laravel Telescope (local/staging only)
- Spatie ActivityLog for: artwork deletion, admin actions, role changes
- Failed login attempts (Fortify)
- File upload rejections

### 9.2 Alerts
- Composer/NPM audit failures in CI
- Semgrep findings in CI
- PHPStan errors in CI
- Security test failures in CI

### 9.3 Incident Response
1. **Credential compromise:** Revoke sessions, force password reset, check audit log
2. **Malicious upload:** Quarantine file, scan MediaLibrary, check conversion outputs
3. **XSS reported:** Verify CSP blocked execution, fix validation, deploy fix
4. **Dependency vulnerability:** Dependabot PR → review → merge → deploy

---

## 10. Future Improvements

| Priority | Improvement | Related Story |
|----------|-------------|---------------|
| High | Nonce-based CSP (remove `unsafe-inline`) | SEC-05 |
| High | Rate limiting on artwork CRUD endpoints | SEC-01 |
| Medium | File type detection via multiple engines (clamav) | SEC-06 |
| Medium | Automated penetration testing in CI | All |
| Low | Content Security Policy reporting endpoint | SEC-05 |
| Low | Security.txt file | SEC-04 |

---

## Appendix A: Data Flow Diagram (Text)

```
USER (Browser)
    │
    ├─► GET / (Public Gallery)
    │       │
    │       ├─► SecurityHeaders Middleware
    │       ├─► HandleInertiaRequests
    │       └─► Welcome Page (React)
    │
    ├─► POST /login
    │       │
    │       ├─► Throttle Middleware (6/min)
    │       ├─► Fortify Authentication
    │       ├─► Session Regeneration
    │       └─► 2FA/Passkey Challenge (if enabled)
    │
    ├─► GET /admin (Admin Panel)
    │       │
    │       ├─► Auth Middleware
    │       ├─► EnsureUserHasAdminRole
    │       ├─► Filament Panel
    │       └─► ArtworkResource (CRUD)
    │
    ├─► POST /admin/artworks (Create Artwork)
    │       │
    │       ├─► File Upload → ValidArtworkImage Rule
    │       │       ├─► finfo MIME check
    │       │       ├─► getimagesize() structural check
    │       │       ├─► Extension/MIME consistency
    │       │       ├─► Dimension limit (8000px)
    │       │       └─► Size limit (10MB)
    │       │
    │       ├─► MediaLibrary Store (public disk)
    │       ├─► Conversions (compressed, thumbnail)
    │       └─► Database Insert (Eloquent)
    │
    └─► GET /design-test
            │
            ├─► SecurityHeaders Middleware
            └─► Design Test Page (React)
```

---

## Appendix B: Security Test Coverage Matrix

| Test Class | SEC-01 | SEC-02 | SEC-03 | SEC-04 | SEC-05 | SEC-06 |
|------------|--------|--------|--------|--------|--------|--------|
| AuthenticationTest | ✅ | | | | | |
| ArtworkAuthorizationTest | ✅ | | | | | |
| InputValidationTest | | ✅ | | | | |
| FileUploadTest | | ✅ | | | | ✅ |
| DependencyTest | | | ✅ | | | |
| HeadersTest | | | | | ✅ | |

---

*End of Threat Model Document*
