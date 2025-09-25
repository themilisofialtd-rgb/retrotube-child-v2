<?php
/**
 * Single template for the Models CPT.
 *
 * Reuse the existing Model Bio template so both slugs share the same layout
 * and breadcrumb logic.
 */

get_header();
?>
<div id="primary" class="content-area">
  <main id="main" class="site-main">
    <div class="container model-bio-page">
      <?php get_template_part('breadcrumb'); ?>
      <?php require __DIR__ . '/single-model_bio.php'; ?>
    </div>
  </main>
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
