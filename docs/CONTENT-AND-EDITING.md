# Content and editing guide

This WordPress installation is an independent demonstration. Changes made here affect the preview only. The official station, donations and audio services remain at KZFR's existing destinations. Do not publish credentials, unpublished station material or private account information in page content.

## Sign in and review changes

Open the site's `/wp-admin/` address and sign in with the account supplied through the private deployment handoff. Credentials are not stored in this guide. Editors manage content; an administrator is needed for Station settings, user accounts, plugins and theme settings. Email is disabled on this demonstration, so an administrator must handle account recovery through the private operational process.

Use **Save draft** and **Preview** while editing. Review the page on a narrow screen as well as desktop, follow its important links and check the source date before publishing or updating. WordPress revisions are available for supported content types. Backups remain necessary for complete recovery.

## Pages and reusable blocks

Open **Pages**, then select Home, About KZFR, Contact KZFR, Volunteer, Donate to KZFR, Underwrite KZFR, Schedule, Archives or About this preview. Change the title and body using WordPress's native blocks. Imported paragraphs and headings are individually editable. More complex HTML, if added later, may appear in a Custom HTML block.

Use the block inserter to add Paragraph, Heading, List, Buttons, Image or the **KZFR station / Volunteer callout** pattern. The theme also supplies a KZFR community callout with a donation link. Keep headings factual and functional; the verified station tagline is **People Powered Radio**. Keep heading levels in order, write descriptive link labels and add useful alternative text to meaningful images. Use a spacer sparingly; the theme handles the normal page rhythm.

The **Station details** panel below the editor contains the source URL. The Home page also has **Home hero title** and **Home hero introduction** fields. The custom templates determine layout and navigation; changing a body block does not replace those templates. Program, event and underwriter listing pages are generated from their content collections rather than ordinary Pages.

Keep the independent-demonstration notice and source caveats until a separately approved production launch. Donation and volunteer pages deliberately link to official external services. This site has no active newsletter, contact or application submission workflow.

## Programs

Open **Programs** and choose a record, or use **Add Program**. Add a clear title, description and short excerpt. In **Station details**, set the host names, schedule description, official archive URL, optional verified playlist URL and source URL. Use **Genres** consistently so listeners can filter programs and recordings.

The October 16, 2026 inventory contains 121 program records from the station website and its official archive directory. A directory record does not establish a current weekly airtime. Programs absent from the weekly schedule are labeled accordingly; do not invent an airtime or a host from their archive listings.

The program's URL slug is the stable link between its detail page, schedule rows and archive records. Avoid changing a published slug without updating those references and a redirect plan. The imported slugs preserve the station's existing short names.

Program schedule descriptions and the weekly schedule are separate editable fields. Update both when a confirmed time changes. Do not infer a current host or rotation week from a shared slot. Leave an unverified playlist URL empty; the demonstration does not invent a Spinitron station identifier.

## Weekly schedule and station links

An administrator opens **Station settings** to update the verified live stream, official service links, public station information and weekly schedule. The site uses **America/Los_Angeles** in **Settings → General** so Pacific daylight-saving changes are handled correctly.

Each schedule row contains a day, start and end time, program references, rotation notes and source URL. Use 24-hour values, for example `18:30`. Add rows using the blank entries at the bottom, then select **Save station settings**. Use the remove checkbox only for a confirmed retirement or corrected duplicate. A missing day or invalid time can omit a row, so check the public schedule after saving.

The source groups overnight entries under the preceding broadcast day. Preserve the **Starts next day** marker for after-midnight segments and the **Ends next day** setting for a slot crossing midnight. Several source rows contain unusual boundaries such as `11:28` or `23:59`; do not round them without station confirmation. An unnamed Friday slot and uncertain rotation assignments are intentionally flagged.

For rotating slots, enter each program title and each corresponding URL slug on its own line, keeping the two lists in the same order. Confirm this week's assignment with the station instead of choosing one for an apparent “on air now” claim. After any edit, open the relevant day and follow each program link. If a source conflict cannot yet be resolved, retain a visible note.

## Events

Open **Events**, then add or edit a record. Enter the title, body and optional short excerpt. In **Station details**, supply the calendar date, time as published, location, official tickets or registration URL and official source URL. Use the separate end-time and doors-time fields when relevant, and include any important arrival instructions in the body. State Pacific time for local events.

Confirm the announcement and destination link before publishing. Do not create a ticket checkout or vendor registration form in the demo. For a cancellation or postponement, make the status clear in the title/body and review the listing page. The current event listings contain November 7 and December 6, 2026 announcements; they are not automatically refreshed.

## Underwriters

Open **Underwriters** and edit the organization or campaign title, description, website URL and source URL. The imported directory preserves event-specific campaigns and near-duplicate organization names. Confirm current participation before combining or removing entries. A public listing is not proof of an active contract.

## Archive records

The October 16, 2026 provider check returned 196 current public recordings across all seven pages. All 196 are represented, including verified direct HTTPS playback destinations. This is the complete currently public catalogue at that check, not every recording ever broadcast. Availability changes as the provider adds and removes recordings.

Open **Archive records** to edit a recording's title, description or browsing categories. The refresh procedure preserves those editorial titles and descriptions. For imported provider records, audio URLs, official recording links, broadcast dates, program associations and availability are refreshed from the provider. Editing those fields manually is not a lasting correction; report mapping or provider-data problems to the operator instead.

Provider IDs, timestamps and playback restrictions are retained privately. The player follows the exact provider `disable_seek` flag: 101 of the 196 checked recordings disable seeking. These recordings offer play/pause without a seek control. There is no editable override for this restriction. The other recordings may offer the available native playback controls.

Audio plays from the provider's official HTTPS host and is never copied into WordPress. Do not upload recordings here or work around an expired recording window. If playback fails, use the official recording-page link. Test program, category and date filters on **Archives**, plus a recording that allows seeking and one that does not.

The current release uses a one-time catalogue import. A repeatable refresh procedure is prepared, but automatic scheduling is disabled. A future recurring refresh requires a separate explicit request. When an operator runs a refresh, it fetches and validates the complete current public catalogue before publication. Source-owned recordings no longer listed become drafts; they are not deleted. The earlier limited website-card records are also retained as unpublished drafts after replacement. Manually authored records are left untouched. A current source-owned record deliberately moved to Trash stops reconciliation for operator review.

## Navigation

Use **Appearance → Menus**. Choose the menu assigned to the **Primary navigation** location, add a page or custom link, arrange the items and save the menu. Keep the main menu short. Review desktop navigation and the mobile Menu button afterward. The persistent Listen and Donate controls are part of the theme.

## Routine publishing check

- Confirm the source, spelling, dates, time zone and outbound destination.
- Preview the title, excerpt and body on mobile and desktop.
- Check links and visible keyboard focus; do not use color alone to communicate meaning.
- Check the related collection or schedule, not just the detail page.
- Keep attribution, demo labeling and honest availability notes intact.
- For a large change or import, take a backup and review on staging first.

The ordinary importer preserves existing imported content by default. Its explicit replacement mode can overwrite staff content and needs a reviewed merge decision and backup. The current-catalogue workflow separately updates provider-owned playback and availability fields while preserving editorial bodies. Refresh procedures and coverage limits are in [CONTENT-SOURCES.md](CONTENT-SOURCES.md).
