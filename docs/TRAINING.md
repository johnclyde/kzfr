# Proposed staff training

These are prepared agendas and exercises for two remote 90-minute sessions. No staff training, recording, attendance or acceptance is claimed. Sessions should use staging accounts and agreed sample content. Record only if the participants request and approve it.

## Session 1: Everyday publishing, 90 minutes

| Time | Activity |
| --- | --- |
| 0–10 minutes | Sign in, identify the independent preview, distinguish draft, preview and published content, and review editor/admin responsibilities. |
| 10–30 minutes | Edit a Page with native paragraphs, headings, buttons and a reusable volunteer or donation callout. Use factual headings and the verified People Powered Radio tagline. Update the source URL and preview at mobile width. |
| 30–50 minutes | Create or revise a Program: description, host, genre, stable slug, official archive link and schedule description. Explain the connection to weekly schedule rows. |
| 50–70 minutes | Publish an Event with confirmed date, time, venue and official ticket link. Update an Underwriter listing without duplicating a campaign. |
| 70–85 minutes | Edit Primary navigation in Appearance → Menus. Check the menu on mobile, follow links with a keyboard and review image alternatives. |
| 85–90 minutes | Questions and a short independent publishing exercise. Record follow-up questions in the internal handoff. |

Exercises: save an About-page draft with a new paragraph; preview a program with a corrected host; create a clearly labeled training event as a draft; move one navigation item and restore it. The learner should be able to explain which changes affect only this preview, find a revision and identify the official donation destination.

Success check: an editor can change a page, program and event without developer help; previews the result before publishing; and preserves source attribution and correct external links. Remove training drafts or label and retain them only with staff agreement.

## Session 2: Schedule, archives and care, 90 minutes

| Time | Activity |
| --- | --- |
| 0–15 minutes | Review Session 1 exercises and common publishing questions. Revisit roles and account recovery for an email-disabled demo. |
| 15–35 minutes | Use Station settings to update one confirmed schedule slot. Discuss Pacific time, overnight rows, rotations and unresolved source conflicts. Verify the program link on the public schedule. |
| 35–50 minutes | Edit an Archive record's editorial title/body and exercise program/date/category filters. Explain the complete current public catalogue, provider-owned playback fields, availability changes and restrictions on seeking. |
| 50–65 minutes | Check live listening after a user gesture, official donation and ticket links, mobile navigation, empty search results and practical accessibility basics. |
| 65–80 minutes | Administrator walkthrough of backups, a staged restore, update ownership and rollback using the deployment runbook. Review the managed catalogue refresh, its last successful receipt, and why failed or partial fetches must leave the last valid catalogue in place. |
| 80–90 minutes | Scenario exercise, questions and a handoff checklist with named owners. |

Exercises: change and restore a staging schedule time; preserve all names in a rotating slot; find a current recording using two filters; play a recording that permits seeking and one whose provider disables it; identify a failed or expired recording and use the official fallback; locate the latest backup and describe who can restore it. Demonstrate a restore only on an isolated copy.

Use the October 16, 2026 check as an example: 121 program records and all 196 current public recordings, with 101 recordings restricting seeking. Explain that those counts will change. Older website references whose audio is no longer public are not advertised as available recordings. Show that expired source-owned records become drafts, manual records are preserved, and provider playback restrictions are not staff-editable overrides.

Explain the operating state accurately: the catalogue was imported once, and the repeatable refresh workflow is prepared but not scheduled. A recurring six-hour refresh requires a separate explicit request before activation. Show the manual run receipt and failure handling without implying background updates are running.

Success check: staff can update normal content, recognize an unresolved rotation, use archive filters, identify third-party failures and find the recovery instructions. Administrators can state who owns updates and backups without exposing credentials.

## Materials and follow-up

Provide [CONTENT-AND-EDITING.md](CONTENT-AND-EDITING.md), [CONTENT-SOURCES.md](CONTENT-SOURCES.md), the deployment/backup guide and the final QA record. Supply access through a private channel, never within the training deck or a recording.

After each session, record the date, attendees, exercised workflows, questions and any permissions-dependent follow-ups. Keep these records in the project's internal coordination files, not GitHub Issues. A later production handoff should include station-approved account ownership and the agreed support period. Training completion and production acceptance must be recorded only after they happen.
