# KZFR community radio demonstration

An independent WordPress redesign demonstration by John Clyde. This is not commissioned, approved, or operated by KZFR. KZFR's official website is [kzfr.org](https://www.kzfr.org/).

**Temporary demonstration:** https://kzfr.clydellmfactory.com

The project includes a Twenty Twenty-One child theme, a small station content plugin, an idempotent public-content importer, and operating and editing guides. No premium theme or third-party plugin is required. Audio remains at the station's stream and Creek; donations go directly to the station's verified service.

## Included experiences

- Home, program directory and detail, weekly schedule, searchable current recording catalogue, event directory and detail, general information, donation landing, and underwriting landing.
- Editable programs, schedule rows and rotations, events, underwriters, archive metadata, pages, menus, and station links.
- Live audio begins only after a listener activates playback. Internal navigation preserves playback, with regular full-page links as a fallback.
- Server-rendered program/category, archive/program/category/date, event/month, and underwriter/name filters work without JavaScript.
- Five switchable whole-site designs, including the original green, cream, tan and white palette. Share a design with the `design` query parameter; see [design options](docs/DESIGN.md).
- Responsive layouts, keyboard focus, labeled controls, skip navigation, reduced-motion support, and clear external-service alternatives.
- Demo noindex controls, disabled outgoing mail and registration, no contact submissions, no payment collection.

## Source layout

`wp-content/themes/kzfr-community/` contains the child theme and editable design styles. `wp-content/plugins/kzfr-station/` contains content types, metadata, settings, and demo protection. `scripts/` contains content collection/import and assertions. `tests/` contains integration checks. `docs/` contains the handoff.

The full public-source snapshot and raw station pages are intentionally excluded from this source repository. No application proposals, personal correspondence, server credentials, production databases, or unrelated product source belong here.

## Install on an existing WordPress site

Use a **separate staging installation**, PHP 8.3 or newer, MariaDB 10.11 or newer (or MySQL 8), and HTTPS. The temporary deployment uses PHP 8.3 and MariaDB 11.4. Install WordPress from official sources and the maintained Twenty Twenty-One parent theme. Set PHP `max_input_vars=5000` for the weekly schedule editor.

Copy the custom plugin and child theme directories into the corresponding WordPress `wp-content` directories. Keep this source checkout and its import scripts outside the public document root. Set the following paths for your installation. The included example is a small, sourced fixture for trying installation without the private full inventory.

```sh
KZFR_WP_PATH=/path/to/wordpress
KZFR_SOURCE_PATH=/private/path/kzfr-source
export KZFR_CONTENT_FILE="$KZFR_SOURCE_PATH/content/example.json"

wp --path="$KZFR_WP_PATH" plugin activate kzfr-station
wp --path="$KZFR_WP_PATH" theme install twentytwentyone
wp --path="$KZFR_WP_PATH" theme activate kzfr-community
wp --path="$KZFR_WP_PATH" eval-file "$KZFR_SOURCE_PATH/scripts/import-content.php"
wp --path="$KZFR_WP_PATH" eval-file "$KZFR_SOURCE_PATH/scripts/apply-archive-catalogue.php"
wp --path="$KZFR_WP_PATH" eval-file "$KZFR_SOURCE_PATH/scripts/assert-content.php"
wp --path="$KZFR_WP_PATH" eval-file "$KZFR_SOURCE_PATH/tests/cms-smoke.php"
```

The example is not the complete content package. For the reviewed full inventory, set `KZFR_CONTENT_FILE` to its private absolute path instead. Catalogue reconciliation safely skips the minimal fixture; with a complete provider inventory, it retires superseded source-owned records before the assertions run. All checks use the same exported inventory path.

Keep the scripts and content inventory **outside the public document root**. The importer creates editable pages and navigation, imports records without deleting anything, and preserves existing staff edits on subsequent runs. `KZFR_IMPORT_UPDATE=1` explicitly replaces previously imported snapshot content; take a backup before using it.

Review [content sources](docs/CONTENT-SOURCES.md), [the editing guide](docs/CONTENT-AND-EDITING.md), [operations](docs/OPERATIONS.md), [design](docs/DESIGN.md), and [the acceptance record](docs/ACCEPTANCE.md).

## Deployment status

The hosted demonstration includes the complete currently public recording catalogue at its last refresh, plus publicly accessible station pages and directories. It is not a completed client migration or approved production launch. See the acceptance record for measured checks and remaining station inputs. The prototype does not establish a contract or claim delivered staff training.

## License

Project-specific source code is GPL-2.0-or-later. WordPress and Twenty Twenty-One retain their own licenses. KZFR's name, station material, public metadata, and third-party marks remain with their respective owners. The source license does not grant rights to those materials or imply station endorsement. See [third-party notices](docs/THIRD-PARTY-NOTICES.md), including the original-site wood texture.
