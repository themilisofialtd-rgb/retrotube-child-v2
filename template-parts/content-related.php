<?php
/**
 * Child override to remove the "Show more related videos" button on model pages.
 *
 * @package RetroTube_Child
 */

if ( ! function_exists( 'tmw_child_include_parent_related_template' ) ) {
    /**
     * Attempt to load the first available parent related template and optionally filter the markup.
     *
     * @param callable|null $filter Optional filter callback applied to the captured markup.
     * @return bool Whether a parent template was rendered.
     */
    function tmw_child_include_parent_related_template( $filter = null ) {
        $parent_dir = trailingslashit( get_template_directory() );
        $templates  = array(
            'template-parts/content-related.php',
            'template-parts/related.php',
        );

        foreach ( $templates as $template ) {
            $parent_path = $parent_dir . ltrim( $template, '/' );

            if ( ! file_exists( $parent_path ) ) {
                continue;
            }

            if ( null === $filter ) {
                include $parent_path;
                return true;
            }

            ob_start();
            include $parent_path;
            $markup = ob_get_clean();

            if ( false === $markup ) {
                continue;
            }

            $filtered = call_user_func( $filter, $markup );

            if ( false === $filtered ) {
                return true;
            }

            echo $filtered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return true;
        }

        return false;
    }
}

if ( ! is_singular( 'model' ) ) {
    if ( function_exists( 'tmw_try_parent_template' ) ) {
        if ( tmw_try_parent_template( array( 'template-parts/content-related.php', 'template-parts/related.php' ) ) ) {
            return;
        }
    }

    if ( tmw_child_include_parent_related_template() ) {
        return;
    }

    return;
}

$filter_markup = static function ( $markup ) {
    return preg_replace(
        '#<div\s+class="show-more">\s*<a[^>]*id="load-more-related"[^>]*>.*?</a>\s*</div>#is',
        '',
        $markup
    );
};

tmw_child_include_parent_related_template( $filter_markup );
