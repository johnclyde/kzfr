<?php
if (!defined('ABSPATH')) exit;
$editorial_program_query = kzfr_query('kzfr_program', 7, ['meta_query' => [
    ['key' => 'kzfr_schedule', 'value' => 'Not listed in the current weekly schedule.', 'compare' => '!='],
]]);
$editorial_programs = $editorial_program_query->posts;
$editorial_feature = array_shift($editorial_programs);
$editorial_events = kzfr_query('kzfr_event', 2, [
    'meta_key' => 'kzfr_event_date', 'orderby' => 'meta_value', 'order' => 'ASC',
    'meta_query' => [['key' => 'kzfr_event_date', 'value' => wp_date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE']],
]);
$editorial_intro = kzfr_meta('hero_intro') ?: 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.';
?>
<div class="editorial-home section-wrap">
    <header class="editorial-masthead">
        <p class="editorial-edition">KZFR <span aria-hidden="true">·</span> 90.1 FM <span aria-hidden="true">·</span> Chico, California</p>
        <h1>People Powered Radio</h1>
        <p class="editorial-intro"><?php echo esc_html($editorial_intro); ?></p>
        <div class="editorial-listen-line">
            <button class="button button-dark" type="button" data-listen><span aria-hidden="true">▶</span> Listen live</button>
            <a href="<?php echo kzfr_url('schedule'); ?>">Weekly schedule</a>
            <a href="<?php echo kzfr_url('archives'); ?>">Recordings</a>
        </div>
    </header>

    <div class="editorial-front-columns">
        <section class="editorial-feature" aria-labelledby="editorial-feature-heading">
            <?php if ($editorial_feature): $editorial_feature_id = $editorial_feature->ID; ?>
                <p class="editorial-kicker">Featured program</p>
                <h2 id="editorial-feature-heading"><a href="<?php echo esc_url(get_permalink($editorial_feature_id)); ?>"><?php echo esc_html(get_the_title($editorial_feature_id)); ?></a></h2>
                <p class="editorial-feature-genre"><?php echo esc_html(kzfr_genre($editorial_feature_id)); ?></p>
                <p class="editorial-feature-summary"><?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt($editorial_feature_id)), 65)); ?></p>
                <dl class="editorial-feature-facts">
                    <?php if (kzfr_meta('host', $editorial_feature_id)): ?><div><dt>Hosted by</dt><dd><?php echo esc_html(kzfr_meta('host', $editorial_feature_id)); ?></dd></div><?php endif; ?>
                    <?php if (kzfr_meta('schedule', $editorial_feature_id)): ?><div><dt>Schedule</dt><dd><?php echo esc_html(kzfr_meta('schedule', $editorial_feature_id)); ?></dd></div><?php endif; ?>
                </dl>
                <a class="editorial-reading-link" href="<?php echo esc_url(get_permalink($editorial_feature_id)); ?>">About this program</a>
            <?php else: ?>
                <p class="editorial-kicker">Program directory</p>
                <h2 id="editorial-feature-heading">Programs</h2>
                <p>Find program information and recordings in the directory.</p>
                <a class="editorial-reading-link" href="<?php echo kzfr_url('programs'); ?>">Browse programs</a>
            <?php endif; ?>
        </section>

        <aside class="editorial-events" aria-labelledby="editorial-events-heading">
            <p class="editorial-kicker">At the station</p>
            <h2 id="editorial-events-heading">Upcoming events</h2>
            <?php if ($editorial_events->posts): ?>
                <div class="editorial-event-list"><?php foreach ($editorial_events->posts as $editorial_event) kzfr_event_card($editorial_event->ID); ?></div>
            <?php else: ?><p>Check the events page for station listings.</p><?php endif; ?>
            <a class="editorial-reading-link" href="<?php echo kzfr_url('events'); ?>">All events</a>
        </aside>
    </div>

    <section class="editorial-directory" aria-labelledby="editorial-programs-heading">
        <div class="editorial-section-heading"><h2 id="editorial-programs-heading">Programs</h2><a class="editorial-reading-link" href="<?php echo kzfr_url('programs'); ?>">Full program directory</a></div>
        <div class="editorial-program-columns">
            <?php foreach ($editorial_programs as $editorial_program): $editorial_id = $editorial_program->ID; ?>
                <article class="editorial-program">
                    <p class="editorial-kicker"><?php echo esc_html(kzfr_genre($editorial_id)); ?></p>
                    <h3><a href="<?php echo esc_url(get_permalink($editorial_id)); ?>"><?php echo esc_html(get_the_title($editorial_id)); ?></a></h3>
                    <?php if (kzfr_meta('host', $editorial_id)): ?><p class="editorial-program-host"><?php echo esc_html(kzfr_meta('host', $editorial_id)); ?></p><?php endif; ?>
                    <p class="editorial-program-time"><?php echo esc_html(kzfr_meta('schedule', $editorial_id)); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="editorial-station" aria-labelledby="editorial-station-heading">
        <div class="editorial-about"><p class="editorial-kicker">Community radio</p><h2 id="editorial-station-heading">About KZFR</h2><div class="prose"><?php the_content(); ?></div><a class="editorial-reading-link" href="<?php echo kzfr_url('about'); ?>">More about the station</a></div>
        <div class="editorial-support"><h2>Support KZFR</h2><p>Find information about donating, volunteering and underwriting.</p><div class="editorial-support-links"><a href="<?php echo kzfr_url('donate'); ?>">Donate</a><a href="<?php echo kzfr_url('volunteer'); ?>">Volunteer</a><a href="<?php echo kzfr_url('underwrite'); ?>">Underwrite</a></div></div>
    </section>
</div>
