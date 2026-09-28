# Sprint 131 — Driver availability calendar

The monthly calendar reads organization scoped driver availability from the API. A missing day means unknown, never available. Drivers submit a daily available or unavailable declaration; a dispatcher with driver supervisory access confirms or rejects it. The submitter cannot confirm their own entry. Each change increments a revision and creates an audit event; stale revisions return 409. Revisions of confirmed entries return to pending. The source timezone is Europe/Prague.

This first calendar release records full days. Part-day windows, holiday calculations, automatic assignment eligibility and trip reservation remain separate workflows. The legacy `driver_schedule_days` table is preserved and is not silently treated as confirmed availability. External carrier administrators need the existing explicit supervisory scope to manage another driver's entry. The backend tests use disposable SQLite.
