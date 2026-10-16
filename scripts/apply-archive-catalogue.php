<?php
/**
 * Reconcile a validated complete current public catalogue after import-content.php.
 * KZFR_CONTENT_FILE=/private/path/station-content.json wp eval-file /path/to/this.php
 * Source-owned playback fields are refreshed; editorial titles/bodies are preserved.
 * Removed source records become drafts. No records are deleted.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) { exit(1); }
if (!function_exists('kzfr_sanitize_content_value') || (string) get_option('blog_public') !== '0') {
    WP_CLI::error('The active, noindex KZFR station plugin is required.');
}
$kzfr_catalogue_file = getenv('KZFR_CONTENT_FILE') ?: dirname(__DIR__) . '/content/station-content.json';
if (!is_readable($kzfr_catalogue_file)) { WP_CLI::error('Cannot read the archive inventory.'); }
$kzfr_catalogue_data = json_decode(file_get_contents($kzfr_catalogue_file), true);
if (!is_array($kzfr_catalogue_data)) { WP_CLI::error('Archive inventory must be a JSON object.'); }
if (!isset($kzfr_catalogue_data['archive_coverage'])) {
    WP_CLI::log('This inventory has no complete provider catalogue; archive reconciliation skipped.');
    return;
}
$kzfr_coverage = $kzfr_catalogue_data['archive_coverage'];
if (!is_array($kzfr_coverage) || ($kzfr_coverage['complete_current_public_catalogue'] ?? null) !== true) {
    WP_CLI::error('Refusing archive reconciliation: the complete-current-catalogue flag is not true.');
}
$kzfr_catalogue_records = $kzfr_catalogue_data['broadcasts'] ?? null;
$kzfr_catalogue_expected = (int) ($kzfr_coverage['imported_playable_records'] ?? -1);
$kzfr_catalogue_pages = $kzfr_coverage['pages_checked'] ?? array();
$kzfr_catalogue_fetched = $kzfr_coverage['retrieved_at'] ?? '';
if (!is_array($kzfr_catalogue_records) || $kzfr_catalogue_expected < 1 || count($kzfr_catalogue_records) !== $kzfr_catalogue_expected ||
    (int) ($kzfr_coverage['provider_total'] ?? -1) !== (int) ($kzfr_coverage['retrieved_unique_records'] ?? -2) ||
    (int) ($kzfr_coverage['provider_total'] ?? 0) < $kzfr_catalogue_expected ||
    !is_array($kzfr_catalogue_pages) || !$kzfr_catalogue_pages || $kzfr_catalogue_pages !== range(1, count($kzfr_catalogue_pages)) ||
    !is_string($kzfr_catalogue_fetched) || strtotime($kzfr_catalogue_fetched) === false ||
    ($kzfr_coverage['endpoint'] ?? '') !== 'https://kzfr.studio.creek.org/api/archives') {
    WP_CLI::error('Refusing archive reconciliation: source totals, dates, endpoint, or checked pages do not validate.');
}

function kzfr_catalogue_url_allowed($url, $hosts) {
    if (!is_string($url) || $url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) { return false; }
    // The official provider returns some file paths containing literal spaces.
    // Encode those path spaces; reject any other sanitizer-driven alteration.
    $normalized = str_replace(' ', '%20', $url);
    if (kzfr_sanitize_url($normalized) !== $normalized) { return false; }
    $parts = wp_parse_url($normalized);
    return is_array($parts) && ($parts['scheme'] ?? '') === 'https' &&
        in_array(strtolower($parts['host'] ?? ''), $hosts, true) &&
        !isset($parts['user']) && !isset($parts['pass']) &&
        (!isset($parts['port']) || (int) $parts['port'] === 443);
}

// Validate every record and its existing stable import identity before mutations.
$kzfr_current_catalogue = array();
foreach ($kzfr_catalogue_records as $record) {
    if (!is_array($record) || !isset($record['provider_id']) || !is_scalar($record['provider_id']) ||
        !preg_match('/^[1-9]\d*$/', (string) $record['provider_id']) ||
        ($record['slug'] ?? '') !== 'creek-broadcast-' . (string) $record['provider_id'] ||
        !array_key_exists('disable_seek', $record) || !is_bool($record['disable_seek']) ||
        !kzfr_catalogue_url_allowed($record['audio_url'] ?? null, array('kzfr-media.s3.us-west-000.backblazeb2.com')) ||
        !kzfr_catalogue_url_allowed($record['source_url'] ?? null, array('studio-share.creek.org', 'kzfr.studio.creek.org')) ||
        kzfr_sanitize_date($record['broadcast_date'] ?? '') !== ($record['broadcast_date'] ?? '') || empty($record['broadcast_date']) ||
        !is_string($record['program_slug'] ?? null) || !get_page_by_path($record['program_slug'], OBJECT, 'kzfr_program')) {
        WP_CLI::error('Refusing archive reconciliation: unsafe or incomplete provider record ' . sanitize_text_field($record['slug'] ?? '(unnamed)') . '.');
    }
    $key = 'kzfr_broadcast:' . $record['slug'];
    if (isset($kzfr_current_catalogue[$key])) { WP_CLI::error('Duplicate current catalogue key: ' . $key); }
    $ids = get_posts(array('post_type' => 'kzfr_broadcast', 'post_status' => array('publish', 'draft', 'pending', 'private', 'future', 'trash'), 'posts_per_page' => -1, 'meta_key' => '_kzfr_import_key', 'meta_value' => $key, 'fields' => 'ids', 'no_found_rows' => true));
    if (count($ids) !== 1) { WP_CLI::error('Expected one imported record before reconciliation: ' . $key); }
    if (get_post_status($ids[0]) === 'trash') { WP_CLI::error('Current provider record is in Trash and requires operator review: ' . $key); }
    $kzfr_current_catalogue[$key] = array('id' => (int) $ids[0], 'record' => $record);
}

$kzfr_catalogue_owned = get_posts(array('post_type' => 'kzfr_broadcast', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_kzfr_import_key', 'fields' => 'ids', 'no_found_rows' => true));
$kzfr_catalogue_drafted_legacy = 0;
$kzfr_catalogue_drafted_expired = 0;
foreach ($kzfr_catalogue_owned as $id) {
    $key = get_post_meta($id, '_kzfr_import_key', true);
    $legacy = (bool) preg_match('/^kzfr_broadcast:broadcast-[1-9]\d*$/', $key);
    $expired = (bool) preg_match('/^kzfr_broadcast:creek-broadcast-[1-9]\d*$/', $key) && !isset($kzfr_current_catalogue[$key]);
    if (!$legacy && !$expired) { continue; }
    $result = wp_update_post(array('ID' => (int) $id, 'post_status' => 'draft'), true);
    if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
    update_post_meta($id, '_kzfr_catalogue_retired_at', gmdate('c'));
    update_post_meta($id, '_kzfr_catalogue_retired_reason', $legacy ? 'Replaced legacy metadata sample with provider catalogue.' : 'Not present in the latest complete current public provider catalogue.');
    if ($legacy) { $kzfr_catalogue_drafted_legacy++; } else { $kzfr_catalogue_drafted_expired++; }
}

$kzfr_catalogue_no_seek = 0;
foreach ($kzfr_current_catalogue as $entry) {
    $id = $entry['id'];
    $record = $entry['record'];
    // These destinations and playback restrictions belong to the provider. Staff
    // title/body edits remain intact, and restrictions are never editor overrides.
    foreach (array('audio_url', 'source_url', 'broadcast_date', 'program_slug') as $field) {
        $fields = kzfr_meta_fields()['kzfr_broadcast'];
        update_post_meta($id, 'kzfr_' . $field, call_user_func(kzfr_meta_sanitizer($fields[$field][1]), $record[$field]));
    }
    update_post_meta($id, '_kzfr_source_record', kzfr_sanitize_content_value($record));
    update_post_meta($id, '_kzfr_catalogue_checked_at', $kzfr_catalogue_fetched);
    delete_post_meta($id, '_kzfr_catalogue_retired_at');
    delete_post_meta($id, '_kzfr_catalogue_retired_reason');
    if (get_post_status($id) !== 'publish') {
        $result = wp_update_post(array('ID' => $id, 'post_status' => 'publish'), true);
        if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
    }
    if ($record['disable_seek']) { $kzfr_catalogue_no_seek++; }
}

$kzfr_catalogue_archives = get_page_by_path('archives', OBJECT, 'page');
if ($kzfr_catalogue_archives && get_post_meta($kzfr_catalogue_archives->ID, '_kzfr_import_key', true) === 'page:archives') {
    $body = '<!-- wp:paragraph --><p>Browse current recordings from KZFR. Availability changes over time.</p><!-- /wp:paragraph -->' . "\n\n" . '<!-- wp:paragraph --><p><a href="https://kzfr.studio.creek.org/archives/">Browse all recordings on KZFR’s archive</a>.</p><!-- /wp:paragraph -->';
    // Change the initial catalogue introduction once, then preserve staff copy edits
    // on automated refreshes. Playback/availability fields still refresh every run.
    if (!get_post_meta($kzfr_catalogue_archives->ID, '_kzfr_catalogue_intro_applied', true)) {
        $result = wp_update_post(wp_slash(array('ID' => $kzfr_catalogue_archives->ID, 'post_content' => $body)), true);
        if (is_wp_error($result)) { WP_CLI::error($result->get_error_message()); }
        update_post_meta($kzfr_catalogue_archives->ID, '_kzfr_catalogue_intro_applied', gmdate('c'));
    }
}

$kzfr_catalogue_published = get_posts(array('post_type' => 'kzfr_broadcast', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_kzfr_import_key', 'fields' => 'ids', 'no_found_rows' => true));
$kzfr_catalogue_published_count = 0;
foreach ($kzfr_catalogue_published as $id) {
    $key = get_post_meta($id, '_kzfr_import_key', true);
    if (preg_match('/^kzfr_broadcast:creek-broadcast-[1-9]\d*$/', $key)) { $kzfr_catalogue_published_count++; }
    if (preg_match('/^kzfr_broadcast:broadcast-[1-9]\d*$/', $key)) { WP_CLI::error('A legacy source-owned sample remained published.'); }
}
if ($kzfr_catalogue_published_count !== $kzfr_catalogue_expected) { WP_CLI::error('Published provider catalogue count does not match validated inventory.'); }
$kzfr_catalogue_receipt = array(
    'complete_current_public_catalogue' => true,
    'fetched_at' => $kzfr_catalogue_fetched,
    'applied_at' => gmdate('c'),
    'source_endpoint' => $kzfr_coverage['endpoint'],
    'provider_total' => (int) $kzfr_coverage['provider_total'],
    'published_records' => $kzfr_catalogue_published_count,
    'playable_records' => $kzfr_catalogue_expected,
    'seek_restricted_records' => $kzfr_catalogue_no_seek,
    'pages_checked' => $kzfr_catalogue_pages,
    'drafted_legacy_records' => $kzfr_catalogue_drafted_legacy,
    'drafted_unavailable_records' => $kzfr_catalogue_drafted_expired,
    'inventory_sha256' => hash_file('sha256', $kzfr_catalogue_file),
    'scope' => 'Currently available public recordings, not an unlimited historical archive.',
);
update_option('kzfr_archive_catalogue', $kzfr_catalogue_receipt, false);
WP_CLI::success('Current public archive catalogue applied: ' . wp_json_encode($kzfr_catalogue_receipt, JSON_UNESCAPED_SLASHES));
