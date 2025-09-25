<?php
/**
 * Category archive template override for Retrotube Child theme.
 *
 * Delegates rendering to the parent template and injects a "Featured Models"
 * block just before the closing </main> element.
 */

$parent_template = '';
$parent_dir      = trailingslashit(get_template_directory());

foreach (['category.php', 'archive.php', 'index.php'] as $candidate) {
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
} else {
    ob_start();
    get_header();
    ?>
    <div id="content" class="site-content row">
      <div id="primary" class="content-area with-sidebar-right category-archive">
        <main id="main" class="site-main with-sidebar-right" role="main">
          <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
              <?php get_template_part('template-parts/content', get_post_type()); ?>
            <?php endwhile; ?>

            <?php the_posts_navigation(); ?>
          <?php else : ?>
            <?php get_template_part('template-parts/content', 'none'); ?>
          <?php endif; ?>
        </main>
      </div>
      <aside id="sidebar" class="widget-area with-sidebar-right" role="complementary">
        <?php get_sidebar(); ?>
      </aside>
    </div>
    <?php
    get_footer();
    $parent_output = ob_get_clean();
}

$featured_markup  = '<section class="widget tmw-featured-slot featured-models-archive">';
$featured_markup .= '<h2 class="widget-title">Featured Models</h2>';
$featured_markup .= do_shortcode('[actors_flipboxes posts_per_page="4" orderby="rand"]');
$featured_markup .= '</section>';

$updated_output = preg_replace('#</main>#i', $featured_markup . '</main>', $parent_output, 1, $replaced);

if (!$replaced) {
    $updated_output = $parent_output . $featured_markup;
}

echo $updated_output;
