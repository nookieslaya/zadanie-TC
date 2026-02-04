<?php

namespace WP_Sejm_API;

class Block
{
    public static function init(): void
    {
        add_action('init', [__CLASS__, 'register']);
    }

    public static function register(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_style(
            'wp-sejm-api',
            WP_SEJM_API_URL . 'assets/style.css',
            [],
            WP_SEJM_API_VERSION
        );

        wp_register_script(
            'wp-sejm-api-mp-grid-editor',
            WP_SEJM_API_URL . 'blocks/mp-grid/index.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'],
            WP_SEJM_API_VERSION,
            true
        );

        wp_register_script(
            'wp-sejm-api-mp-single-editor',
            WP_SEJM_API_URL . 'blocks/mp-single/index.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n'],
            WP_SEJM_API_VERSION,
            true
        );

        register_block_type(WP_SEJM_API_PATH . 'blocks/mp-grid/block.json', [
            // Dynamic block keeps pagination and filtering on the server and avoids duplicate markup logic.
            'render_callback' => [__CLASS__, 'render'],
        ]);

        register_block_type(WP_SEJM_API_PATH . 'blocks/mp-single/block.json', [
            'render_callback' => [__CLASS__, 'render_single'],
        ]);

        // Legacy block name for backward compatibility.
        register_block_type('mp-importer/mp-grid', [
            'api_version' => 2,
            'title' => 'MPs Grid (legacy)',
            'category' => 'widgets',
            'icon' => 'id',
            'attributes' => [
                'postsPerPage' => [
                    'type' => 'number',
                    'default' => 12,
                ],
                'enablePagination' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'enableFilters' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'limit' => [
                    'type' => 'number',
                ],
            ],
            'supports' => [
                'html' => false,
                'inserter' => false,
            ],
            'style' => 'wp-sejm-api',
            'editor_style' => 'wp-sejm-api',
            'editor_script' => 'wp-sejm-api-mp-grid-editor',
            'render_callback' => [__CLASS__, 'render'],
        ]);
    }

    public static function render(array $attributes = [], string $content = ''): string
    {
        wp_enqueue_style('wp-sejm-api');
        return Grid_Renderer::render_block($attributes);
    }

    public static function render_single(array $attributes = [], string $content = ''): string
    {
        $post_id = get_the_ID();
        if (!$post_id || get_post_type($post_id) !== 'mp') {
            return '';
        }

        wp_enqueue_style('wp-sejm-api');

        $html = Single_Renderer::render();
        if ($html === '') {
            return '';
        }

        return '<div class="mp-single"><div class="mp-container">' . $html . '</div></div>';
    }
}
