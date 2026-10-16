<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?> data-design="<?php echo esc_attr(kzfr_current_design()); ?>"><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main">Skip to content</a>
<div class="demo-bar"><span>WEBSITE PREVIEW</span><span>Not the official KZFR website. <a href="<?php echo kzfr_url('demo-notes'); ?>">About this preview</a></span></div>
<div class="design-review" aria-label="Preview design options">
<div class="design-review-label"><strong>Compare five designs</strong><span>Same pages. Different look.</span></div>
<nav class="design-choices" aria-label="Choose a preview design"><?php $design_index=0; foreach(kzfr_design_options() as $design_key=>$design_option): $design_index++; ?>
<a href="<?php echo kzfr_design_url($design_key); ?>" class="design-choice" data-design-choice="<?php echo esc_attr($design_key); ?>" <?php if(kzfr_current_design()===$design_key) echo 'aria-current="true"'; ?> title="<?php echo esc_attr($design_option[1]); ?>"><span class="design-swatch swatch-<?php echo esc_attr($design_key); ?>" aria-hidden="true"></span><span><?php echo esc_html($design_index.'. '.$design_option[0]); ?></span></a>
<?php endforeach; ?></nav>
<p class="design-description" aria-live="polite"><?php echo esc_html(kzfr_design_options()[kzfr_current_design()][1]); ?></p>
</div>
<header class="site-header"><a class="wordmark" href="<?php echo kzfr_url(); ?>" aria-label="KZFR demo home"><span>KZFR<span class="wordmark-signal" aria-hidden="true">◔</span></span><small>90.1 FM <span>CHICO, CALIFORNIA</span></small></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="primary-navigation">Menu <span aria-hidden="true">☰</span></button>
<nav id="primary-navigation" aria-label="Main navigation"><?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'menu_class'=>'nav-list','fallback_cb'=>false,'depth'=>1]); ?></nav>
<div class="header-actions"><button class="listen-button button button-dark" data-listen><span class="play-icon" aria-hidden="true">▶</span> Listen live</button><a class="button button-red" href="<?php echo kzfr_url('donate'); ?>">Donate </a></div></header>
<main id="main" tabindex="-1">
