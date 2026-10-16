<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('responsive-embeds');
    add_theme_support('editor-styles'); add_editor_style('assets/site.css');
    register_nav_menus(['primary' => 'Primary navigation', 'footer' => 'Footer navigation']);
});
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('twenty-twenty-one-style'); wp_dequeue_style('twenty-twenty-one-print-style');
    wp_dequeue_script('twenty-twenty-one-primary-navigation-script'); wp_dequeue_script('twenty-twenty-one-responsive-embeds-script');
    wp_enqueue_style('kzfr-community', get_stylesheet_directory_uri().'/assets/site.css', [], filemtime(get_stylesheet_directory().'/assets/site.css'));
    wp_enqueue_style('kzfr-design-options', get_stylesheet_directory_uri().'/assets/designs.css', ['kzfr-community'], filemtime(get_stylesheet_directory().'/assets/designs.css'));
    foreach (['portal','editorial','minimal','listening','evening'] as $home_layout) {
        $home_asset='/assets/home-'.$home_layout.'.css';
        if (file_exists(get_stylesheet_directory().$home_asset)) wp_enqueue_style('kzfr-home-'.$home_layout, get_stylesheet_directory_uri().$home_asset, ['kzfr-design-options'], filemtime(get_stylesheet_directory().$home_asset));
    }
    wp_enqueue_script('kzfr-community', get_stylesheet_directory_uri().'/assets/site.js', [], filemtime(get_stylesheet_directory().'/assets/site.js'), true);
}, 30);
add_filter('body_class', fn($classes) => array_merge($classes, ['kzfr-site']));
function kzfr_station($key, $fallback = '') { $station=get_option('kzfr_station',[]); return isset($station[$key]) && is_scalar($station[$key]) && $station[$key] !== '' ? $station[$key] : $fallback; }
function kzfr_meta($key, $id = 0) { return get_post_meta($id ?: get_the_ID(), 'kzfr_'.$key, true); }
function kzfr_url($path = '') { return esc_url(kzfr_design_link(home_url('/'.trim($path, '/').($path ? '/' : '')))); }
function kzfr_link($url, $text, $class = 'text-link') { if ($url) echo '<a class="'.esc_attr($class).'" href="'.esc_url($url).'">'.esc_html($text).'</a>'; }
function kzfr_query($type, $limit = -1, $extra = []) { return new WP_Query(array_merge(['post_type'=>$type, 'post_status'=>'publish', 'posts_per_page'=>$limit, 'orderby'=>'title', 'order'=>'ASC'], $extra)); }
function kzfr_terms($id = 0) { $terms=get_the_terms($id ?: get_the_ID(), 'kzfr_genre'); return is_array($terms) ? $terms : []; }
function kzfr_genre($id = 0) { $terms=kzfr_terms($id); return $terms ? $terms[0]->name : 'Community radio'; }
function kzfr_program_art($id, $i = 0) { $variant=abs(crc32(get_the_title($id))) % 5; echo '<div class="program-art art-'.$variant.'" aria-hidden="true"><div class="art-record"><span>90.1<br>FM</span></div><span class="art-label">'.esc_html(kzfr_genre($id)).'</span><span class="art-corner">KZFR</span></div>'; }
function kzfr_program_card($id, $i = 0) { ?>
<article class="program-card"><a class="card-art-link" href="<?php echo esc_url(get_permalink($id)); ?>" tabindex="-1" aria-hidden="true"><?php kzfr_program_art($id,$i); ?></a><div class="card-copy"><p class="eyebrow"><?php echo esc_html(kzfr_genre($id)); ?></p><h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3><p class="muted"><?php echo esc_html(kzfr_meta('host',$id)); ?></p><p class="card-schedule"><?php echo esc_html(kzfr_meta('schedule',$id)); ?></p></div></article>
<?php }
function kzfr_event_card($id) { $date=kzfr_meta('event_date',$id); $ts=$date ? strtotime($date.' 12:00:00') : false; ?>
<article class="event-row"><div class="event-date"><?php if ($ts): ?><span><?php echo esc_html(wp_date('M',$ts)); ?></span><strong><?php echo esc_html(wp_date('d',$ts)); ?></strong><?php else: ?><span>To be</span><strong>set</strong><?php endif; ?></div><div><p class="eyebrow">Station event</p><h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3><p class="muted"><?php echo esc_html(kzfr_meta('location',$id)); ?> · <?php echo esc_html(kzfr_meta('event_time',$id)); if(kzfr_meta('event_end_time',$id)) echo ' to '.esc_html(kzfr_meta('event_end_time',$id)); ?></p></div></article>
<?php }
function kzfr_page_intro($eyebrow, $title, $description = '') { ?><div class="page-intro"><p class="eyebrow"><?php echo esc_html($eyebrow); ?></p><h1><?php echo esc_html($title); ?></h1><?php if ($description): ?><p class="lead"><?php echo esc_html($description); ?></p><?php endif; ?></div><?php }
function kzfr_filter_value($key) { return isset($_GET[$key]) && is_scalar($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : ''; }
function kzfr_source_note($id = 0) { $url=kzfr_meta('source_url',$id); if ($url): ?><aside class="source-note"><a href="<?php echo esc_url($url); ?>">View this page on KZFR’s website</a>.</aside><?php endif; }
add_action('init', function(){
    if (function_exists('register_block_pattern')) {
        register_block_pattern('kzfr/community-callout', ['title'=>'KZFR community callout','categories'=>['featured'],'content'=>'<!-- wp:group {"className":"community-callout"} --><div class="wp-block-group community-callout"><!-- wp:heading --><h2 class="wp-block-heading">Support KZFR</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Learn about donating to KZFR through the official station donation service.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/donate/">Support KZFR</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->']);
    }
});

function kzfr_recording_button($id = 0) {
    $id = $id ?: get_the_ID();
    $url = kzfr_meta('audio_url', $id);
    if (!$url) return;
    $source = get_post_meta($id, '_kzfr_source_record', true);
    $disabled_seek = is_array($source) && !empty($source['disable_seek']);
    echo '<button class="button button-dark recording-button" data-recording="'.esc_url($url).'" data-recording-title="'.esc_attr(get_the_title($id)).'" data-recording-source="'.esc_url(kzfr_meta('source_url',$id)).'" data-disable-seek="'.($disabled_seek?'true':'false').'" aria-label="'.esc_attr('Play '.get_the_title($id)).'"><span aria-hidden="true">▶</span> Play</button>';
}

// Preview-only presentation choices. The same content and audio work in every design.
function kzfr_design_options() {
    return [
        'original' => ['Original greens', 'Their green highlights, warm neutrals, and white panels.'],
        'cream' => ['Soft cream', 'Warm paper, olive accents, and relaxed serif headings.'],
        'white' => ['Open white', 'A light, spacious layout with simple typography.'],
        'blue' => ['River blue', 'Pale blue, rounded shapes, and softer contrast.'],
        'evening' => ['Warm evening', 'A quiet dark palette with warm, readable text.'],
    ];
}
function kzfr_current_design() {
    $requested = kzfr_filter_value('design');
    return array_key_exists($requested, kzfr_design_options()) ? $requested : 'original';
}
function kzfr_design_url($design) {
    $request = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
    $request = '/'.ltrim($request, '/');
    return esc_url(add_query_arg('design', $design, $request));
}

// Preserve an explicit preview choice through regular links as well as enhanced navigation.
function kzfr_design_link($url) {
    if (is_admin() || !isset($_GET['design']) || !is_scalar($_GET['design'])) return $url;
    $design = kzfr_filter_value('design');
    if (!array_key_exists($design, kzfr_design_options())) return $url;
    $host = wp_parse_url($url, PHP_URL_HOST);
    if ($host && strtolower($host) !== strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST))) return $url;
    return add_query_arg('design', $design, $url);
}
add_filter('post_type_link', 'kzfr_design_link');
add_filter('page_link', 'kzfr_design_link');
add_filter('nav_menu_link_attributes', function ($attributes) {
    if (isset($attributes['href'])) $attributes['href'] = kzfr_design_link($attributes['href']);
    return $attributes;
});
