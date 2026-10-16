# Public content, attribution and refresh

This temporary website preview is hosted by Clyde LLM Factory. KZFR has not commissioned, approved or launched it. The source website and service ownership remain with KZFR and its providers. The preview is labeled and configured to discourage search indexing; noindex is not access control.

## Current coverage

The October 16, 2026 inventory contains 121 program records, 79 schedule rows across seven days, all 196 recordings in the provider's current public catalogue, 95 underwriter or campaign listings, two events and ten supplied page bodies. The archive check retrieved all seven result pages and validated the reported total against 196 unique records. This replaces the earlier limited website-card selection; it does not claim access to every recording in station history.

The provider's show directory contains 132 entries. Exact reviewed aliases and matching titles map those entries to 121 program records, including 36 additional archive-directory programs. A directory entry does not establish a current weekly airtime. Programs absent from the weekly schedule are labeled accordingly.

The checked recordings span August 12, 2025 through October 16, 2026 in Pacific time. The private inventory records the exact retrieval timestamp, checked pages, provider IDs, source URLs, mapping decisions and source hashes. Counts and availability can change on the next check.

| Material | Official source |
| --- | --- |
| Programs and daily schedule | [KZFR programs](https://kzfr.org/programs), including its seven linked weekday pages |
| Individual shows | Observed `/shows/{slug}` program pages on [kzfr.org](https://kzfr.org/) |
| Current recording catalogue | [Public archive endpoint](https://kzfr.studio.creek.org/api/archives), used by the official archive application |
| Archive program directory | [Public show directory endpoint](https://kzfr.studio.creek.org/api/archives/shows-list) |
| Listener archive | [KZFR archive](https://kzfr.studio.creek.org/archives/) and per-record official sharing URLs returned by the provider |
| Live stream | [Official KZFR stream](https://listen.kzfr.org/stream), read from the station's player configuration |
| Donation destination | [KZFR's official donation service](https://kzfr-donate.creek.org/) |
| Events | [Laurie Lewis & Nina Gerber](https://kzfr.org/events/956), [Winter Record Swap](https://kzfr.org/events/957) |
| Local supporters | [Official underwriter directory](https://kzfr.org/underwriters) |
| Station and participation | [About](https://kzfr.org/pages/about), [Contact](https://kzfr.org/pages/contact), [Volunteer](https://kzfr.org/pages/volunteer), [Programmer training](https://kzfr.org/pages/becomeaprogrammer), [Underwriting](https://kzfr.org/pages/underwrite) |

Program descriptions are concise original summaries derived from public descriptions, or factual provider-directory descriptions when no weekly website record exists. Names, dates, schedules, public contacts and service links are factual references. Station editorial review remains necessary before a production launch.

The public source repository should contain project-specific code and documentation, not the extensive station-content snapshot, full raw HTML cache, audio, credentials, deployment backups or private account details. Keep those local/private records out of version control. Code licensing does not grant rights to station names, logos, source media, third-party recordings or imported editorial content. WordPress, its parent theme and other components retain their respective licenses. Project-specific code licensing is recorded in the repository's license file.

## Playback and provider restrictions

Each current recording has a verified HTTPS audio URL on `kzfr-media.s3.us-west-000.backblazeb2.com` and an official sharing URL on `studio-share.creek.org`. The browser plays from the provider. No station audio is copied into or rehosted by WordPress. Playback requires a user gesture and has an official-page fallback. Literal spaces in some official audio paths are safely encoded as `%20` without changing the allowed host or protocol.

The exact provider `disable_seek` boolean is stored in private source metadata. It is true for 101 of the 196 checked recordings. The theme derives the player restriction from that value and does not expose seeking for those items. Staff cannot override it through a content field. Hidden or members-only records are excluded by the fetcher, and an incomplete or excluded catalogue cannot pass the complete-catalogue reconciliation gate.

The checked [official archive application](https://kzfr.studio.creek.org/archives/js/app.182839c7.js) renders playback when an item has `audio.url` and applies the seek restriction from `show.archives_disable_seek`. Its code does not use the separate `archives_disable` field as an item playback prohibition. That field's backend meaning has not been established; no alternative meaning is assumed.

Older website pages expose historical broadcast references whose recordings may be hidden or unavailable. Those references are not advertised as currently playable audio. Source image URLs are retained only as provenance; images were not imported where rights or HTTPS delivery remained unverified. Do not disable certificate checks to obtain an image. The preview's decorative graphics must not be represented as official station artwork.

## Remaining coverage limits

- This is public sampling, not a complete CMS export or the proposed migration of up to 1,500 records and associated media. No staff export or private provider credentials have been supplied.
- Schedule rotations, unusual end times, unnamed slots and conflicting descriptive prose require station review. The demo does not claim to know the current on-air program.
- “Real Radio for Real People” was excluded because its published end date precedes its start date. Event dates were not repaired by guesswork.
- The 196 recordings are the entire current public catalogue at the recorded check, not an unlimited historical collection. Recordings can expire or become unavailable afterward.
- The public archive API is verified against the official client, but no private provider account access is available. Station-specific Spinitron access remains unverified, so playlist fields are empty.
- Underwriter listings include campaign-specific and possibly older records. The source directory is not a contract-status database.
- Contact and volunteer forms, newsletter signup, analytics and payment processing are not enabled in WordPress. Deliberate donations and tickets go to the station's verified external destinations. No messages were sent as part of content preparation.

## Current catalogue refresh procedure

The release uses a one-time full-catalogue import. A repeatable refresh workflow is prepared, but automatic scheduling is disabled. An optional six-hour schedule requires a separate explicit request before activation.

`site/scripts/sync-current-archives.py` reads every public archive page and the provider's show directory. It validates totals, stable IDs, public availability and HTTPS destinations, preserves existing editorial titles and text, and writes a separate candidate inventory. It does not download audio, submit forms or modify WordPress. Requirements are Python 3.9 or later and curl.

```sh
python3 site/scripts/sync-current-archives.py \
  --source /private/path/station-content.json \
  --output /private/path/station-content-candidate.json
```

An optional `--cache` directory retains public evidence outside the public repository. Output must differ from source. The result must mark `archive_coverage.complete_current_public_catalogue` as true, with matching provider, retrieved and playable counts and every page checked. A partial fetch or invalid record leaves the last valid inventory and website untouched.

After validation, review the candidate and take a database backup. Run the normal importer, `apply-archive-catalogue.php` and `assert-content.php` in that order against the candidate. The prepared operational runner uses one database transaction, rolls back on failure and prevents overlapping runs. Publish the candidate snapshot only after all checks succeed. Record its hash, fetched time, counts and reconciliation receipt.

The normal importer preserves staff content by default and never deletes records. Catalogue reconciliation updates provider-owned audio URLs, recording links, dates, program associations, private source metadata and availability while preserving editorial titles and bodies. Source-owned recordings absent from the complete catalogue become drafts; reappearing source-owned recordings can be republished. The old limited website-card records are also retained as drafts. Manual records are untouched, and a current provider record in Trash requires operator review.

Stable recording identities use source keys `kzfr_broadcast:creek-broadcast-{id}`. The `kzfr_archive_catalogue` option records the latest complete check, published and seek-restricted counts, checked pages and inventory hash. Do not use the importer's explicit replacement mode for routine catalogue refreshes: that mode can replace staff content and settings.

## Website and editorial refresh

`site/scripts/refresh-public-content.py` separately reviews public website pages for schedule and program changes. It now invokes the same complete provider-catalogue synchronizer, so it does not replace the catalogue with the old limited website-card selection. It additionally requires BeautifulSoup4. Its output still needs editorial review before publication.

Compare proposed schedule, host, description, event, underwriter and contact changes with staff edits. Recheck status, media permissions and external links. Resolve rotation conflicts with the station instead of inferring assignments. Preserve evidence and the last valid inventory. Keep credentials and private paths in the private operational handoff.

## Future production migration

Obtain and preserve a complete source export, compare record counts against scope, map source IDs and slugs, identify private/draft material, review the 30 agreed priority pages, import approved media over verified HTTPS and report every skipped/conflicting record. Test a restore, redirects, editing, discovery and provider fallbacks on staging before a station-approved cutover.

Keep observed source IDs in the migration ledger. WordPress routes use `/programs/{slug}/`, `/events/{slug}/` and `/archive-records/creek-broadcast-{id}/`; station information moves from `/pages/...` to ordinary page paths. Unsurveyed governance, historical and public-document pages must be inventoried before consolidation or retirement. No redirect or change on the production KZFR domain is authorized by this demonstration.
