<?php
if (!defined('ABSPATH')) { exit; }
$minimal_title = kzfr_meta('hero_title') ?: 'People Powered Radio';
$minimal_intro = kzfr_meta('hero_intro') ?: 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.';
$minimal_day = wp_date('l');
$minimal_rows = array_values(array_filter(get_option('kzfr_schedule', array()), function ($row) use ($minimal_day) { return ($row['day'] ?? '') === $minimal_day; }));
$minimal_rows = array_slice($minimal_rows, 0, 6);
$minimal_recordings = kzfr_query('kzfr_broadcast', 4, array('meta_key' => 'kzfr_broadcast_date', 'orderby' => array('meta_value' => 'DESC', 'ID' => 'ASC')));
$minimal_events = kzfr_query('kzfr_event', 2, array('meta_key' => 'kzfr_event_date', 'orderby' => 'meta_value', 'order' => 'ASC', 'meta_query' => array(array('key' => 'kzfr_event_date', 'value' => wp_date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE'))));
?>
<div class="minimal-home">
    <section class="minimal-masthead" aria-labelledby="minimal-title">
        <div class="minimal-identity">
            <p class="minimal-station-line">KZFR 90.1 FM · Chico, California</p>
            <h1 id="minimal-title"><?php echo esc_html(preg_replace('/\s+/', ' ', $minimal_title)); ?></h1>
            <p class="minimal-intro"><?php echo esc_html($minimal_intro); ?></p>
        </div>
        <button type="button" class="button button-dark minimal-listen" data-listen><span aria-hidden="true">▶</span> Listen live</button>
    </section>

    <section class="minimal-programs" aria-labelledby="minimal-programs-heading">
        <div class="minimal-section-heading">
            <div><h2 id="minimal-programs-heading">From <?php echo esc_html($minimal_day); ?>’s schedule</h2><p>Pacific time. Rotating programs share a slot.</p></div>
            <a href="<?php echo esc_url(add_query_arg('day', $minimal_day, kzfr_design_link(home_url('/schedule/')))); ?>">View complete schedule</a>
        </div>
        <div class="minimal-schedule-list">
            <?php foreach ($minimal_rows as $minimal_row):
                $minimal_titles = $minimal_row['program_titles'] ?? array($minimal_row['program_title'] ?? '');
                $minimal_slugs = $minimal_row['program_slugs'] ?? array($minimal_row['program_slug'] ?? '');
                $minimal_first = !empty($minimal_slugs[0]) ? get_page_by_path($minimal_slugs[0], OBJECT, 'kzfr_program') : null;
                if ($minimal_first && $minimal_first->post_status !== 'publish') { $minimal_first = null; }
            ?>
                <article class="minimal-program-row">
                    <p class="minimal-airtime"><?php echo esc_html($minimal_row['start'] ?? ''); ?><?php if (!empty($minimal_row['start_day_offset'])): ?><small>next day</small><?php endif; ?><span>to <?php echo esc_html($minimal_row['end'] ?? ''); ?><?php if (!empty($minimal_row['end_day_offset'])): ?> (next day)<?php endif; ?></span></p>
                    <div class="minimal-program-copy">
                        <h3><?php foreach ($minimal_titles as $minimal_index => $minimal_name):
                            $minimal_program = !empty($minimal_slugs[$minimal_index]) ? get_page_by_path($minimal_slugs[$minimal_index], OBJECT, 'kzfr_program') : null;
                            if ($minimal_index > 0) { echo ' / '; }
                            if ($minimal_program && $minimal_program->post_status === 'publish'): ?><a href="<?php echo esc_url(get_permalink($minimal_program->ID)); ?>"><?php echo esc_html($minimal_name); ?></a><?php else: echo esc_html($minimal_name); endif;
                        endforeach; if (!$minimal_titles) { echo 'Program not named in the station schedule'; } ?></h3>
                        <?php if ($minimal_first): $minimal_excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($minimal_first->ID)), 22); if ($minimal_excerpt): ?><p><?php echo esc_html($minimal_excerpt); ?></p><?php endif; endif; ?>
                        <?php if (!empty($minimal_row['rotation'])): ?><p class="minimal-rotation">Check the station schedule for this week’s rotation.</p><?php endif; ?>
                    </div>
                    <span class="minimal-program-category"><?php if ($minimal_first) { echo esc_html(kzfr_genre($minimal_first->ID)); } ?></span>
                </article>
            <?php endforeach; ?>
            <?php if (!$minimal_rows): ?><p class="minimal-empty">See the weekly schedule for program times.</p><?php endif; ?>
        </div>
        <a class="minimal-directory-link" href="<?php echo kzfr_url('programs'); ?>">Browse the program directory</a>
    </section>

    <section class="minimal-recordings" aria-labelledby="minimal-recordings-heading">
        <div class="minimal-section-heading"><h2 id="minimal-recordings-heading">Recent recordings</h2><a href="<?php echo kzfr_url('archives'); ?>">All recordings</a></div>
        <div class="minimal-recording-list">
            <?php foreach ($minimal_recordings->posts as $minimal_recording): ?>
                <article class="minimal-recording-row">
                    <p class="minimal-recording-date"><?php echo esc_html(kzfr_meta('broadcast_date', $minimal_recording->ID)); ?></p>
                    <h3><a href="<?php echo esc_url(get_permalink($minimal_recording->ID)); ?>"><?php echo esc_html($minimal_recording->post_title); ?></a></h3>
                    <?php kzfr_recording_button($minimal_recording->ID); ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$minimal_recordings->posts): ?><p class="minimal-empty">Visit the archives for available recordings.</p><?php endif; ?>
        </div>
    </section>

    <section class="minimal-events" aria-labelledby="minimal-events-heading">
        <div class="minimal-section-heading"><h2 id="minimal-events-heading">Community events</h2><a href="<?php echo kzfr_url('events'); ?>">All events</a></div>
        <div class="minimal-event-list">
            <?php foreach ($minimal_events->posts as $minimal_event) { kzfr_event_card($minimal_event->ID); } ?>
            <?php if (!$minimal_events->posts): ?><p class="minimal-empty">Check the events page for current station listings.</p><?php endif; ?>
        </div>
    </section>

    <section class="minimal-about" aria-labelledby="minimal-about-heading">
        <h2 id="minimal-about-heading">About KZFR</h2>
        <div class="prose"><?php the_content(); ?></div>
        <div class="minimal-station-links"><a href="<?php echo kzfr_url('about'); ?>">Station information</a><a href="<?php echo kzfr_url('volunteer'); ?>">Volunteer</a><a href="<?php echo kzfr_url('donate'); ?>">Donate to KZFR</a></div>
    </section>
</div>
