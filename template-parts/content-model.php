<?php
$model_id   = get_the_ID();
$model_name = get_the_title();

$banner_url      = tmw_resolve_model_banner_url( $model_id );

$cta_url   = function_exists( 'get_field' ) ? get_field( 'model_link', $model_id ) : '';
$cta_label = function_exists( 'get_field' ) ? get_field( 'model_link_label', $model_id ) : '';
$cta_note  = function_exists( 'get_field' ) ? get_field( 'model_link_note', $model_id ) : '';

if ( empty( $cta_label ) ) {
        $cta_label = __( 'Watch Live', 'retrotube' );
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemprop="performer" itemscope itemtype="http://schema.org/Person">
        <header class="entry-header">

                <div class="video-player box-shadow model-banner">
                        <?php
                        if ( defined( 'TMW_BANNER_DEBUG' ) && TMW_BANNER_DEBUG ) {
                                echo "\n<!-- TMW Banner URL: " . esc_html( $banner_url ? $banner_url : 'EMPTY' ) . " -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                        <?php if ( ! tmw_render_model_banner( $model_id, 'frontend' ) ) : ?>
                                <div class="tmw-banner-container">
                                        <div class="tmw-banner-frame frontend">
                                                <div class="no-banner-placeholder">
                                                        <p><?php esc_html_e( 'No banner image uploaded yet.', 'retrotube' ); ?></p>
                                                </div>
                                        </div>
                                </div>
                        <?php endif; ?>

                        <?php if ( $cta_url ) : ?>
                                <a class="button model-cta" id="model-cta" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="nofollow noopener">
                                        <i class="fa fa-video-camera"></i>
                                        <?php echo esc_html( $cta_label ); ?>
                                </a>
                        <?php endif; ?>

                        <?php if ( $cta_note ) : ?>
                                <p class="model-cta-note"><?php echo wp_kses_post( $cta_note ); ?></p>
                        <?php endif; ?>
                </div>

                <div class="tmw-slot-banner" style="margin-top:15px;margin-bottom:25px;text-align:center;">
                        <?php echo do_shortcode( '[tmw_slot_machine]' ); ?>
                </div>

                <div class="title-block box-shadow">
                        <div class="model-header-inline">
                                <?php the_title( '<h1 class="entry-title" itemprop="name">', '</h1>' ); ?>
                                <?php if ( xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
                                        <div id="rating" class="post-like-inline">
                                                <span id="video-rate"><?php echo wpst_get_post_like_link( get_the_ID() ); ?></span>
                                                <?php $is_rated_yet = ( wpst_get_post_like_rate( get_the_ID() ) === false ) ? ' not-rated-yet' : ''; ?>
                                        </div>
                                <?php endif; ?>
                        </div>
                        <div id="video-tabs" class="tabs">
                                <button class="tab-link active about" data-tab-id="video-about">
                                        <i class="fa fa-info-circle"></i> <?php esc_html_e( 'About', 'wpst' ); ?>
                                </button>
                                <?php if ( xbox_get_field_value( 'wpst-options', 'enable-video-share' ) == 'on' ) : ?>
                                        <button class="tab-link share" data-tab-id="video-share">
                                                <i class="fa fa-share"></i> <?php esc_html_e( 'Share', 'wpst' ); ?>
                                        </button>
                                <?php endif; ?>
                        </div>
                </div>

                               <div class="clear"></div>


        </header><!-- .entry-header -->

        <div class="entry-content">
                <?php if ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'on' || xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
                        <div id="rating-col">
                                <?php if ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'on' ) : ?>
                                        <div id="video-views"><span>0</span> <?php esc_html_e( 'views', 'wpst' ); ?></div>
                                <?php endif; ?>
                                <?php if ( xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
                                        <div class="rating-bar"><div class="rating-bar-meter"></div></div>
                                        <div class="rating-result">
                                                <div class="percentage">0%</div>
                                                <div class="likes">
                                                        <i class="fa fa-thumbs-up"></i> <span class="likes_count">0</span>
                                                        <i class="fa fa-thumbs-down fa-flip-horizontal"></i> <span class="dislikes_count">0</span>
                                                </div>
                                        </div>
                                <?php endif; ?>
                        </div>
                <?php endif; ?>

                <div class="tab-content">
                        <?php $width = ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'off' && xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'off' ) ? '100' : '70'; ?>
                        <div id="video-about" class="width<?php echo $width; ?>">
                                <div class="video-description">
                                        <?php if ( xbox_get_field_value( 'wpst-options', 'show-description-video-about' ) == 'on' ) : ?>
                                                <div class="desc <?php echo ( xbox_get_field_value( 'wpst-options', 'truncate-description' ) == 'on' ) ? 'more' : ''; ?>">
                                                        <?php the_content(); ?>
                                                </div>
                                        <?php endif; ?>
                                </div>

                                <?php if ( xbox_get_field_value( 'wpst-options', 'show-categories-video-about' ) == 'on' || xbox_get_field_value( 'wpst-options', 'show-tags-video-about' ) == 'on' ) : ?>
                                        <div class="tags"><?php wpst_entry_footer(); ?></div>
                                <?php endif; ?>
                        </div>

                        <?php if ( xbox_get_field_value( 'wpst-options', 'enable-video-share' ) == 'on' ) : ?>
                                <?php get_template_part( 'template-parts/content', 'share-buttons' ); ?>
                        <?php endif; ?>
                </div>
        </div><!-- .entry-content -->

        <?php if ( xbox_get_field_value( 'wpst-options', 'display-related-videos' ) == 'on' ) : ?>
                <?php get_template_part( 'template-parts/content', 'related' ); ?>
        <?php endif; ?>

        <?php
        if ( xbox_get_field_value( 'wpst-options', 'enable-comments' ) == 'on' ) {
                if ( comments_open() || get_comments_number() ) :
                        comments_template();
                endif;
        }
        ?>
</article><!-- #post-## -->
