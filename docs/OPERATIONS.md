# Operations and deployment

The temporary demonstration runs as an independent WordPress application and MariaDB database behind an HTTPS reverse proxy. The database has no public port; the application binds to loopback. WordPress, PHP, MariaDB, and the core parent theme come from official sources. Exact deployed versions and checks belong in the local deployment receipt.

## Security and account ownership

Administrator credentials live only in an owner-readable server secret store. Never commit, email, or place them in page content. Staff should receive individual accounts, normally Editor for content publishers and Administrator only for approved operators. Disable unused accounts. Use strong unique passwords and add a maintained second-factor solution or hosting identity layer before production handoff.

The demo disables registration, outgoing WordPress mail, comments, pings, XML-RPC, public user enumeration, sitemaps, and indexing. The proxy adds noindex headers and blocks sensitive files and PHP execution under uploads. Noindex is not privacy; publish only public material. The public repository is restricted to custom source and sanitized documentation. No payment data or donor database is stored by this project.

The security settings in the station plugin are intentionally for this demo. A production launch must deliberately review indexing, mail, password reset, scheduled jobs, analytics, forms, payment links, and account recovery. Do not simply remove all guards at once.

## Updates

John owns maintenance for the temporary preview. Review critical security notices promptly and review component updates at least weekly while it remains online. Before updating: create a database and files backup, restore to isolated staging, update WordPress and the parent theme, apply refreshed supported runtime images, run the content checks and representative browser flows, then release. Minor WordPress core security updates are enabled through the five-minute WordPress scheduled-task runner. Container image references are pinned for reproducibility; they are not automatically refreshed by container restart. Parent-theme and major-version updates require operator review. Document each change and rollback revision.

No premium plugin or theme subscription is needed. WordPress core, Twenty Twenty-One, the child theme, and the one station plugin are the component inventory. The hosting runtime also contains a minimal must-use demo guard so disabling the normal plugin does not enable mail or indexing. Retain the official licenses. Creek, streaming, donations, and social services remain station-owned external dependencies.

## Backup and restore

Back up both the database and all uploaded files; custom source alone cannot reconstruct staff edits. The host keeps timestamped database and file backups outside the web root, with restrictive permissions. The temporary deployment runs a daily backup at 03:20 Pacific and retains seven days. Local backups share the host's failure domain: an encrypted off-host backup destination is a remaining production requirement.

To restore, put the KZFR vhost into maintenance, stop only its application, take a precautionary backup of current state, restore the selected database dump and files with matching ownership, restart the application, run checks, then remove maintenance. Never restore or replace another site's database or proxy block. A restore drill must use a separate throwaway database, never overwrite the live demo simply to test a backup. Record record counts and content checks before dropping the drill database.

## Rollback and removal

A code rollback restores the preceding child-theme/plugin release and rechecks representative routes. A schema/content rollback requires its matching database backup. Preserve a copy of the prior proxy configuration and validate any new configuration before reloading. For temporary-site removal, remove only the KZFR vhost and KZFR DNS record, stop only its application/database containers, and retain a final restricted backup according to John's instructions.

## Release checklist

1. Review source-only diff and no-secret scan.
2. Lint PHP and JavaScript; validate inventory before import.
3. Copy only plugin and theme into the document root. Keep import scripts and raw inventory outside it.
4. Run import, record assertions, CMS smoke tests, and backup/restore verification.
5. Verify HTTPS, noindex, robots, sensitive-file blocking, all page families, filter behavior, live listening, external destinations, and the existing unrelated site health.
6. Record revision, versions, evidence, remaining limitations, and operator ownership.
