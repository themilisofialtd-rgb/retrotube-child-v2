<?php
/**
 * Template Name: Single Model
 * Description: Identical to RetroTube single-video.php but displays model banner instead of video player
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) : the_post();

	$model_id   = get_the_ID();
	$model_name = get_the_title();

	// ACF fields
	$banner_image      = get_field( 'banner_image', $model_id );
	$model_link        = get_field( 'model_link', $model_id );
	$flipbox_shortcode = get_field( 'flipbox_shortcode', $model_id );
	$bio               = get_field( 'model', $model_id );

	error_log('[ModelLayout] single-model.php loaded for ' . $model_name);
?>

<div id="primary" class="content-area with-sidebar-right">
	<main id="main" class="site-main with-sidebar-right" role="main">

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header class="entry-header">

				<!-- Banner replaces video player -->
				<div class="video-player box-shadow">
					<?php
					if ( $banner_image ) {
						$banner_url = is_array( $banner_image ) ? $banner_image['url'] : $banner_image;
						echo '<img src="' . esc_url( $banner_url ) . '" alt="' . esc_attr( $model_name ) . '" class="aligncenter" />';
					} else {
						echo '<div class="no-banner-placeholder"><p>' . esc_html__( 'No banner image uploaded yet.', 'retrotube' ) . '</p></div>';
					}
					?>
				</div>

				<div class="title-block box-shadow">
					<h1 class="entry-title" itemprop="name"><?php echo esc_html( $model_name ); ?></h1>
				</div>

				<!-- Meta Strip -->
				<div class="video-meta-inline">
					<span class="video-meta-item video-meta-model">
						<i class="fa fa-star"></i> <?php esc_html_e( 'Model:', 'retrotube' ); ?> <?php echo esc_html( $model_name ); ?>
					</span>
					<?php if ( $model_link ) : ?>
					<span class="video-meta-item video-meta-link">
						<a href="<?php echo esc_url( $model_link ); ?>" target="_blank" class="btn btn-primary">
							<i class="fa fa-video-camera"></i> <?php esc_html_e( 'Watch Live', 'retrotube' ); ?>
						</a>
					</span>
					<?php endif; ?>
					<span class="video-meta-item video-meta-date">
						<i class="fa fa-calendar"></i> <?php echo get_the_date(); ?>
					</span>
				</div>

			</header><!-- .entry-header -->

			<!-- RetroTube Accordion/Tabs -->
			<div class="video-content">
				<div id="video-tabs" class="tabs">
					<ul class="tab-buttons">
						<li class="active"><a href="#tab-description"><?php esc_html_e( 'About', 'retrotube' ); ?></a></li>
						<li><a href="#tab-videos"><?php esc_html_e( 'Videos', 'retrotube' ); ?></a></li>
						<li><a href="#tab-comments"><?php esc_html_e( 'Comments', 'retrotube' ); ?></a></li>
					</ul>

					<div class="tab-content">

						<!-- About Tab -->
						<div id="tab-description" class="tab active">
							<div class="video-description entry-content">
								<?php
									if ( $bio ) {
										echo wpautop( $bio );
									} else {
										the_content();
									}

									if ( $flipbox_shortcode ) {
										echo '<div class="model-flipbox">';
										echo do_shortcode( $flipbox_shortcode );
										echo '</div>';
									}
								?>

								<?php if ( has_tag() ) : ?>
									<div class="post-tags">
										<?php the_tags( '<strong>' . esc_html__( 'Tags:', 'retrotube' ) . '</strong> ', ', ' ); ?>
									</div>
								<?php endif; ?>
							</div>
						</div>

						<!-- Videos Tab -->
						<div id="tab-videos" class="tab">
							<div class="under-video-block">
								<h2 class="widget-title"><?php esc_html_e( 'Model’s Videos', 'retrotube' ); ?></h2>

								<?php
								$related = new WP_Query( [
									'post_type'      => 'video',
									'posts_per_page' => 6,
									'meta_query'     => [
										[
											'key'     => 'related_model',
											'value'   => $model_name,
											'compare' => 'LIKE',
										],
									],
								] );

								if ( $related->have_posts() ) :
									echo '<div class="related-videos-grid">';
									while ( $related->have_posts() ) :
										$related->the_post();
										get_template_part( 'template-parts/loop', 'video' );
									endwhile;
									echo '</div>';
									wp_reset_postdata();
								else :
									echo '<p>' . esc_html__( 'No related videos found for this model.', 'retrotube' ) . '</p>';
								endif;
								?>
							</div>
						</div>

						<!-- Comments Tab -->
						<div id="tab-comments" class="tab">
							<?php comments_template(); ?>
						</div>

					</div><!-- .tab-content -->
				</div><!-- #video-tabs -->
			</div><!-- .video-content -->

		</article>

	</main><!-- #main -->
</div><!-- #primary -->

<!-- Sidebar -->
<?php get_sidebar(); ?>

<?php
	endwhile;
endif;

get_footer();
