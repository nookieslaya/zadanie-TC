<?php

namespace WP_Sejm_API;

class Block_Templates
{
    public static function init(): void
    {
        add_action('init', [__CLASS__, 'register'], 20);
    }

    public static function register(): void
    {
        if (!function_exists('register_block_template')) {
            return;
        }

        if (!function_exists('wp_is_block_theme') || !wp_is_block_theme()) {
            return;
        }

        $single_content = self::single_template_content();
        $archive_content = self::archive_template_content();

        if ($single_content !== '') {
            register_block_template('wp-sejm-api//single-mp', [
                'title' => __('MP Profile', 'wp-sejm-api'),
                'description' => __('Template for the single MP view.', 'wp-sejm-api'),
                'content' => $single_content,
                'post_types' => ['mp'],
            ]);
        }

        if ($archive_content !== '') {
            register_block_template('wp-sejm-api//archive-mp', [
                'title' => __('MPs Archive', 'wp-sejm-api'),
                'description' => __('Template for the MP archive view.', 'wp-sejm-api'),
                'content' => $archive_content,
                'post_types' => ['mp'],
            ]);
        }
    }

    protected static function single_template_content(): string
    {
        return implode("\n", [
            '<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->',
            '<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->',
            '<!-- wp:wp-sejm-api/mp-single /-->',
            '<!-- /wp:group -->',
            '<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->',
        ]);
    }

    protected static function archive_template_content(): string
    {
        return implode("\n", [
            '<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->',
            '<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->',
            '<!-- wp:wp-sejm-api/mp-grid {"postsPerPage":12,"enablePagination":true,"enableFilters":true} /-->',
            '<!-- /wp:group -->',
            '<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->',
        ]);
    }
}
