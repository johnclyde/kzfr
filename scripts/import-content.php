<?php
/**
 * Repeatable public-content import. Run inside an installed WordPress instance:
 * KZFR_CONTENT_FILE=/path/to/station-content.json wp eval-file /path/to/import-content.php
 * Set KZFR_IMPORT_UPDATE=1 only to replace previously imported content with the snapshot.
 * Existing staff edits are preserved by default; no records are deleted.
 */

if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) {
    fwrite(STDERR, "Run this importer using wp eval-file in the WordPress installation.\n");
    exit(1);
}
if (!function_exists('kzfr_register_content')) {
    WP_CLI::error('Activate the kzfr-station plugin before importing.');
}

// WP-CLI eval-file includes this source inside a method scope. Bind shared
// state explicitly so helper functions and the final receipt use one instance.
global $kzfr_counts, $kzfr_map;

$kzfr_file = getenv('KZFR_CONTENT_FILE') ?: dirname(__DIR__) . '/content/station-content.json';
if (!is_readable($kzfr_file)) {
    WP_CLI::error('Content inventory is not readable: ' . $kzfr_file);
}
$kzfr_data = json_decode(file_get_contents($kzfr_file), true);
if (!is_array($kzfr_data) || json_last_error() !== JSON_ERROR_NONE) {
    WP_CLI::error('Content inventory must contain valid JSON object data.');
}
$kzfr_update = getenv('KZFR_IMPORT_UPDATE') === '1';
$kzfr_counts = array('created' => 0, 'updated' => 0, 'preserved' => 0);
$kzfr_map = array();
$kzfr_types = array('programs' => 'kzfr_program', 'events' => 'kzfr_event', 'underwriters' => 'kzfr_underwriter', 'broadcasts' => 'kzfr_broadcast');

/** Convert ordinary HTML to individually editable core blocks. */
function kzfr_import_blocks($content) {
    $content = wp_kses_post((string) $content);
    if (has_blocks($content)) { return $content; }
    if (trim($content) === '') { return ''; }
    // Plain paragraphs/headings/lists become native blocks. Complex source HTML stays an HTML block.
    $parts = preg_split('/(?=<h[2-6]\b|<p\b|<ul\b|<ol\b|<blockquote\b)/i', $content, -1, PREG_SPLIT_NO_EMPTY);
    $blocks = array();
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') { continue; }
        if (preg_match('/^<p(?:\s[^>]*)?>.*<\/p>$/s', $part)) {
            $blocks[] = "<!-- wp:paragraph -->\n" . $part . "\n<!-- /wp:paragraph -->";
        } elseif (preg_match('/^<h([2-6])(?:\s[^>]*)?>(.*)<\/h\1>$/s', $part, $matches)) {
            $blocks[] = '<!-- wp:heading {"level":' . $matches[1] . '} -->' . "\n<h" . $matches[1] . ' class="wp-block-heading">' . $matches[2] . '</h' . $matches[1] . ">\n<!-- /wp:heading -->";
        } else {
            $blocks[] = "<!-- wp:html -->\n" . $part . "\n<!-- /wp:html -->";
        }
    }
    return implode("\n\n", $blocks);
}

/** A stable type:source-slug key avoids dependence on local database IDs. */
function kzfr_import_record($type, $record, $replace) {
    global $kzfr_counts, $kzfr_map;
    $slug = sanitize_title($record['slug']);
    $key = $type . ':' . $slug;
    $found = get_posts(array('post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_kzfr_import_key', 'meta_value' => $key, 'fields' => 'ids', 'no_found_rows' => true));
    $existing = $found ? (int) $found[0] : 0;
    if (!$existing) {
        $by_slug = get_page_by_path($slug, OBJECT, $type);
        if ($by_slug) {
            // An unowned item at the desired slug must never be overwritten by an import.
            if (get_post_meta($by_slug->ID, '_kzfr_import_key', true) !== $key) {
                WP_CLI::warning('Preserving existing unowned ' . $type . ' at /' . $slug . '/.');
                $kzfr_counts['preserved']++;
                $kzfr_map[$key] = (int) $by_slug->ID;
                return (int) $by_slug->ID;
            }
            $existing = (int) $by_slug->ID;
        }
    }
    if ($existing && !$replace) {
        $kzfr_counts['preserved']++;
        $kzfr_map[$key] = $existing;
        return $existing;
    }
    $post = array(
        'post_type' => $type,
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => sanitize_text_field($record['title']),
        'post_content' => kzfr_import_blocks($record['content'] ?? ''),
        'post_excerpt' => sanitize_textarea_field(wp_strip_all_tags($record['excerpt'] ?? $record['summary'] ?? '')),
        'comment_status' => 'closed', 'ping_status' => 'closed',
    );
    if ($existing) { $post['ID'] = $existing; }
    $id = wp_insert_post(wp_slash($post), true);
    if (is_wp_error($id)) { WP_CLI::error('Import failed for ' . $key . ': ' . $id->get_error_message()); }
    update_post_meta($id, '_kzfr_import_key', $key);
    update_post_meta($id, '_kzfr_imported_at', gmdate('c'));
    update_post_meta($id, '_kzfr_source_slug', $slug);
    $fields = kzfr_meta_fields();
    foreach ($fields[$type] ?? array() as $name => $field) {
        if (array_key_exists($name, $record)) {
            update_post_meta($id, 'kzfr_' . $name, call_user_func(kzfr_meta_sanitizer($field[1]), $record[$name]));
        }
    }
    if (isset($record['genres']) && is_array($record['genres']) && in_array($type, array('kzfr_program', 'kzfr_broadcast'), true)) {
        $terms = array_values(array_filter(array_map('sanitize_text_field', $record['genres'])));
        $result = wp_set_object_terms($id, $terms, 'kzfr_genre');
        if (is_wp_error($result)) { WP_CLI::error('Genre mapping failed for ' . $key . ': ' . $result->get_error_message()); }
    }
    // Preserve all source fields privately for a later audited migration or comparison.
    update_post_meta($id, '_kzfr_source_record', kzfr_sanitize_content_value($record));
    $kzfr_counts[$existing ? 'updated' : 'created']++;
    $kzfr_map[$key] = (int) $id;
    return (int) $id;
}

// Validate the complete inventory before any content mutations.
$kzfr_seen = array();
foreach (array_merge($kzfr_types, array('pages' => 'page')) as $collection => $type) {
    if (isset($kzfr_data[$collection]) && !is_array($kzfr_data[$collection])) {
        WP_CLI::error('Inventory collection must be an array: ' . $collection);
    }
    foreach ($kzfr_data[$collection] ?? array() as $record) {
        if (!is_array($record) || empty($record['slug']) || empty($record['title']) || !is_string($record['slug']) || !is_string($record['title'])) {
            WP_CLI::error('Every record requires string slug and title fields in ' . $collection . '.');
        }
        $key = $type . ':' . sanitize_title($record['slug']);
        if (str_ends_with($key, ':') || isset($kzfr_seen[$key])) { WP_CLI::error('Empty or duplicate record key: ' . $key); }
        $kzfr_seen[$key] = true;
        foreach (kzfr_meta_fields()[$type] ?? array() as $name => $field) {
            if (isset($record[$name]) && !is_scalar($record[$name])) { WP_CLI::error('Meta field must be scalar: ' . $key . '/' . $name); }
        }
    }
}
foreach ($kzfr_data['broadcasts'] ?? array() as $record) {
    if (!empty($record['program_slug']) && !isset($kzfr_seen['kzfr_program:' . sanitize_title($record['program_slug'])])) {
        WP_CLI::error('Broadcast references a missing program: ' . $record['slug']);
    }
}

foreach ($kzfr_types as $collection => $type) {
    foreach ($kzfr_data[$collection] ?? array() as $record) { kzfr_import_record($type, $record, $kzfr_update); }
}

$kzfr_defaults = array(
    'home' => array('title' => 'Home', 'content' => '<p>KZFR 90.1 FM is a nonprofit, noncommercial community radio station in Chico, California. It began broadcasting on July 6, 1990.</p>', 'hero_title' => "People Powered\nRadio", 'hero_intro' => 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.', 'source_url' => 'https://kzfr.org/pages/about'),
    'schedule' => array('title' => 'Schedule', 'content' => '<p>All times are Pacific. Programs and rotating slots may change. Check KZFR’s official schedule for current airtimes.</p>', 'source_url' => 'https://www.kzfr.org/schedule'),
    'archives' => array('title' => 'Archives', 'content' => '<p>Find selected broadcasts by program or date, then open the recording on KZFR’s website. Visit the official archive for more recordings. Availability can change.</p>', 'source_url' => 'https://kzfr.studio.creek.org/archives/'),
    'about' => array('title' => 'About KZFR', 'content' => '<p>KZFR is community radio broadcasting from Chico, California. Read the station’s current mission and history at its official website.</p>', 'source_url' => 'https://www.kzfr.org/pages/about'),
    'contact' => array('title' => 'Contact KZFR', 'content' => '<p>Visit KZFR’s official contact page to reach the station.</p>', 'source_url' => 'https://www.kzfr.org/pages/contact'),
    'volunteer' => array('title' => 'Volunteer', 'content' => '<p>Find current volunteer opportunities and application information on KZFR’s official website.</p>', 'source_url' => 'https://www.kzfr.org/pages/volunteer'),
    'donate' => array('title' => 'Donate to KZFR', 'content' => '<p>Donations are handled by KZFR’s official donation service. No payment information is collected here.</p>', 'source_url' => 'https://kzfr-donate.creek.org/'),
    'underwrite' => array('title' => 'Underwrite KZFR', 'content' => '<p>Find current underwriting opportunities and contact information on KZFR’s official website.</p>', 'source_url' => 'https://www.kzfr.org/pages/underwrite'),
    'demo-notes' => array('title' => 'About this preview', 'content' => '<p>This temporary website preview is hosted by Clyde LLM Factory. KZFR has not commissioned, approved, or launched this site. The official station website is <a href="https://www.kzfr.org/">kzfr.org</a>.</p><h2>Station information</h2><p>This preview includes selected programs, broadcasts, events, and underwriters from KZFR’s public website. Listings may change. Follow the official station links for current schedules, event details, and available recordings.</p><h2>Listening and support</h2><p>Live audio and recordings come from official station services. Donations, ticket purchases, volunteer applications, and contact requests take place on the linked official sites. This preview does not collect payments, applications, or messages.</p>'),
);
$kzfr_pages = array();
$kzfr_archive_intros = array();
foreach ($kzfr_data['pages'] ?? array() as $record) {
    $slug = sanitize_title($record['slug']);
    if (in_array($slug, array('programs', 'events', 'underwriters', 'archive-records'), true)) {
        $kzfr_archive_intros[$slug] = wp_kses_post($record['content'] ?? '');
        continue;
    }
    $kzfr_pages[$slug] = array_merge($kzfr_defaults[$slug] ?? array(), $record);
}
foreach ($kzfr_defaults as $slug => $record) {
    if (!isset($kzfr_pages[$slug])) { $kzfr_pages[$slug] = $record; }
}
foreach ($kzfr_pages as $slug => $record) {
    $record['slug'] = $slug;
    kzfr_import_record('page', $record, $kzfr_update);
}
$kzfr_expected_records = count($kzfr_pages);
foreach (array_keys($kzfr_types) as $collection) { $kzfr_expected_records += count($kzfr_data[$collection] ?? array()); }
if (count($kzfr_map) !== $kzfr_expected_records || array_sum($kzfr_counts) !== $kzfr_expected_records) {
    WP_CLI::error('Import accounting mismatch; refusing a success receipt. Expected ' . $kzfr_expected_records . ', mapped ' . count($kzfr_map) . ', accounted ' . array_sum($kzfr_counts) . '.');
}

$kzfr_options = array(
    'kzfr_station' => kzfr_sanitize_station($kzfr_data['station'] ?? array()),
    'kzfr_schedule' => kzfr_sanitize_schedule($kzfr_data['schedule'] ?? array()),
    'kzfr_archive_intros' => $kzfr_archive_intros,
    'kzfr_content_snapshot' => array(
        'fetched_at' => sanitize_text_field($kzfr_data['fetched_at'] ?? ''),
        'version' => sanitize_text_field((string) ($kzfr_data['version'] ?? '1')),
        'sha256' => hash_file('sha256', $kzfr_file),
        'sources' => kzfr_sanitize_content_value($kzfr_data['sources'] ?? array()),
        'integrations' => kzfr_sanitize_content_value($kzfr_data['integrations'] ?? array()),
        'uncertainties' => kzfr_sanitize_content_value($kzfr_data['uncertainties'] ?? array()),
    ),
);
foreach ($kzfr_options as $name => $value) {
    if ($kzfr_update || get_option($name, false) === false) { update_option($name, $value, false); }
}
update_option('kzfr_import_map', $kzfr_map, false);

// Repair an absent/invalid front-page reference even after an interrupted initial
// setup. A valid page chosen by an editor is preserved on repeat imports.
$kzfr_current_front_id = (int) get_option('page_on_front');
$kzfr_current_front = $kzfr_current_front_id > 0 ? get_post($kzfr_current_front_id) : null;
if (!$kzfr_current_front || $kzfr_current_front->post_type !== 'page' || $kzfr_current_front->post_status !== 'publish') {
    update_option('page_on_front', $kzfr_map['page:home']);
    update_option('show_on_front', 'page');
}

// Apply initial site configuration once; future imports preserve editor changes.
if (!get_option('kzfr_configured_at')) {
    update_option('blogname', 'KZFR 90.1 FM');
    update_option('blogdescription', 'Community radio from Chico, California. Independent demonstration.');
    update_option('timezone_string', 'America/Los_Angeles');
    update_option('date_format', 'F j, Y');
    update_option('time_format', 'g:i a');
    update_option('start_of_week', '1');
    update_option('permalink_structure', '/%postname%/');
    update_option('show_on_front', 'page');
    update_option('page_on_front', $kzfr_map['page:home']);
    update_option('blog_public', '0');
    update_option('default_comment_status', 'closed');
    update_option('default_ping_status', 'closed');
    update_option('ping_sites', '');
    update_option('users_can_register', '0');
    if (wp_get_theme('kzfr-community')->exists()) { switch_theme('kzfr-community'); }

    $menu = wp_get_nav_menu_object('Station navigation');
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu('Station navigation');
    if (is_wp_error($menu_id)) { WP_CLI::error('Could not create navigation: ' . $menu_id->get_error_message()); }
    $existing_items = wp_get_nav_menu_items($menu_id);
    if (!$existing_items) {
        $links = array('Programs' => 'programs', 'Schedule' => 'schedule', 'Archives' => 'archives', 'Events' => 'events', 'About' => 'about');
        foreach ($links as $title => $slug) {
            $item = array('menu-item-title' => $title, 'menu-item-status' => 'publish');
            if (isset($kzfr_map['page:' . $slug])) {
                $item['menu-item-type'] = 'post_type';
                $item['menu-item-object'] = 'page';
                $item['menu-item-object-id'] = $kzfr_map['page:' . $slug];
            } else {
                $item['menu-item-type'] = 'custom';
                $item['menu-item-url'] = home_url('/' . $slug . '/');
            }
            $result = wp_update_nav_menu_item($menu_id, 0, $item);
            if (is_wp_error($result)) { WP_CLI::error('Could not create navigation item: ' . $result->get_error_message()); }
        }
    }
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    update_option('kzfr_configured_at', gmdate('c'));
}
flush_rewrite_rules();

$kzfr_summary = array(
    'completed_at' => gmdate('c'),
    'inventory_sha256' => hash_file('sha256', $kzfr_file),
    'replace_existing' => $kzfr_update,
    'counts' => $kzfr_counts,
    'mapped_records' => count($kzfr_map),
    'schedule_rows' => count(get_option('kzfr_schedule', array())),
);
update_option('kzfr_last_import', $kzfr_summary, false);
WP_CLI::success('Content import completed: ' . wp_json_encode($kzfr_summary, JSON_UNESCAPED_SLASHES));
