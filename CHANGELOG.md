# Changelog

All notable changes to PocketTrack will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.0.0] — 2026-04-09

### Added
- Student management: add, view, search, delete students with unique IDs
- Deposit recording with automatic balance update
- Withdrawal recording with insufficient-balance protection (client + server)
- Transaction history with pagination and type/student filters
- Admin delete on any transaction (balance auto-adjusted on delete)
- Admin dashboard with total students, total balance, total deposits/withdrawals
- Recent transactions panel on dashboard
- Session-based admin login with 1-hour timeout
- Change password page with live strength meter and match check
- CSRF protection on all POST forms
- Secure session cookies: HttpOnly, SameSite=Strict, session_regenerate_id on login
- `.env` support via lightweight built-in parser (no Composer required)
- `.htaccess` with security headers and block rules for sensitive files
- Auto-delete transactions older than 1 year (MySQL event + PHP fallback)
- Mobile-responsive sidebar layout
- Prepared statements throughout (PDO)
- Input validation: rejects negative values, zero amounts, empty required fields
