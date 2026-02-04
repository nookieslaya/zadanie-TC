<?php

namespace WP_Sejm_API;

class Base_Page
{
    protected const OPTION_ID = 'wp_sejm_api_base_page_id';
    protected const OPTION_SLUG = 'wp_sejm_api_base_slug';
    protected const OPTION_FLUSH = 'wp_sejm_api_flush_rewrite';
    protected const OPTION_REWRITE_VERSION = 'wp_sejm_api_rewrite_version';
    protected const LEGACY_OPTION_ID = 'mp_importer_base_page_id';
    protected const LEGACY_OPTION_SLUG = 'mp_importer_base_slug';
    protected const LEGACY_OPTION_FLUSH = 'mp_importer_flush_rewrite';
    protected const LEGACY_OPTION_REWRITE_VERSION = 'mp_importer_rewrite_version';
    protected const REWRITE_VERSION = 2;

    public static function init(): void
    {
        add_action('save_post_page', [__CLASS__, 'maybe_update_base_page'], 10, 3);
        add_action('deleted_post', [__CLASS__, 'maybe_remove_base_page']);
        add_action('init', [__CLASS__, 'maybe_upgrade_rewrite'], 15);
        add_action('init', [__CLASS__, 'maybe_flush_rewrite'], 20);
        add_filter('rewrite_rules_array', [__CLASS__, 'prepend_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'register_query_vars']);
        add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
    }

    public static function get_base_page_id(): int
    {
        $filtered = (int) apply_filters('wp_sejm_api_base_page_id', (int) apply_filters('mp_importer_base_page_id', 0));
        if ($filtered > 0) {
            return $filtered;
        }

        $stored = (int) get_option(self::OPTION_ID, 0);
        if ($stored < 1) {
            $legacy = (int) get_option(self::LEGACY_OPTION_ID, 0);
            if ($legacy > 0) {
                update_option(self::OPTION_ID, $legacy, false);
                $stored = $legacy;
            }
        }
        if ($stored > 0 && get_post_status($stored) === 'publish') {
            return $stored;
        }

        $found = self::find_page_with_block();
        if ($found > 0) {
            self::store_base_page($found);
            return $found;
        }

        return 0;
    }

    public static function get_base_slug(): string
    {
        $stored = (string) get_option(self::OPTION_SLUG, '');
        if ($stored === '') {
            $legacy = (string) get_option(self::LEGACY_OPTION_SLUG, '');
            if ($legacy !== '') {
                update_option(self::OPTION_SLUG, $legacy, false);
                $stored = $legacy;
            }
        }
        if ($stored !== '') {
            return $stored;
        }

        $page_id = self::get_base_page_id();
        if ($page_id < 1) {
            return '';
        }

        $uri = get_page_uri($page_id);
        $uri = is_string($uri) ? trim($uri, '/') : '';

        if ($uri !== '') {
            update_option(self::OPTION_SLUG, $uri, false);
        }

        return $uri;
    }

    public static function has_base_page(): bool
    {
        return self::get_base_page_id() > 0;
    }

    public static function filter_post_type_link(string $link, $post): string
    {
        if (!is_object($post) || ($post->post_type ?? '') !== 'mp') {
            return $link;
        }

        $slug = self::get_base_slug();
        if ($slug === '' || $slug === 'mp') {
            return $link;
        }

        $permalink = home_url(trailingslashit($slug) . $post->post_name);

        return user_trailingslashit($permalink);
    }

    public static function maybe_update_base_page(int $post_id, $post, bool $update): void
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (!is_object($post) || $post->post_status !== 'publish') {
            return;
        }

        $has_block = self::has_grid_block($post->post_content ?? '');
        if (!$has_block) {
            return;
        }

        self::store_base_page($post_id);
    }

    public static function maybe_remove_base_page(int $post_id): void
    {
        $stored = (int) get_option(self::OPTION_ID, 0);
        if ($stored !== $post_id) {
            return;
        }

        delete_option(self::OPTION_ID);
        delete_option(self::OPTION_SLUG);
        delete_option(self::LEGACY_OPTION_ID);
        delete_option(self::LEGACY_OPTION_SLUG);
        flush_rewrite_rules();
    }

    protected static function store_base_page(int $post_id): void
    {
        $post_id = (int) $post_id;
        if ($post_id < 1) {
            return;
        }

        $current = (int) get_option(self::OPTION_ID, 0);
        $current_slug = (string) get_option(self::OPTION_SLUG, '');

        $new_slug = get_page_uri($post_id);
        $new_slug = is_string($new_slug) ? trim($new_slug, '/') : '';

        update_option(self::OPTION_ID, $post_id, false);
        update_option(self::OPTION_SLUG, $new_slug, false);

        if ($current !== $post_id || $current_slug !== $new_slug) {
            update_option(self::OPTION_FLUSH, 1, false);
        }
    }

    protected static function find_page_with_block(): int
    {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        if (!is_array($pages)) {
            return 0;
        }

        foreach ($pages as $page_id) {
            $content = get_post_field('post_content', $page_id);
            if (self::has_grid_block($content)) {
                return (int) $page_id;
            }
        }

        return 0;
    }

    public static function maybe_flush_rewrite(): void
    {
        $should_flush = (int) get_option(self::OPTION_FLUSH, 0);
        if ($should_flush !== 1) {
            $legacy_flush = (int) get_option(self::LEGACY_OPTION_FLUSH, 0);
            if ($legacy_flush === 1) {
                update_option(self::OPTION_FLUSH, 1, false);
                delete_option(self::LEGACY_OPTION_FLUSH);
                $should_flush = 1;
            }
        }
        if ($should_flush !== 1) {
            return;
        }

        delete_option(self::OPTION_FLUSH);
        flush_rewrite_rules();
    }

    public static function maybe_upgrade_rewrite(): void
    {
        $version = (int) get_option(self::OPTION_REWRITE_VERSION, 0);
        if ($version < 1) {
            $legacy_version = (int) get_option(self::LEGACY_OPTION_REWRITE_VERSION, 0);
            if ($legacy_version > 0) {
                update_option(self::OPTION_REWRITE_VERSION, $legacy_version, false);
                delete_option(self::LEGACY_OPTION_REWRITE_VERSION);
                $version = $legacy_version;
            }
        }
        if ($version >= self::REWRITE_VERSION) {
            return;
        }

        update_option(self::OPTION_REWRITE_VERSION, self::REWRITE_VERSION, false);
        update_option(self::OPTION_FLUSH, 1, false);
    }

    public static function prepend_rewrite_rules(array $rules): array
    {
        $slug = self::get_base_slug();
        if ($slug === '' || $slug === 'mp') {
            return $rules;
        }

        $pattern = '^' . trim($slug, '/') . '/([^/]+)/?$';
        $custom = [];

        $page_id = self::get_base_page_id();
        if ($page_id > 0) {
            $custom['^' . trim($slug, '/') . '/page/([0-9]+)/?$'] = 'index.php?page_id=' . $page_id . '&mp_page=$matches[1]';
        } else {
            $custom['^' . trim($slug, '/') . '/page/([0-9]+)/?$'] = 'index.php?pagename=' . trim($slug, '/') . '&mp_page=$matches[1]';
        }

        $custom[$pattern] = 'index.php?post_type=mp&name=$matches[1]';

        return $custom + $rules;
    }

    public static function register_query_vars(array $vars): array
    {
        $vars[] = 'mp_page';
        return $vars;
    }

    protected static function has_grid_block(string $content): bool
    {
        return has_block('wp-sejm-api/mp-grid', $content) || has_block('mp-importer/mp-grid', $content);
    }
}
