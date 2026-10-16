# Preview verification record

Date: October 16, 2026, Pacific time. Temporary preview: https://kzfr.clydellmfactory.com . This records checks of an independent demonstration, not KZFR's production acceptance.

## Implemented

All ten proposed template families are implemented in a real WordPress installation: home, program listing, program detail, schedule, archive search/playback, event listing, event detail, general information, donation landing, and underwriting landing. Additional pages include the underwriter directory, contact and volunteer information, and preview notes.

The live content includes 121 program records, 79 weekly schedule entries, 196 current public recordings from all seven provider catalogue pages, two unambiguous future events, 95 underwriter/campaign records, and nine main pages. The catalogue was reconciled in a transaction; 119 superseded sample records were retained as drafts. This is the complete currently public playable catalogue at retrieval, not every historical recording or a staff export. Automatic archive refresh is prepared but disabled; refreshes are operator-run.

Five design options share the same content and audio controls. Original greens uses the station's original palette and header texture; the other options explore cream editorial, minimal white, blue listening, and dark music-focused presentation. The second design pass changes homepage composition as well as colors. All five were visually checked at desktop width and at a measured 390-pixel viewport; none had horizontal page overflow. Screenshots remain in the local deployment evidence.

## Checks completed

- PHP syntax checks on custom theme, plugin and deployment/import entry points. JavaScript syntax and Python compilation checks passed.
- 2,870 WordPress content assertions and 2,913 CMS smoke assertions passed on the complete catalogue. These include stable record mapping, editor-role access, native blocks, settings round trips, edit-preserving repeat imports, provider URL validation, catalogue totals and the 101 recordings whose provider disables seeking.
- WordPress core checksums verified; the official parent theme is installed separately.
- HTTP checks passed for 26 representative routes and security endpoints plus 123 additional content links. The listing pages expose 135 distinct internal content links. Program title/host search, empty results, archive program/date/category filters, event month filtering, donation destination, noindex headers, robots, and sensitive-file blocking passed.
- Live playback began only after a user gesture and continued through internal page changes. A current seek-restricted recording played with pause/volume controls and no seek control; playback continued through a design change and program navigation. Stream and recording files remain on the station's providers.
- After the second layout pass, live audio continued across design switching and navigation to the donation page. The selected design remained active in header and footer links. Archive program/date filters remained selected after changing designs. The mobile menu opened, exposed the station links and closed after navigation.
- All 35 primary palette text/surface pairs meet 4.5:1 normal-text contrast. A design review also found and fixed the evening skip-link, evening chooser focus, and original dark-footer focus contrasts.
- The player reserves its actual height for scrolling and keyboard focus. No decorative up-right arrows remain in visitor-facing theme copy.
- The final full-catalogue database/files backup was restored to an isolated throwaway application/database. All 2,870 assertions and published record counts matched. The temporary restore resources were removed after verification. A fresh backup followed the focus-style patch.
- The unrelated application sharing the host continued to return a healthy HTTP response.

## Performance observations

Unthrottled HTTP fetches from the test computer took 0.37–0.76 seconds for representative home, program, schedule, archive and event pages. Homepage HTML was approximately 38 KB and the full archive HTML approximately 195 KB before the second layout pass. These are network fetch observations, not a mobile rendering benchmark or a promise of three-second loads. No font service, large photography, analytics or autoplay download is required by the preview.

## Remaining acceptance work

- Station review and selection of a design, final logo/media files, content ownership review, and consolidation of ambiguous legacy program identities.
- A staff export and migration reconciliation, including private/unavailable historical content and media. Public-source collection cannot certify a complete CMS migration.
- Real-device Safari/Chrome/Firefox checks, representative screen-reader sessions, zoom/reflow testing and a full WCAG 2.2 AA audit. The implemented controls and spot checks do not establish certification.
- Mobile performance measurement under recorded network/device conditions.
- Staff-approved production forms, mail, analytics, social integrations, redirects and any authenticated provider setup.
- Individual staff accounts, production second-factor/account recovery decisions, and encrypted off-host backups. Local backups alone do not protect against losing the host.
- The two proposed training sessions, design revision rounds, client signoff and production launch have not happened. The proposal has not been sent as part of this work.

Detailed runtime receipts, screenshots and restricted deployment materials remain in the local opportunity folder and are not published in this source repository. Administrator credentials remain in the protected server secret store.
