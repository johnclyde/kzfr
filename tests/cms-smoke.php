<?php
/**
 * wp eval-file /path/to/cms-smoke.php
 * Set KZFR_RUN_IMPORT_TESTS=1 for a temporary staff edit + repeat-import check.
 * All temporary changes are restored in finally blocks.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) { exit(1); }
global $kzfr_checks, $kzfr_failures, $kzfr_expected_minimum;
require dirname(__DIR__) . '/scripts/assert-content.php';

$kzfr_editor = get_role('editor');
foreach (array('kzfr_program', 'kzfr_event', 'kzfr_underwriter', 'kzfr_broadcast') as $type) {
    $object = get_post_type_object($type);
    kzfr_assert_content($kzfr_editor && $kzfr_editor->has_cap($object->cap->edit_posts), 'Editor can edit ' . $type);
    kzfr_assert_content($kzfr_editor && $kzfr_editor->has_cap($object->cap->publish_posts), 'Editor can publish ' . $type);
    kzfr_assert_content(post_type_supports($type, 'editor') && post_type_supports($type, 'revisions'), 'Native editor and revisions enabled: ' . $type);
}
kzfr_assert_content($kzfr_editor && !$kzfr_editor->has_cap('manage_options'), 'Editors cannot change administrator station settings');
foreach (array('home', 'about', 'contact', 'volunteer', 'donate', 'underwrite', 'demo-notes') as $slug) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    $blocks = $page ? parse_blocks($page->post_content) : array();
    $named = array_values(array_filter($blocks, function ($block) { return !empty($block['blockName']); }));
    kzfr_assert_content(count($named) > 0, 'Native editor blocks parse: ' . $slug);
    kzfr_assert_content($page && serialize_blocks($blocks) === $page->post_content, 'Native editor block roundtrip: ' . $slug);
}
$kzfr_schedule_original = get_option('kzfr_schedule', array());
try {
    $test_rows = array(array(
        'day' => 'Monday', 'start' => '23:00', 'end' => '02:00',
        'program_title' => 'Old visible name', 'program_slug' => 'old-slug',
        'program_titles' => array('Old hidden name'), 'program_slugs' => array('old-hidden'),
        'program_titles_text' => "Edited first program\nEdited rotation program",
        'program_slugs_text' => "edited-first\nedited-rotation",
        'rotation' => 'Alternating weeks, pending station confirmation.',
        'source_url' => 'https://www.kzfr.org/schedule',
    ));
    $clean_rows = kzfr_sanitize_schedule($test_rows);
    kzfr_assert_content($clean_rows[0]['program_titles'] === array('Edited first program', 'Edited rotation program'), 'Visible program title edits replace stale arrays');
    kzfr_assert_content($clean_rows[0]['program_slugs'] === array('edited-first', 'edited-rotation'), 'Visible program slug edits replace stale arrays');
    kzfr_assert_content($clean_rows[0]['program_title'] === 'Edited first program / Edited rotation program' && $clean_rows[0]['program_slug'] === 'edited-first', 'Schedule scalar fields stay synchronized');
    kzfr_assert_content($clean_rows[0]['end_day_offset'] === 1, 'Overnight schedule remains next day');
    update_option('kzfr_schedule', $clean_rows, false);
    kzfr_assert_content(get_option('kzfr_schedule') === $clean_rows, 'Edited schedule persists through option storage');
} finally {
    update_option('kzfr_schedule', $kzfr_schedule_original, false);
}
kzfr_assert_content(get_option('kzfr_schedule') === $kzfr_schedule_original, 'Original schedule is restored');

if (getenv('KZFR_RUN_IMPORT_TESTS') === '1') {
    $programs = get_posts(array('post_type' => 'kzfr_program', 'posts_per_page' => 1, 'orderby' => 'ID', 'order' => 'ASC'));
    $program = $programs ? $programs[0] : null;
    kzfr_assert_content((bool) $program, 'Program available for repeat import preservation test');
    if ($program) {
        $original_content = $program->post_content;
        $original_host = get_post_meta($program->ID, 'kzfr_host', true);
        $original_update_env = getenv('KZFR_IMPORT_UPDATE');
        $marker = '<!-- wp:paragraph --><p>Temporary CMS preservation test.</p><!-- /wp:paragraph -->';
        $before_counts = array();
        foreach (array('kzfr_program', 'kzfr_event', 'kzfr_underwriter', 'kzfr_broadcast', 'page') as $type) { $before_counts[$type] = (int) wp_count_posts($type)->publish; }
        try {
            wp_update_post(wp_slash(array('ID' => $program->ID, 'post_content' => $original_content . "\n\n" . $marker)));
            update_post_meta($program->ID, 'kzfr_host', 'Temporary preservation test host');
            putenv('KZFR_IMPORT_UPDATE=0');
            $result = WP_CLI::runcommand('eval-file ' . escapeshellarg(dirname(__DIR__) . '/scripts/import-content.php'), array('return' => 'all', 'launch' => true, 'exit_error' => false));
            clean_post_cache($program->ID);
            kzfr_assert_content((int) $result->return_code === 0, 'Repeat importer succeeds');
            kzfr_assert_content(str_contains(get_post($program->ID)->post_content, $marker), 'Repeat importer preserves staff body edits');
            kzfr_assert_content(get_post_meta($program->ID, 'kzfr_host', true) === 'Temporary preservation test host', 'Repeat importer preserves staff field edits');
            foreach ($before_counts as $type => $count) {
                wp_cache_delete(_count_posts_cache_key($type, ''), 'counts');
                kzfr_assert_content((int) wp_count_posts($type)->publish === $count, 'Repeat importer creates no duplicate ' . $type);
            }
        } finally {
            wp_update_post(wp_slash(array('ID' => $program->ID, 'post_content' => $original_content)));
            update_post_meta($program->ID, 'kzfr_host', $original_host);
            if ($original_update_env === false) { putenv('KZFR_IMPORT_UPDATE'); } else { putenv('KZFR_IMPORT_UPDATE=' . $original_update_env); }
        }
        kzfr_assert_content(get_post($program->ID)->post_content === $original_content && get_post_meta($program->ID, 'kzfr_host', true) === $original_host, 'Staff-edit test restores original program');
    }
}
if ($kzfr_checks < $kzfr_expected_minimum) { WP_CLI::error('Smoke assertion accounting mismatch; refusing a success receipt.'); }
if ($kzfr_failures) {
    foreach ($kzfr_failures as $failure) { WP_CLI::warning($failure); }
    WP_CLI::error(count($kzfr_failures) . ' of ' . $kzfr_checks . ' CMS smoke assertions failed.');
}
WP_CLI::success($kzfr_checks . ' CMS smoke assertions passed; original content and schedule restored.');
