<?php

namespace WP_Sejm_API;

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

            $archive = WP_SEJM_API_PATH . 'templates/archive-mp.php';
            if (file_exists($archive)) {
                return $archive;
            }
        }

        if (is_singular('mp')) {
            $theme_template = locate_template(['single-mp.php']);
            if (!empty($theme_template)) {
                return $theme_template;
            }

            $single = WP_SEJM_API_PATH . 'templates/single-mp.php';
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

        if (wp_style_is('wp-sejm-api', 'registered')) {
            wp_enqueue_style('wp-sejm-api');
            return;
        }

        $style_path = WP_SEJM_API_PATH . 'assets/style.css';
        $style_ver = file_exists($style_path) ? (string) filemtime($style_path) : WP_SEJM_API_VERSION;

        wp_enqueue_style(
            'wp-sejm-api',
            WP_SEJM_API_URL . 'assets/style.css',
            [],
            $style_ver
        );
    }
}
