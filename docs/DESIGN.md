# Five preview design options

The preview offers five whole-site visual options with separate homepage compositions. They use the same programs, recordings, schedules, events, and navigation. The comparison controls are part of the temporary preview. They are not proposed station navigation or new brand slogans.

| Option | Direction | Shareable preview |
|---|---|---|
| Original greens | A familiar community-radio portal: cream and tan, original wood texture, green highlights, a recording sidebar, programs, and events | https://kzfr.clydellmfactory.com/?design=original |
| Soft cream | A centered editorial masthead, ruled program columns, an events sidebar, warm paper, and Georgia headings | https://kzfr.clydellmfactory.com/?design=cream |
| Open white | A minimal radio-station masthead, integrated listening, program rows, and community events on white | https://kzfr.clydellmfactory.com/?design=white |
| River blue | A centered listening area, latest recordings, and wide program rows on pale blue surfaces | https://kzfr.clydellmfactory.com/?design=blue |
| Warm evening | A large typographic frequency masthead, music-journal program columns, and recording lists in a warm dark palette | https://kzfr.clydellmfactory.com/?design=evening |

Original greens is the default. This respects the possibility that KZFR likes its existing identity and needs functional repairs. The preview does not presume a rebrand. Typography uses local system fonts and Georgia, with no external font service. It approximates, rather than exactly reproduces, the original Source Sans Pro/PT Sans/Arvo typography.

## Original palette evidence

The official [main stylesheet](https://kzfr.org/theme/51/css/css_main.css?x=1780599859), inspected October 16, 2026, supplies the cream `#f2ede3`, white cards, tan header `#d4b891`, tan tabs `#ead5b7`, label greens `#009561` and `#007f46`, Listen green `#00734a`, and Donate green `#00704a`. The preview uses the darker original greens for small text to maintain readable contrast. Its pale yellow broadcast icon recalls the original `#ffff99` highlights.

`assets/original-wood.jpg` is the 21,081-byte [original public header texture](https://kzfr.org/theme/51/images/header/wood_darker1.jpg), retrieved over verified HTTPS. It is a source reference for this speculative preview, not project-created artwork. See THIRD-PARTY-NOTICES.md. Broken media on the station's separate media host were not copied or loaded with certificate checks disabled. Production logo and photo assets still require a working source or staff export.

## Editable implementation

`wp-content/themes/kzfr-community/assets/site.css` supplies shared layout. `assets/designs.css` supplies the five palettes and directory/listing variations. `template-parts/home-*.php` and matching `assets/home-*.css` supply distinct homepage structures. The HTML `data-design` value comes from an allowlisted query parameter. Each option has a link, so it can be shared and loaded without JavaScript. Enhanced internal navigation retains the choice and keeps audio playing; changing options fetches the chosen homepage composition, preserves existing archive filters, and retains audio playback. Regular page links and filter forms also preserve an explicit design choice when JavaScript is unavailable.

The station's exact “People Powered Radio” tagline and plain functional headings are used throughout. No invented station slogans or decorative up-right arrows are part of the design. The original CSS geometry is decorative and hidden from assistive technology. No stock or generated photographs are presented as real station material.

Desktop layouts use a maximum 1440px canvas. Program cards move from four columns to two, and general pages and the hero stack on phones. Navigation becomes a Menu button below 900px. The persistent audio bar reserves its actual height so content and keyboard focus remain accessible.

## Accessibility and approval

The design provides labeled controls, visible keyboard focus, a skip link, semantic landmarks, no autoplay, reduced-motion support, and ordinary GET filters. Internal transitions focus the new main heading. Measured contrast and browser checks are recorded in ACCEPTANCE.md. Those checks do not replace a complete assistive-technology audit.

The five implemented options support design discussion. Station approval, two consolidated revision rounds, production media, listener testing, and final design signoff have not occurred. No staff training or client acceptance is claimed.
