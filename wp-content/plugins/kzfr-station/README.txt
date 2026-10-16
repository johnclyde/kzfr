KZFR Station Content 1.0.0
GPL-2.0-or-later

Purpose
-------
Project-specific content types, native editor fields, editable station links and
weekly schedule, reusable blocks, and protective settings for an independent demo.
Requires WordPress 6.6+ and PHP 8.1+. No paid dependencies and no network API calls.

Content contract
----------------
kzfr_program -> /programs/; kzfr_event -> /events/;
kzfr_underwriter -> /underwriters/; kzfr_broadcast -> /archive-records/.
kzfr_genre is shared by programs and archive records.
Meta fields are defined in kzfr_meta_fields(); all public keys start with kzfr_.
Source provenance is public kzfr_source_url plus private _kzfr_source_record.
Options kzfr_station, kzfr_schedule, kzfr_archive_intros and kzfr_content_snapshot
are populated by scripts/import-content.php. The theme reads these options.

Editing
-------
Use the Programs, Events, Underwriters and Archive records admin sections.
Titles and body content use the WordPress editor. The Station details box edits
host names, sources, dates, destinations and program references. Genres use the
standard taxonomy editor. A blank audio URL is intentional when only an official
recording page, not a directly usable audio endpoint, is verified.

Station settings edits station information and weekly schedule. Times use
America/Los_Angeles. Choose a day and valid start/end times to add a row. Program
slugs should match the program URLs. For rotating slots, enter one title per line
and one corresponding slug per line; the public schedule reads these same lists.
Use the next-calendar-day checkboxes for after-midnight broadcast-day entries.
Rotation notes preserve unconfirmed assignments.
The five blank rows at the bottom support new entries; save to get more rows.
Set PHP max_input_vars=5000 to retain the complete weekly schedule form.
Pages, including Home hero fields and Demo notes, use the native page editor.
The Volunteer callout pattern is available under KZFR station in the block inserter.

Import behavior
---------------
Activate this plugin before invoking the WP-CLI importer. Use KZFR_CONTENT_FILE
to point at the inventory JSON. Default imports preserve existing imported content
and staff edits; new source keys create records. KZFR_IMPORT_UPDATE=1 explicitly
replaces imported records and station options with the snapshot. Neither mode
deletes records. Unowned records with a matching slug are preserved. Stable import
keys combine the content type and source slug, so repeated imports do not duplicate.
Run scripts/assert-content.php with wp eval-file after initial import. Assertions
compare the snapshot and are intended before staff edits. No test sends email.
Run tests/cms-smoke.php for native editor, role, and schedule save/restore checks.
KZFR_RUN_IMPORT_TESTS=1 additionally tests an actual repeat import against a
temporary staff edit, then restores the original content and metadata.

Current archive catalogue
-------------------------
The current catalogue uses the station's public archive API and plays verified
official HTTPS audio URLs directly. Audio files are not copied into WordPress.
Provider IDs, UTC timestamps and the exact disable_seek boolean remain in private
source metadata. The theme reads that boolean to honor playback restrictions;
there is no editor checkbox that can override the provider's restriction.

After import, scripts/apply-archive-catalogue.php reconciles only an inventory
explicitly marked complete_current_public_catalogue with matching source totals
and all pages checked. Missing coverage in the tiny example fixture safely skips
reconciliation; a partial or malformed declared catalogue fails. Source hosts,
HTTPS destinations, IDs and boolean restrictions are validated before mutation.
Source-owned legacy sample broadcasts and no-longer-available provider recordings
become drafts, never deletions. Manual records are untouched. Existing provider
records retain staff titles and body text while their audio destinations, dates,
program links, source metadata and availability refresh from the current catalogue.
The initial Archives introduction is replaced once; subsequent staff edits survive.
The catalogue option records exact source fetch time, counts and inventory hash.
This is the complete currently public catalogue at the checked time, not a claim
to contain every broadcast in the station's history.

Managed refresh sequence
------------------------
Use the public catalogue sync script to fetch and validate every provider page into
a candidate snapshot. Keep the last valid inventory if fetching or checks fail.
After validation, take a database backup before mutations. Run the normal importer,
apply-archive-catalogue.php and assert-content.php in that order. Keep them inside
one database transaction with rollback on any failure, and use an operator lock to
prevent overlapping refreshes. Publish the candidate snapshot only after success.
Monitor the resulting receipt and logs. The current release uses a one-time import.
An optional six-hour operational refresh is prepared but disabled; enabling a
recurring schedule requires an explicit request. This plugin installs no cron job.

Demonstration protections
------------------------
While this plugin is active, search indexing is forced off; robots.txt disallows
all crawlers and HTML/HTTP robots controls request noindex/nofollow/noarchive.
Core sitemaps, XML-RPC methods, pings, comments and outgoing wp_mail are disabled.
Anonymous REST user listings and public author archives are unavailable.
Admin editing and ordinary public content REST routes remain available.
Noindex is not access control. Credentials and private source must stay off-site.
Core automatic-update notices and password-reset email are blocked too. An operator
must use secured WP-CLI access for account recovery and monitor updates externally.

Production transition
---------------------
These demonstration protections deliberately remain active even if an editor
changes WordPress reading settings. Any production use needs an explicit reviewed
configuration change, station acceptance, verified provider integrations, staff
training and operational ownership. Deactivating this plugin also hides the custom
content types, though their database records remain. Do not disable it merely to
enable email or indexing on this speculative site.
