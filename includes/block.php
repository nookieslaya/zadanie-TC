<?php

namespace MP_Importer;

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
            'mp-importer',
            MP_IMPORTER_URL . 'assets/style.css',
            [],
            MP_IMPORTER_VERSION
        );

        wp_register_script(
            'mp-importer-mp-grid-editor',
            MP_IMPORTER_URL . 'blocks/mp-grid/index.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'],
            MP_IMPORTER_VERSION,
            true
        );

        register_block_type(MP_IMPORTER_PATH . 'blocks/mp-grid/block.json', [
            // Dynamic block keeps pagination and filtering on the server and avoids duplicate markup logic.
            'render_callback' => [__CLASS__, 'render'],
        ]);
    }

    public static function render(array $attributes = [], string $content = ''): string
    {
        return Grid_Renderer::render_block($attributes);
    }
}
