<?php
get_header();

// Get ACF fields
$bio = get_field('bio');
$model_link = get_field('model_link');
$banner = get_field('banner_image');
$flipbox_shortcode = get_field('flipbox_shortcode');
?>

<div class="container model-bio-page">

    <!-- ✅ Breadcrumbs -->
    <div id="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList" style="margin:15px 0;">
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="<?php echo home_url(); ?>">
                <span itemprop="name">Home</span>
            </a>
            <meta itemprop="position" content="1" />
        </span>
        <span class="separator"><i class="fa fa-caret-right"></i></span>

        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="<?php echo site_url('/models/'); ?>">
                <span itemprop="name">Models</span>
            </a>
            <meta itemprop="position" content="2" />
        </span>
        <span class="separator"><i class="fa fa-caret-right"></i></span>

        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name"><?php the_title(); ?></span>
            <meta itemprop="position" content="3" />
        </span>
    </div>
    <!-- ✅ End Breadcrumbs -->

    <div class="model-header" style="text-align: center; margin-bottom: 30px;">
        <h1><?php the_title(); ?></h1>

        <?php if ($banner): ?>
            <img src="<?php echo esc_url($banner); ?>" alt="<?php the_title(); ?>" style="max-width:100%; height:auto; margin-top: 15px;">
        <?php endif; ?>
    </div>

    <div class="model-content">
        <?php if ($bio): ?>
            <div class="model-bio" style="margin-bottom: 30px;">
                <?php echo wp_kses_post($bio); ?>
            </div>
        <?php endif; ?>

        <?php if ($flipbox_shortcode): ?>
            <div class="model-flipbox" style="margin: 40px 0;">
                <?php echo do_shortcode($flipbox_shortcode); ?>
            </div>
        <?php endif; ?>

        <?php if ($model_link): ?>
            <div class="model-link" style="text-align: center; margin-top: 40px;">
                <a href="<?php echo esc_url($model_link); ?>" class="btn" target="_blank" rel="nofollow">
                    Visit <?php the_title(); ?> on LiveJasmin
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
