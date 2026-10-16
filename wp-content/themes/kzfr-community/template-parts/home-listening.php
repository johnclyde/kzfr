<?php
if (!defined('ABSPATH')) exit;
$listening_intro = kzfr_meta('hero_intro') ?: 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.';
$listening_programs = kzfr_query('kzfr_program', 6, ['meta_query' => [
    ['key' => 'kzfr_schedule', 'value' => 'Not listed in the current weekly schedule.', 'compare' => '!='],
]]);
// Source timestamps distinguish recordings within the same broadcast date.
$listening_recording_query = kzfr_query('kzfr_broadcast', -1);
$listening_recordings = [];
foreach ($listening_recording_query->posts as $listening_recording) {
    $listening_source = get_post_meta($listening_recording->ID, '_kzfr_source_record', true);
    $listening_start = is_array($listening_source) && !empty($listening_source['start_utc']) ? $listening_source['start_utc'] : kzfr_meta('broadcast_date', $listening_recording->ID);
    $listening_recordings[] = ['post' => $listening_recording, 'start' => $listening_start];
}
usort($listening_recordings, static function ($a, $b) { return strcmp($b['start'], $a['start']); });
$listening_recordings = array_slice($listening_recordings, 0, 3);
$listening_events = kzfr_query('kzfr_event', 2, [
    'meta_key' => 'kzfr_event_date', 'orderby' => 'meta_value', 'order' => 'ASC',
    'meta_query' => [['key' => 'kzfr_event_date', 'value' => wp_date('Y-m-d'), 'compare' => '>=', 'type' => 'DATE']],
]);
?>
<div class="listening-home section-wrap">
    <header class="listening-intro">
        <div class="listening-frequency" aria-hidden="true"><span>90.1<small>FM</small></span></div>
        <p class="listening-kicker">KZFR · Chico, California</p>
        <h1>People Powered Radio</h1>
        <p class="listening-description"><?php echo esc_html($listening_intro); ?></p>
        <button class="button button-dark listening-live" type="button" data-listen><span aria-hidden="true">▶</span> Listen live</button>
        <a class="listening-schedule-link" href="<?php echo kzfr_url('schedule'); ?>">View the weekly schedule</a>
    </header>

    <section class="listening-recent" aria-labelledby="listening-recent-heading">
        <div class="listening-section-heading"><h2 id="listening-recent-heading">Latest recordings</h2><a href="<?php echo kzfr_url('archives'); ?>">All recordings</a></div>
        <div class="listening-recording-grid">
            <?php foreach ($listening_recordings as $listening_item): $listening_recording_id = $listening_item['post']->ID; ?>
                <article class="listening-recording">
                    <p class="listening-kicker">Recorded broadcast</p>
                    <h3><a href="<?php echo esc_url(get_permalink($listening_recording_id)); ?>"><?php echo esc_html(get_the_title($listening_recording_id)); ?></a></h3>
                    <div class="listening-recording-actions"><?php kzfr_recording_button($listening_recording_id); ?><a href="<?php echo esc_url(get_permalink($listening_recording_id)); ?>">Details</a></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$listening_recordings): ?><p class="listening-empty">Check the archive for available recordings.</p><?php endif; ?>
        </div>
    </section>

    <section class="listening-programs" aria-labelledby="listening-programs-heading">
        <div class="listening-section-heading"><h2 id="listening-programs-heading">Programs</h2><a href="<?php echo kzfr_url('programs'); ?>">Full program directory</a></div>
        <div class="listening-program-list">
            <?php foreach ($listening_programs->posts as $listening_program): $listening_program_id = $listening_program->ID; ?>
                <article class="listening-program-row">
                    <p class="listening-program-genre"><?php echo esc_html(kzfr_genre($listening_program_id)); ?></p>
                    <div class="listening-program-title"><h3><a href="<?php echo esc_url(get_permalink($listening_program_id)); ?>"><?php echo esc_html(get_the_title($listening_program_id)); ?></a></h3><?php if (kzfr_meta('host', $listening_program_id)): ?><p><?php echo esc_html(kzfr_meta('host', $listening_program_id)); ?></p><?php endif; ?></div>
                    <p class="listening-program-schedule"><?php echo esc_html(kzfr_meta('schedule', $listening_program_id)); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="listening-station" aria-labelledby="listening-station-heading">
        <div class="listening-about"><p class="listening-kicker">Community radio</p><h2 id="listening-station-heading">About KZFR</h2><div class="prose"><?php the_content(); ?></div><div class="listening-station-links"><a href="<?php echo kzfr_url('about'); ?>">About the station</a><a href="<?php echo kzfr_url('donate'); ?>">Support KZFR</a><a href="<?php echo kzfr_url('volunteer'); ?>">Volunteer</a></div></div>
        <div class="listening-events"><h2>Upcoming events</h2><?php foreach ($listening_events->posts as $listening_event) kzfr_event_card($listening_event->ID); ?><?php if (!$listening_events->posts): ?><p>Check the events page for station listings.</p><?php endif; ?><a class="listening-events-link" href="<?php echo kzfr_url('events'); ?>">All events</a></div>
    </section>
</div>
