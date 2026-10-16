<?php
get_header();
the_post();
$home_layouts = [
    'original' => 'portal',
    'cream' => 'editorial',
    'white' => 'minimal',
    'blue' => 'listening',
    'evening' => 'evening',
];
get_template_part('template-parts/home', $home_layouts[kzfr_current_design()]);
get_footer();
