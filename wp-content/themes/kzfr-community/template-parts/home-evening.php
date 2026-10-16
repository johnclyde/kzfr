<?php if (!defined('ABSPATH')) exit; ?>
<section class="evening-masthead section-wrap">
    <p class="eyebrow">KZFR · CHICO, CALIFORNIA</p>
    <h1><?php echo esc_html(preg_replace('/\s+/u', ' ', kzfr_meta('hero_title') ?: 'People Powered Radio')); ?></h1>
    <div class="evening-frequency" aria-hidden="true">90.1<span>FM</span></div>
    <div class="evening-intro"><p><?php echo esc_html(kzfr_meta('hero_intro') ?: 'Community radio from Chico, California. Listen live at 90.1 FM or explore programs and recordings.'); ?></p><div class="button-row"><button class="button button-red button-large" data-listen><span aria-hidden="true">▶</span> Listen live</button><a class="text-link" href="<?php echo kzfr_url('schedule'); ?>">Weekly schedule</a></div></div>
</section>
<section class="evening-programs section-wrap section-space">
    <div class="section-heading"><h2>Programs</h2><a class="text-link" href="<?php echo kzfr_url('programs'); ?>">All programs</a></div>
    <div class="evening-program-list"><?php $programs=kzfr_query('kzfr_program',6,['meta_query'=>[['key'=>'kzfr_schedule','value'=>'Not listed in the current weekly schedule.','compare'=>'!=']]]); while($programs->have_posts()): $programs->the_post(); ?>
        <article class="evening-program"><p class="eyebrow"><?php echo esc_html(kzfr_genre()); ?></p><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><?php if(kzfr_meta('host')): ?><p><?php echo esc_html(kzfr_meta('host')); ?></p><?php endif; ?><p class="evening-program-time"><?php echo esc_html(kzfr_meta('schedule')); ?></p></article>
    <?php endwhile; wp_reset_postdata(); ?></div>
</section>
<section class="evening-recordings section-wrap section-space">
    <div class="evening-section-intro"><p class="eyebrow">LISTEN AGAIN</p><h2>Latest recordings</h2><a class="text-link" href="<?php echo kzfr_url('archives'); ?>">Browse the archives</a></div>
    <div class="evening-recording-list"><?php $recordings=kzfr_query('kzfr_broadcast',4,['meta_key'=>'kzfr_broadcast_date','orderby'=>'meta_value','order'=>'DESC']); while($recordings->have_posts()): $recordings->the_post(); ?>
        <article><h3><?php the_title(); ?></h3><?php kzfr_recording_button(); ?></article>
    <?php endwhile; wp_reset_postdata(); ?></div>
</section>
<section class="evening-events section-wrap section-space">
    <div class="section-heading"><h2>Events</h2><a class="text-link" href="<?php echo kzfr_url('events'); ?>">All events</a></div>
    <?php $events=kzfr_query('kzfr_event',2,['meta_key'=>'kzfr_event_date','orderby'=>'meta_value','order'=>'ASC']); while($events->have_posts()): $events->the_post(); kzfr_event_card(get_the_ID()); endwhile; wp_reset_postdata(); ?>
</section>
<section class="evening-support section-wrap"><h2>Support KZFR</h2><p>Donations, volunteering, and underwriting support KZFR’s community radio programming.</p><a class="button button-red" href="<?php echo kzfr_url('donate'); ?>">Donate</a></section>
