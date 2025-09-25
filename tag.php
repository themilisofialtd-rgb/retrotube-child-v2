<?php
/**
 * Tag archive template override for Retrotube Child theme.
 *
 * Delegates rendering to the parent template.
 */

$parent_template = '';
$parent_dir      = trailingslashit(get_template_directory());

foreach (['tag.php', 'archive.php', 'index.php'] as $candidate) {
    $path = $parent_dir . $candidate;
    if (file_exists($path)) {
        $parent_template = $path;
        break;
    }
}

if ($parent_template) {
    include $parent_template;
    return;
}

get_header();
?>
<div id="content" class="site-content row">
  <div id="primary" class="content-area with-sidebar-right tag-archive">
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
