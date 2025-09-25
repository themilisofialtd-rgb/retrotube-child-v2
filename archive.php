<?php
/**
 * Generic archive template override for Retrotube Child theme.
 */

$parent_template = '';
$parent_dir      = trailingslashit(get_template_directory());

foreach (['archive.php', 'index.php'] as $candidate) {
    $path = $parent_dir . $candidate;
    if (file_exists($path)) {
        $parent_template = $path;
        break;
    }
}

if ($parent_template) {
    ob_start();
    include $parent_template;
    $parent_output = ob_get_clean();

    if ($parent_output !== false) {
        $featured_markup = '';

        if (tmw_should_output_featured_block() && tmw_featured_block_dedup()) {
            $shortcode = tmw_get_featured_shortcode_for_context();
            set_query_var('tmw_featured_shortcode', $shortcode);
            ob_start();
            get_template_part('partials/featured-models-block');
            $featured_markup = ob_get_clean();
            set_query_var('tmw_featured_shortcode', null);
        }

        if (!empty($featured_markup)) {
            if (strpos($parent_output, '</main>') !== false) {
                $parent_output = preg_replace('#</main>#', $featured_markup . '</main>', $parent_output, 1);
            } else {
                $parent_output .= $featured_markup;
            }
        }

        echo $parent_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
}

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right generic-archive">
    <main id="main" class="site-main with-sidebar-right" role="main">
      <?php if (have_posts()) : ?>
        <header class="page-header">
          <h1 class="page-title"><?php the_archive_title(); ?></h1>
          <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>

        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/content', get_post_type()); ?>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>
      <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
      <?php endif; ?>
      <?php
      if (tmw_should_output_featured_block() && tmw_featured_block_dedup()) {
          $shortcode = tmw_get_featured_shortcode_for_context();
          set_query_var('tmw_featured_shortcode', $shortcode);
          get_template_part('partials/featured-models-block');
          set_query_var('tmw_featured_shortcode', null);
      }
      ?>
    </main>
  </div>
  <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
    <?php get_sidebar(); ?>
  </aside>
</div>
<?php
get_footer();
