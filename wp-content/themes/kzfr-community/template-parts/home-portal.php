<?php
if (!defined('ABSPATH')) { exit; }
$portal_title = kzfr_meta('hero_title') ?: 'People Powered Radio';
$portal_intro = kzfr_meta('hero_intro') ?: 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.';
$portal_program_ids = array();
foreach (get_option('kzfr_schedule', array()) as $portal_slot) {
    foreach ($portal_slot['program_slugs'] ?? array($portal_slot['program_slug'] ?? '') as $portal_slug) {
        $portal_program = $portal_slug ? get_page_by_path($portal_slug, OBJECT, 'kzfr_program') : null;
        if ($portal_program && $portal_program->post_status === 'publish') { $portal_program_ids[$portal_program->ID] = $portal_program->ID; }
        if (count($portal_program_ids) >= 6) { break 2; }
    }
}
if (!$portal_program_ids) {
    $portal_fallback = kzfr_query('kzfr_program', 6);
    $portal_program_ids = wp_list_pluck($portal_fallback->posts, 'ID');
}
$portal_recordings = kzfr_query('kzfr_broadcast', 3, array('meta_key' => 'kzfr_broadcast_date', 'orderby' => array('meta_value' => 'DESC', 'ID' => 'ASC')));
$portal_events = kzfr_query('kzfr_event', 3, array('meta_key' => 'kzfr_event_date', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array(array('key' => 'kzfr_event_date', 'value' => wp_date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'))));
?>
<div class="portal-home">
    <section class="portal-welcome" aria-labelledby="portal-title">
        <div>
            <p class="portal-station-line">KZFR 90.1 FM <span>Chico, California</span></p>
            <h1 id="portal-title"><?php echo esc_html(preg_replace('/\s+/', ' ', $portal_title)); ?></h1>
            <p class="portal-intro"><?php echo esc_html($portal_intro); ?></p>
        </div>
        <div class="portal-listen">
            <button type="button" class="button button-dark" data-listen><span aria-hidden="true">▶</span> Listen live</button>
            <a href="<?php echo kzfr_url('schedule'); ?>">Weekly schedule</a>
        </div>
    </section>

    <div class="portal-columns">
        <aside class="portal-sidebar" aria-labelledby="portal-recordings-heading">
            <section class="portal-sidebar-section">
                <h2 id="portal-recordings-heading">Recent recordings</h2>
                <?php foreach ($portal_recordings->posts as $portal_recording): ?>
                    <article class="portal-recording">
                        <p class="portal-date"><?php echo esc_html(kzfr_meta('broadcast_date', $portal_recording->ID)); ?></p>
                        <h3><a href="<?php echo esc_url(get_permalink($portal_recording->ID)); ?>"><?php echo esc_html($portal_recording->post_title); ?></a></h3>
                        <?php kzfr_recording_button($portal_recording->ID); ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$portal_recordings->posts): ?><p>Visit the archives for available recordings.</p><?php endif; ?>
                <a class="portal-section-link" href="<?php echo kzfr_url('archives'); ?>">All recordings</a>
            </section>
            <section class="portal-sidebar-section portal-support" aria-labelledby="portal-support-heading">
                <h2 id="portal-support-heading">Support KZFR</h2>
                <ul>
                    <li><a href="<?php echo kzfr_url('donate'); ?>">Donate to the station</a></li>
                    <li><a href="<?php echo kzfr_url('volunteer'); ?>">Volunteer</a></li>
                    <li><a href="<?php echo kzfr_url('underwrite'); ?>">Underwriting information</a></li>
                    <li><a href="<?php echo kzfr_url('underwriters'); ?>">Station underwriters</a></li>
                </ul>
            </section>
        </aside>

        <div class="portal-main-column">
            <section class="portal-programs" aria-labelledby="portal-programs-heading">
                <div class="portal-section-heading">
                    <div><p>From the weekly schedule</p><h2 id="portal-programs-heading">Programs</h2></div>
                    <a href="<?php echo kzfr_url('programs'); ?>">All programs</a>
                </div>
                <div class="portal-program-list">
                    <?php foreach ($portal_program_ids as $portal_id): ?>
                        <article class="portal-program">
                            <div class="portal-program-heading">
                                <h3><a href="<?php echo esc_url(get_permalink($portal_id)); ?>"><?php echo esc_html(get_the_title($portal_id)); ?></a></h3>
                                <span><?php echo esc_html(kzfr_genre($portal_id)); ?></span>
                            </div>
                            <?php $portal_host = kzfr_meta('host', $portal_id); if ($portal_host): ?><p class="portal-host"><?php echo esc_html($portal_host); ?></p><?php endif; ?>
                            <?php $portal_excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($portal_id)), 25); if ($portal_excerpt): ?><p class="portal-program-description"><?php echo esc_html($portal_excerpt); ?></p><?php endif; ?>
                            <p class="portal-program-time"><?php echo esc_html(kzfr_meta('schedule', $portal_id)); ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$portal_program_ids): ?><p>See the program directory for station programming.</p><?php endif; ?>
                </div>
            </section>

            <section class="portal-events" aria-labelledby="portal-events-heading">
                <div class="portal-section-heading"><h2 id="portal-events-heading">Upcoming events</h2><a href="<?php echo kzfr_url('events'); ?>">All events</a></div>
                <div class="portal-event-list">
                    <?php foreach ($portal_events->posts as $portal_event) { kzfr_event_card($portal_event->ID); } ?>
                    <?php if (!$portal_events->posts): ?><p>Check the events page for current station listings.</p><?php endif; ?>
                </div>
            </section>

            <section class="portal-about" aria-labelledby="portal-about-heading">
                <h2 id="portal-about-heading">About KZFR</h2>
                <div class="prose"><?php the_content(); ?></div>
                <a href="<?php echo kzfr_url('about'); ?>">Station information</a>
            </section>
        </div>
    </div>
</div>
