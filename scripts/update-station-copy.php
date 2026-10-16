<?php
/** Targeted approved Home-copy update: wp eval-file /private/path/update-station-copy.php */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) { exit(1); }
if (!function_exists('kzfr_meta_fields') || wp_parse_url(home_url(), PHP_URL_HOST) !== 'kzfr.clydellmfactory.com' || (string) get_option('blog_public') !== '0') {
    WP_CLI::error('This copy update is restricted to the noindex KZFR demonstration.');
}
$kzfr_home = get_page_by_path('home', OBJECT, 'page');
if (!$kzfr_home || get_post_meta($kzfr_home->ID, '_kzfr_import_key', true) !== 'page:home') {
    WP_CLI::error('The imported demonstration Home page was not found.');
}
$kzfr_home_body = "<!-- wp:paragraph -->\n<p>KZFR 90.1 FM is a nonprofit, noncommercial community radio station in Chico, California. It began broadcasting on July 6, 1990.</p>\n<!-- /wp:paragraph -->";
$kzfr_home_result = wp_update_post(wp_slash(array('ID' => $kzfr_home->ID, 'post_content' => $kzfr_home_body)), true);
if (is_wp_error($kzfr_home_result)) { WP_CLI::error($kzfr_home_result->get_error_message()); }
update_post_meta($kzfr_home->ID, 'kzfr_hero_title', "People Powered\nRadio");
update_post_meta($kzfr_home->ID, 'kzfr_hero_intro', 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.');
update_post_meta($kzfr_home->ID, 'kzfr_source_url', 'https://kzfr.org/pages/about');
clean_post_cache($kzfr_home->ID);
if (get_post($kzfr_home->ID)->post_content !== $kzfr_home_body || get_post_meta($kzfr_home->ID, 'kzfr_hero_title', true) !== "People Powered\nRadio") {
    WP_CLI::error('Home copy verification failed.');
}
WP_CLI::success('Home copy now uses KZFR’s verified tagline and factual station information.');

// Neutral page labels and listener-facing copy approved for the temporary preview.
$kzfr_copy_file = getenv('KZFR_CONTENT_FILE') ?: dirname(__DIR__) . '/content/station-content.json';
$kzfr_copy_data = json_decode(file_get_contents($kzfr_copy_file), true);
if (!is_array($kzfr_copy_data)) { WP_CLI::error('Cannot read the reviewed content snapshot.'); }
foreach ($kzfr_copy_data['pages'] as $record) {
    $page = get_page_by_path(sanitize_title($record['slug']), OBJECT, 'page');
    if (!$page || get_post_meta($page->ID, '_kzfr_import_key', true) !== 'page:'.$record['slug']) { continue; }
    $content = $page->post_content;
    $content = str_replace(
        array('Donate directly to KZFR through its official Creek donation site','This demonstration contains a limited sample of public broadcast metadata from October 2026. It is not a complete migration of KZFR’s archive. Recording availability is controlled by the station and may change.','Open the full official Creek archive','. Creek requires JavaScript.'),
        array('Donate directly to KZFR','This preview includes selected broadcasts from October 2026. Recordings may become unavailable over time.','Browse all recordings on KZFR’s archive','.'),
        $content
    );
    $updated = wp_update_post(wp_slash(array('ID'=>$page->ID,'post_title'=>$record['title'],'post_content'=>$content)),true);
    if (is_wp_error($updated)) { WP_CLI::error($updated->get_error_message()); }
}
$notes=get_page_by_path('demo-notes',OBJECT,'page');
if($notes) {
    $content = '<!-- wp:paragraph --><p>This temporary website preview is hosted by Clyde LLM Factory. It is not commissioned, approved, or operated by KZFR. The official station website is <a href="https://www.kzfr.org/">kzfr.org</a>.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Station information comes from KZFR’s public website. Donations, tickets, and volunteer applications go to the station’s official services. This preview does not accept payments or contact submissions.</p><!-- /wp:paragraph -->';
    wp_update_post(wp_slash(array('ID'=>$notes->ID,'post_content'=>$content)));
}
foreach(get_posts(array('post_type'=>'kzfr_broadcast','posts_per_page'=>-1)) as $broadcast) {
    $content=str_replace('Public archive metadata sampled from KZFR. ','',$broadcast->post_content);
    wp_update_post(wp_slash(array('ID'=>$broadcast->ID,'post_content'=>$content)));
}
WP_CLI::success('Neutral page labels, archive wording, and hosting attribution applied.');
