<?php
/**
 * Plugin Name: KZFR Station Content
 * Description: Editable station content and protective settings for the independently prepared KZFR demonstration.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: John Clyde / VentiMath
 * License: GPL-2.0-or-later
 * Text Domain: kzfr-station
 */

if (!defined('ABSPATH')) {
    exit;
}

/** URL fields accept only HTTP(S), including when saved through the REST API. */
function kzfr_sanitize_url($value) {
    if (!is_scalar($value)) { return ''; }
    $value = esc_url_raw(trim((string) $value), array('http', 'https'));
    $parts = wp_parse_url($value);
    return is_array($parts) && !empty($parts['host']) && isset($parts['scheme']) && in_array(strtolower($parts['scheme']), array('http', 'https'), true) ? $value : '';
}

function kzfr_sanitize_date($value) {
    if (!is_scalar($value)) { return ''; }
    $value = sanitize_text_field((string) $value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        return '';
    }
    return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : '';
}

function kzfr_sanitize_time($value) {
    if (!is_scalar($value)) { return ''; }
    $value = sanitize_text_field((string) $value);
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : '';
}

function kzfr_meta_fields() {
    $source = array('source_url' => array('Source page URL', 'url'));
    return array(
        'kzfr_program' => array_merge(array(
            'host' => array('Host or hosts', 'text'),
            'schedule' => array('Schedule description', 'textarea'),
            'archive_url' => array('Official archive URL', 'url'),
            'playlist_url' => array('Official playlist URL', 'url'),
            'image_url' => array('Official image URL', 'url'),
        ), $source),
        'kzfr_event' => array_merge(array(
            'event_date' => array('Event date', 'date'),
            'event_time' => array('Time as published, including timezone if applicable', 'text'),
            'event_end_time' => array('End time, if published', 'text'),
            'doors_time' => array('Doors open time, if published', 'text'),
            'location' => array('Venue or location', 'text'),
            'ticket_url' => array('Official tickets or registration URL', 'url'),
        ), $source),
        'kzfr_underwriter' => array_merge(array('url' => array('Underwriter website URL', 'url')), $source),
        'kzfr_broadcast' => array_merge(array(
            'broadcast_date' => array('Broadcast date', 'date'),
            'program_slug' => array('Program URL slug', 'slug'),
            'audio_url' => array('Official audio or recording URL', 'url'),
        ), $source),
        'page' => array_merge(array(
            'hero_title' => array('Home hero title (Home page only)', 'textarea'),
            'hero_intro' => array('Home hero introduction (Home page only)', 'textarea'),
        ), $source),
    );
}

function kzfr_meta_sanitizer($type) {
    if ($type === 'url') { return 'kzfr_sanitize_url'; }
    if ($type === 'date') { return 'kzfr_sanitize_date'; }
    if ($type === 'slug') { return 'sanitize_title'; }
    if ($type === 'textarea') { return 'sanitize_textarea_field'; }
    return 'sanitize_text_field';
}

function kzfr_meta_authorized($allowed, $key, $post_id) {
    return current_user_can('edit_post', $post_id);
}

function kzfr_register_content() {
    $types = array(
        'kzfr_program' => array('Programs', 'Program', 'programs', 'dashicons-microphone'),
        'kzfr_event' => array('Events', 'Event', 'events', 'dashicons-calendar-alt'),
        'kzfr_underwriter' => array('Underwriters', 'Underwriter', 'underwriters', 'dashicons-store'),
        'kzfr_broadcast' => array('Archive records', 'Archive record', 'archive-records', 'dashicons-format-audio'),
    );
    foreach ($types as $type => $settings) {
        register_post_type($type, array(
            'labels' => array('name' => $settings[0], 'singular_name' => $settings[1], 'add_new_item' => 'Add ' . strtolower($settings[1]), 'edit_item' => 'Edit ' . strtolower($settings[1])),
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => $settings[2], 'with_front' => false),
            'menu_icon' => $settings[3],
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'),
        ));
    }
    register_taxonomy('kzfr_genre', array('kzfr_program', 'kzfr_broadcast'), array(
        'labels' => array('name' => 'Genres', 'singular_name' => 'Genre'),
        'public' => true, 'show_in_rest' => true, 'hierarchical' => true,
        'rewrite' => array('slug' => 'genres', 'with_front' => false),
    ));
    foreach (kzfr_meta_fields() as $post_type => $fields) {
        foreach ($fields as $name => $field) {
            register_post_meta($post_type, 'kzfr_' . $name, array(
                'type' => 'string', 'single' => true, 'default' => '', 'show_in_rest' => true,
                'sanitize_callback' => kzfr_meta_sanitizer($field[1]),
                'auth_callback' => 'kzfr_meta_authorized',
            ));
        }
    }
}
add_action('init', 'kzfr_register_content');

function kzfr_activate() {
    kzfr_register_content();
    update_option('blog_public', '0');
    update_option('default_ping_status', 'closed');
    update_option('default_comment_status', 'closed');
    update_option('ping_sites', '');
    update_option('users_can_register', '0');
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'kzfr_activate');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

add_action('add_meta_boxes', function () {
    foreach (kzfr_meta_fields() as $post_type => $fields) {
        add_meta_box('kzfr-station-details', 'Station details', 'kzfr_render_meta_box', $post_type, 'normal', 'default');
    }
});

function kzfr_render_meta_box($post) {
    wp_nonce_field('kzfr_save_station_details', 'kzfr_station_nonce');
    echo '<p>Keep public station information and official destinations current. This independent demonstration never collects donations or sends messages.</p>';
    foreach (kzfr_meta_fields()[$post->post_type] as $name => $field) {
        $key = 'kzfr_' . $name;
        $value = get_post_meta($post->ID, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($field[0]) . '</strong></label><br>';
        if ($field[1] === 'textarea') {
            echo '<textarea rows="3" class="widefat" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
        } else {
            $type = in_array($field[1], array('url', 'date'), true) ? $field[1] : 'text';
            echo '<input class="widefat" type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</p>';
    }
}

add_action('save_post', function ($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!isset($_POST['kzfr_station_nonce']) || !is_string($_POST['kzfr_station_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kzfr_station_nonce'])), 'kzfr_save_station_details')) {
        return;
    }
    $fields = kzfr_meta_fields();
    if (!isset($fields[$post->post_type])) { return; }
    foreach ($fields[$post->post_type] as $name => $field) {
        $key = 'kzfr_' . $name;
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            update_post_meta($post_id, $key, call_user_func(kzfr_meta_sanitizer($field[1]), wp_unslash($_POST[$key])));
        }
    }
}, 10, 2);

function kzfr_station_field_labels() {
    return array(
        'name' => 'Station name', 'tagline' => 'Tagline', 'frequency' => 'Frequency',
        'stream_url' => 'Live audio URL', 'archive_url' => 'Official archives URL', 'donate_url' => 'Official donation URL',
        'playlist_url' => 'Official playlists URL', 'website_url' => 'Official station website',
        'contact_url' => 'Official contact page', 'volunteer_url' => 'Official volunteering page', 'underwrite_url' => 'Official underwriting page',
        'address' => 'Station address', 'phone' => 'Station phone', 'email' => 'Public contact email',
    );
}

/** Sanitize imported scalar/nested data without discarding documented source fields. */
function kzfr_sanitize_content_value($value, $key = '') {
    if (is_array($value)) {
        $clean = array();
        foreach ($value as $child_key => $child_value) {
            $clean_key = is_int($child_key) ? $child_key : sanitize_key($child_key);
            $clean[$clean_key] = kzfr_sanitize_content_value($child_value, (string) $clean_key);
        }
        return $clean;
    }
    if (is_bool($value) || is_int($value) || is_float($value) || is_null($value)) { return $value; }
    if (str_ends_with($key, '_url') || $key === 'url') { return kzfr_sanitize_url($value); }
    if ($key === 'email') { return sanitize_email($value); }
    return sanitize_textarea_field((string) $value);
}

function kzfr_sanitize_station($value) {
    return is_array($value) ? kzfr_sanitize_content_value($value) : array();
}

function kzfr_sanitize_schedule($rows) {
    if (!is_array($rows)) { return array(); }
    $clean = array();
    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    foreach ($rows as $row) {
        if (!is_array($row) || !empty($row['remove'])) { continue; }
        $row = kzfr_sanitize_content_value($row);
        if (!isset($row['day']) || !in_array($row['day'], $days, true)) { continue; }
        $row['start'] = kzfr_sanitize_time($row['start'] ?? '');
        $row['end'] = kzfr_sanitize_time($row['end'] ?? '');
        if ($row['start'] === '' || $row['end'] === '') { continue; }
        // Both imported arrays and the visible multiline editor resolve to one
        // consistent list. The theme may safely prefer the arrays over scalars.
        if (array_key_exists('program_titles_text', $row)) {
            $row['program_titles'] = is_string($row['program_titles_text']) ? preg_split('/\r?\n/', trim($row['program_titles_text'])) : array();
        }
        if (array_key_exists('program_slugs_text', $row)) {
            $row['program_slugs'] = is_string($row['program_slugs_text']) ? preg_split('/\r?\n/', trim($row['program_slugs_text'])) : array();
        }
        $titles = isset($row['program_titles']) && is_array($row['program_titles']) ? $row['program_titles'] : array($row['program_title'] ?? '');
        $slugs = isset($row['program_slugs']) && is_array($row['program_slugs']) ? $row['program_slugs'] : array($row['program_slug'] ?? '');
        $row['program_titles'] = array();
        $row['program_slugs'] = array();
        foreach ($titles as $title_index => $title) {
            $title = sanitize_text_field($title);
            if ($title === '') { continue; }
            $row['program_titles'][] = $title;
            $row['program_slugs'][] = sanitize_title($slugs[$title_index] ?? '');
        }
        $row['program_slug'] = $row['program_slugs'][0] ?? '';
        $row['program_title'] = implode(' / ', $row['program_titles']);
        $row['start_day_offset'] = !empty($row['start_day_offset']) ? 1 : 0;
        $row['end_day_offset'] = max($row['start_day_offset'], !empty($row['end_day_offset']) ? 1 : 0, $row['start_day_offset'] + ($row['end'] < $row['start'] ? 1 : 0));
        unset($row['remove'], $row['program_titles_text'], $row['program_slugs_text']);
        $clean[] = $row;
    }
    return $clean;
}

add_action('admin_init', function () {
    register_setting('kzfr_station_settings', 'kzfr_station', array('type' => 'array', 'sanitize_callback' => 'kzfr_sanitize_station', 'default' => array()));
    register_setting('kzfr_station_settings', 'kzfr_schedule', array('type' => 'array', 'sanitize_callback' => 'kzfr_sanitize_schedule', 'default' => array()));
});

add_action('admin_menu', function () {
    add_menu_page('Station settings', 'Station settings', 'manage_options', 'kzfr-station', 'kzfr_render_settings', 'dashicons-radio', 28);
});

/** Hidden fields preserve provenance and rotation metadata when visible fields change. */
function kzfr_hidden_fields($name, $value) {
    if (is_array($value)) {
        foreach ($value as $key => $child) { kzfr_hidden_fields($name . '[' . $key . ']', $child); }
    } else {
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
    }
}

function kzfr_render_settings() {
    if (!current_user_can('manage_options')) { return; }
    $station = get_option('kzfr_station', array());
    $schedule = get_option('kzfr_schedule', array());
    echo '<div class="wrap"><h1>Station settings</h1><p>Maintain the station links and weekly schedule here. Changes affect this demonstration only. Official payment and contact services remain at their verified station destinations.</p>';
    settings_errors();
    echo '<form method="post" action="options.php">';
    settings_fields('kzfr_station_settings');
    echo '<h2>Station information</h2><table class="form-table" role="presentation">';
    foreach (kzfr_station_field_labels() as $key => $label) {
        $value = isset($station[$key]) && is_scalar($station[$key]) ? $station[$key] : '';
        $type = str_ends_with($key, '_url') ? 'url' : ($key === 'email' ? 'email' : 'text');
        echo '<tr><th scope="row"><label for="kzfr-station-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" type="' . esc_attr($type) . '" id="kzfr-station-' . esc_attr($key) . '" name="kzfr_station[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></td></tr>';
    }
    echo '</table>';
    foreach ($station as $key => $value) {
        if (!array_key_exists($key, kzfr_station_field_labels())) { kzfr_hidden_fields('kzfr_station[' . $key . ']', $value); }
    }
    echo '<h2>Weekly schedule</h2><p>Times use the station timezone, America/Los_Angeles. Add rows using the blank entries at the bottom. A blank day or invalid time omits a row. For rotating shows, put one program title on each line and its matching URL slug on the same line in the slug box. Preserve rotation notes until the station confirms the week assignment.</p><div style="overflow-x:auto"><table class="widefat striped"><thead><tr><th>Day / times</th><th>Programs / matching URL slugs</th><th>Rotation or note</th><th>Source / remove</th></tr></thead><tbody>';
    $schedule = array_merge($schedule, array_fill(0, 5, array()));
    foreach ($schedule as $index => $row) {
        $prefix = 'kzfr_schedule[' . $index . ']';
        echo '<tr><td><label class="screen-reader-text" for="kzfr-day-' . (int) $index . '">Day, row ' . ((int) $index + 1) . '</label><select id="kzfr-day-' . (int) $index . '" name="' . esc_attr($prefix) . '[day]"><option value="">Choose day</option>';
        foreach (array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') as $day) {
            echo '<option value="' . esc_attr($day) . '" ' . selected($row['day'] ?? '', $day, false) . '>' . esc_html($day) . '</option>';
        }
        echo '</select><p><label>Start <input type="time" name="' . esc_attr($prefix) . '[start]" value="' . esc_attr($row['start'] ?? '') . '"></label><br><label>End <input type="time" name="' . esc_attr($prefix) . '[end]" value="' . esc_attr($row['end'] ?? '') . '"></label></p><label><input type="checkbox" name="' . esc_attr($prefix) . '[start_day_offset]" value="1" ' . checked(!empty($row['start_day_offset']), true, false) . '> Starts next calendar day</label><br><label><input type="checkbox" name="' . esc_attr($prefix) . '[end_day_offset]" value="1" ' . checked(!empty($row['end_day_offset']), true, false) . '> Ends next calendar day</label></td>';
        $program_titles = isset($row['program_titles']) && is_array($row['program_titles']) ? $row['program_titles'] : array($row['program_title'] ?? '');
        $program_slugs = isset($row['program_slugs']) && is_array($row['program_slugs']) ? $row['program_slugs'] : array($row['program_slug'] ?? '');
        echo '<td><label>Program titles (one per line)<br><textarea rows="3" name="' . esc_attr($prefix) . '[program_titles_text]">' . esc_textarea(implode("\n", $program_titles)) . '</textarea></label><p><label>Matching URL slugs (one per line)<br><textarea rows="3" name="' . esc_attr($prefix) . '[program_slugs_text]">' . esc_textarea(implode("\n", $program_slugs)) . '</textarea></label></p></td>';
        echo '<td><label>Rotation<br><textarea name="' . esc_attr($prefix) . '[rotation]" rows="2">' . esc_textarea($row['rotation'] ?? '') . '</textarea></label><br><label>Note<br><textarea name="' . esc_attr($prefix) . '[note]" rows="2">' . esc_textarea($row['note'] ?? '') . '</textarea></label></td>';
        echo '<td><label>Source URL<br><input type="url" name="' . esc_attr($prefix) . '[source_url]" value="' . esc_attr($row['source_url'] ?? '') . '"></label><p><label><input type="checkbox" name="' . esc_attr($prefix) . '[remove]" value="1"> Remove row</label></p>';
        foreach ($row as $key => $value) {
            if (!in_array($key, array('day', 'start', 'end', 'program_title', 'program_slug', 'program_titles', 'program_slugs', 'rotation', 'note', 'source_url', 'start_day_offset', 'end_day_offset', 'remove'), true)) { kzfr_hidden_fields($prefix . '[' . $key . ']', $value); }
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
    submit_button('Save station settings');
    echo '</form></div>';
}

// Speculative demo protections. These are deliberately enforced, not just install defaults.
add_filter('pre_option_blog_public', function () { return '0'; });
add_filter('wp_robots', function ($robots) {
    unset($robots['index'], $robots['follow']);
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;
    return $robots;
});
add_filter('wp_headers', function ($headers) {
    $headers['X-Robots-Tag'] = 'noindex, nofollow, noarchive';
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
    unset($headers['X-Pingback']);
    return $headers;
});
add_filter('robots_txt', function () { return "User-agent: *\nDisallow: /\n"; }, 100);
add_filter('wp_sitemaps_enabled', '__return_false');
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');
add_filter('pings_open', '__return_false', 99);
add_filter('comments_open', '__return_false', 99);
add_filter('pre_option_ping_sites', '__return_empty_string');
add_filter('pre_wp_mail', function () { return false; }, 999);
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        foreach (array_keys($endpoints) as $route) {
            if (preg_match('#^/wp/v2/users(?:/|$)#', $route)) { unset($endpoints[$route]); }
        }
    }
    return $endpoints;
});
add_action('template_redirect', function () {
    if (is_author()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
});
add_action('admin_notices', function () {
    echo '<div class="notice notice-info"><p><strong>KZFR independent demonstration.</strong> Search indexing, outgoing email, comments and pingbacks are disabled. Content changes here do not update the official station website. CMS editing and media uploads remain available.</p></div>';
});

add_action('init', function () {
    if (!function_exists('register_block_pattern')) { return; }
    register_block_pattern_category('kzfr', array('label' => 'KZFR station'));
    register_block_pattern('kzfr/volunteer-callout', array(
        'title' => 'Volunteer callout', 'categories' => array('kzfr'),
        'content' => '<!-- wp:group {"className":"station-callout"} --><div class="wp-block-group station-callout"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Volunteer at KZFR</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Read about volunteering at the station, events, and programmer training.</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/volunteer/">Explore volunteering</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
    ));
});
