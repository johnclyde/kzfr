<?php
/** Read-only CMS/import assertions: wp eval-file /path/to/assert-content.php */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) {
    fwrite(STDERR, "Run this verification using wp eval-file.\n");
    exit(1);
}
// eval-file runs in method scope; assertion helpers need the same shared state.
global $kzfr_checks, $kzfr_failures, $kzfr_expected_minimum;
$kzfr_file = getenv('KZFR_CONTENT_FILE') ?: dirname(__DIR__) . '/content/station-content.json';
$kzfr_data = is_readable($kzfr_file) ? json_decode(file_get_contents($kzfr_file), true) : null;
if (!is_array($kzfr_data)) { WP_CLI::error('Cannot read inventory.'); }
$kzfr_checks = 0;
$kzfr_failures = array();
$kzfr_expected_records = 0;
foreach (array('programs', 'events', 'underwriters', 'broadcasts') as $collection) { $kzfr_expected_records += count($kzfr_data[$collection] ?? array()); }
$kzfr_expected_minimum = max(30, 20 + 4 * $kzfr_expected_records + 2 * count($kzfr_data['broadcasts'] ?? array()));
function kzfr_assert_content($condition, $message) {
    global $kzfr_checks, $kzfr_failures;
    $kzfr_checks++;
    if (!$condition) { $kzfr_failures[] = $message; }
}

kzfr_assert_content(function_exists('kzfr_register_content'), 'Station plugin is active');
if (!function_exists('kzfr_register_content')) { WP_CLI::error('Station plugin is required.'); }
$kzfr_map = get_option('kzfr_import_map', array());
foreach (array('programs' => 'kzfr_program', 'events' => 'kzfr_event', 'underwriters' => 'kzfr_underwriter', 'broadcasts' => 'kzfr_broadcast') as $collection => $type) {
    kzfr_assert_content(post_type_exists($type), 'Content type exists: ' . $type);
    foreach ($kzfr_data[$collection] ?? array() as $record) {
        $key = $type . ':' . sanitize_title($record['slug']);
        $ids = get_posts(array('post_type' => $type, 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_kzfr_import_key', 'meta_value' => $key, 'fields' => 'ids', 'no_found_rows' => true));
        kzfr_assert_content(count($ids) === 1, 'Exactly one record for import key ' . $key);
        if (count($ids) !== 1) { continue; }
        $id = (int) $ids[0];
        kzfr_assert_content(isset($kzfr_map[$key]) && (int) $kzfr_map[$key] === $id, 'Stable ID map for ' . $key);
        kzfr_assert_content(get_post_status($id) === 'publish', 'Record is public: ' . $key);
        kzfr_assert_content(get_post_meta($id, 'kzfr_source_url', true) === kzfr_sanitize_url($record['source_url'] ?? ''), 'Source provenance for ' . $key);
        if ($type === 'kzfr_broadcast') {
            $slug = get_post_meta($id, 'kzfr_program_slug', true);
            kzfr_assert_content(isset($kzfr_map['kzfr_program:' . $slug]), 'Broadcast resolves to program: ' . $key);
            kzfr_assert_content(get_post_meta($id, 'kzfr_broadcast_date', true) === kzfr_sanitize_date($record['broadcast_date']), 'Broadcast date mapping: ' . $key);
            if (!empty($kzfr_data['archive_coverage']['complete_current_public_catalogue'])) {
                $audio = get_post_meta($id, 'kzfr_audio_url', true);
                $audio_parts = wp_parse_url($audio);
                kzfr_assert_content(is_array($audio_parts) && ($audio_parts['scheme'] ?? '') === 'https' && ($audio_parts['host'] ?? '') === 'kzfr-media.s3.us-west-000.backblazeb2.com' && !isset($audio_parts['user']) && !isset($audio_parts['pass']), 'Provider audio is on the verified HTTPS host: ' . $key);
                kzfr_assert_content($audio === kzfr_sanitize_url($record['audio_url'] ?? ''), 'Current provider audio destination matches after safe URL normalization: ' . $key);
                $source_record = get_post_meta($id, '_kzfr_source_record', true);
                kzfr_assert_content(is_array($source_record) && isset($source_record['disable_seek']) && is_bool($source_record['disable_seek']) && $source_record['disable_seek'] === $record['disable_seek'], 'Provider playback restriction retained as exact boolean: ' . $key);
                if (function_exists('kzfr_recording_button')) {
                    ob_start();
                    kzfr_recording_button($id);
                    $button = ob_get_clean();
                    kzfr_assert_content(str_contains($button, 'data-disable-seek="' . ($record['disable_seek'] ? 'true' : 'false') . '"'), 'Public player derives the provider seek restriction: ' . $key);
                }
            }
        }
    }
}
foreach (array('home', 'schedule', 'archives', 'about', 'contact', 'volunteer', 'donate', 'underwrite', 'demo-notes') as $slug) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    kzfr_assert_content($page && $page->post_status === 'publish', 'Editable page exists: ' . $slug);
}
kzfr_assert_content((int) get_option('page_on_front') === (int) ($kzfr_map['page:home'] ?? 0), 'Home is the front page');
kzfr_assert_content(get_option('show_on_front') === 'page', 'Static front page is configured');
kzfr_assert_content(get_option('timezone_string') === 'America/Los_Angeles', 'Station timezone is configured');
kzfr_assert_content(count(get_option('kzfr_schedule', array())) === count($kzfr_data['schedule'] ?? array()), 'All sampled weekly schedule rows imported');
kzfr_assert_content((get_option('kzfr_station', array())['donate_url'] ?? '') === 'https://kzfr-donate.creek.org/', 'Donations point to the official service');
kzfr_assert_content(get_option('blog_public') === '0', 'Search indexing is disabled');
kzfr_assert_content(apply_filters('wp_sitemaps_enabled', true) === false, 'Core sitemap is disabled');
kzfr_assert_content(apply_filters('pre_wp_mail', null, array()) === false, 'Outgoing email is blocked without attempting delivery');
kzfr_assert_content(apply_filters('xmlrpc_enabled', true) === false, 'XML-RPC authentication disabled');
kzfr_assert_content(apply_filters('xmlrpc_methods', array('pingback.ping' => 'x')) === array(), 'XML-RPC methods disabled');
kzfr_assert_content(apply_filters('pings_open', true, 0) === false, 'Pingbacks disabled');
$headers = apply_filters('wp_headers', array('X-Pingback' => 'test'));
kzfr_assert_content(($headers['X-Robots-Tag'] ?? '') === 'noindex, nofollow, noarchive', 'Noindex response header configured');
kzfr_assert_content(!isset($headers['X-Pingback']), 'Pingback header removed');
$robots = apply_filters('wp_robots', array('index' => true));
kzfr_assert_content(!isset($robots['index']) && !empty($robots['noindex']), 'HTML robots declaration enforces noindex');
kzfr_assert_content(kzfr_sanitize_url('javascript:alert(1)') === '', 'Unsafe URL protocol rejected');
kzfr_assert_content(kzfr_sanitize_url('data:text/html,example') === '', 'Data URL protocol rejected');
kzfr_assert_content(kzfr_sanitize_date('2026-02-30') === '', 'Invalid calendar date rejected');
kzfr_assert_content(kzfr_sanitize_date('2026-10-16') === '2026-10-16', 'Valid calendar date accepted');
kzfr_assert_content(kzfr_sanitize_time('25:00') === '', 'Invalid schedule time rejected');
kzfr_assert_content(kzfr_sanitize_schedule(array(array('day' => 'Monday', 'start' => '23:00', 'end' => '02:00', 'program_slug' => 'sample', 'program_title' => 'Sample')))[0]['end_day_offset'] === 1, 'Overnight schedule rollover is preserved');
if (!empty($kzfr_data['archive_coverage']['complete_current_public_catalogue'])) {
    $catalogue_receipt = get_option('kzfr_archive_catalogue', array());
    $catalogue_expected = (int) $kzfr_data['archive_coverage']['imported_playable_records'];
    $provider_count = 0;
    $legacy_count = 0;
    foreach (get_posts(array('post_type' => 'kzfr_broadcast', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_kzfr_import_key', 'fields' => 'ids', 'no_found_rows' => true)) as $broadcast_id) {
        $source_key = get_post_meta($broadcast_id, '_kzfr_import_key', true);
        if (preg_match('/^kzfr_broadcast:creek-broadcast-[1-9]\d*$/', $source_key)) { $provider_count++; }
        if (preg_match('/^kzfr_broadcast:broadcast-[1-9]\d*$/', $source_key)) { $legacy_count++; }
    }
    kzfr_assert_content($provider_count === $catalogue_expected, 'Published provider catalogue matches complete checked inventory');
    kzfr_assert_content($legacy_count === 0, 'Superseded source-owned legacy archive samples are not published');
    kzfr_assert_content(($catalogue_receipt['published_records'] ?? -1) === $catalogue_expected && ($catalogue_receipt['complete_current_public_catalogue'] ?? false) === true, 'Catalogue receipt confirms reconciliation');
    kzfr_assert_content(($catalogue_receipt['fetched_at'] ?? '') === ($kzfr_data['archive_coverage']['retrieved_at'] ?? ''), 'Catalogue receipt has the current fetched timestamp');
}
if ($kzfr_checks < $kzfr_expected_minimum) { WP_CLI::error('Assertion accounting mismatch; refusing a success receipt.'); }
if ($kzfr_failures) {
    foreach ($kzfr_failures as $failure) { WP_CLI::warning($failure); }
    WP_CLI::error(count($kzfr_failures) . ' of ' . $kzfr_checks . ' assertions failed.');
}
WP_CLI::success($kzfr_checks . ' CMS, import mapping, and demo-protection assertions passed.');
