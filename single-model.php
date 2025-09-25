<?php
/**
 * Single template for the Models CPT.
 *
 * Reuse the existing Model Bio template so both slugs share the same layout
 * and breadcrumb logic.
 */

get_header();
?>
<div id="primary" class="content-area container">
  <div class="row">
    <main id="main" class="site-main col-md-8 model-bio-page">
      <?php get_template_part('breadcrumb'); ?>
      <?php require __DIR__ . '/single-model_bio.php'; ?>
    </main>
    <aside class="col-md-4">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</div>
<?php get_footer(); ?>
