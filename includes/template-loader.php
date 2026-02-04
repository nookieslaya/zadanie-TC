<?php

namespace MP_Importer;

class Template_Loader
{
    public static function init(): void
    {
        add_filter('template_include', [__CLASS__, 'template_include']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function template_include(string $template): string
    {
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            return $template;
        }

        if (is_post_type_archive('mp')) {
            if (Base_Page::has_base_page()) {
                return $template;
            }

            $theme_template = locate_template(['archive-mp.php']);
            if (!empty($theme_template)) {
                return $theme_template;
            }

            $archive = MP_IMPORTER_PATH . 'templates/archive-mp.php';
            if (file_exists($archive)) {
                return $archive;
            }
        }

        if (is_singular('mp')) {
            $theme_template = locate_template(['single-mp.php']);
            if (!empty($theme_template)) {
                return $theme_template;
            }

            $single = MP_IMPORTER_PATH . 'templates/single-mp.php';
            if (file_exists($single)) {
                return $single;
            }
        }

        return $template;
    }

    public static function enqueue_assets(): void
    {
        if (!is_post_type_archive('mp') && !is_singular('mp')) {
            return;
        }

        wp_enqueue_style(
            'mp-importer',
            MP_IMPORTER_URL . 'assets/style.css',
            [],
            MP_IMPORTER_VERSION
        );
    }
}
