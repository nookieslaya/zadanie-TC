<?php

namespace WP_Sejm_API;

class Theme_Compat
{
    protected static bool $fallback_open = false;
    protected static bool $blade_namespace_added = false;

    public static function header(): void
    {
        if (self::has_header_php()) {
            get_header();
            return;
        }

        self::render_fallback_header();
    }

    public static function footer(): void
    {
        if (self::has_footer_php()) {
            get_footer();
            return;
        }

        self::render_fallback_footer();
    }

    public static function render_with_blade(string $view, string $content): bool
    {
        if (!self::is_blade_theme()) {
            return false;
        }

        if (!self::register_blade_namespace()) {
            return false;
        }

        try {
            echo view('wp-sejm-api::' . $view, [
                'content' => $content,
            ])->render();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function has_header_php(): bool
    {
        return (bool) locate_template(['header.php']);
    }

    protected static function has_footer_php(): bool
    {
        return (bool) locate_template(['footer.php']);
    }

    protected static function render_fallback_header(): void
    {
        if (self::$fallback_open) {
            return;
        }

        $lang_attributes = function_exists('get_language_attributes') ? get_language_attributes() : '';
        $charset = esc_attr(get_bloginfo('charset'));
        $body_classes = function_exists('get_body_class') ? trim(implode(' ', get_body_class())) : '';
        $wrapper_id = self::has_blade_layout() ? 'app' : 'mp-app';

        echo '<!doctype html>';
        echo '<html ' . $lang_attributes . '>';
        echo '<head>';
        echo '<meta charset="' . $charset . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        do_action('get_header', null);
        wp_head();
        echo '</head>';
        echo '<body class="' . esc_attr($body_classes) . '">';
        wp_body_open();
        echo '<div id="' . esc_attr($wrapper_id) . '">';

        self::$fallback_open = true;

        self::render_blade('sections.header');
    }

    protected static function render_fallback_footer(): void
    {
        if (self::$fallback_open) {
            self::render_blade('sections.footer');
            echo '</div>';
        }

        do_action('get_footer', null);
        wp_footer();
        echo '</body>';
        echo '</html>';
    }

    protected static function is_blade_theme(): bool
    {
        return function_exists('view') && self::has_blade_layout();
    }

    protected static function has_blade_layout(): bool
    {
        $paths = [
            trailingslashit(get_stylesheet_directory()) . 'resources/views/layouts/app.blade.php',
            trailingslashit(get_template_directory()) . 'resources/views/layouts/app.blade.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }

    protected static function register_blade_namespace(): bool
    {
        if (self::$blade_namespace_added) {
            return true;
        }

        $views_path = WP_SEJM_API_PATH . 'views';

        try {
            if (function_exists('app')) {
                $factory = app('view');
                if (is_object($factory) && method_exists($factory, 'addNamespace')) {
                    $factory->addNamespace('wp-sejm-api', $views_path);
                    self::$blade_namespace_added = true;
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $factory = view();
            if (is_object($factory) && method_exists($factory, 'addNamespace')) {
                $factory->addNamespace('wp-sejm-api', $views_path);
                self::$blade_namespace_added = true;
                return true;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    protected static function render_blade(string $view): bool
    {
        if (!function_exists('view')) {
            return false;
        }

        try {
            $instance = view($view);
            echo $instance->render();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
