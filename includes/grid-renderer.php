<?php

namespace MP_Importer;

use WP_Query;

class Grid_Renderer
{
    public static function render_block(array $attributes = []): string
    {
        if (!isset($attributes['postsPerPage']) && isset($attributes['limit'])) {
            $attributes['postsPerPage'] = (int) $attributes['limit'];
        }

        $attributes = wp_parse_args($attributes, [
            'postsPerPage' => 12,
            'enablePagination' => true,
            'enableFilters' => true,
        ]);

        return self::render($attributes, [
            'context' => 'block',
        ]);
    }

    public static function render_archive(): string
    {
        return self::render([
            'postsPerPage' => (int) get_option('posts_per_page'),
            'enablePagination' => true,
            'enableFilters' => true,
        ], [
            'context' => 'archive',
        ]);
    }

    protected static function render(array $attributes, array $context): string
    {
        $attributes = wp_parse_args($attributes, [
            'postsPerPage' => 12,
            'enablePagination' => true,
            'enableFilters' => true,
        ]);

        $filters = $attributes['enableFilters'] ? self::get_filters_from_request() : [
            'klub_parlamentarny' => '',
            'okreg_wyborczy' => '',
            'imie_nazwisko' => '',
        ];

        $query_args = self::build_query_args($attributes, $filters);
        $query = new WP_Query($query_args);

        $cards = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $card = Template_Tags::get_archive_card(get_the_ID());
                $card['permalink'] = get_permalink();
                $cards[] = $card;
            }
        }
        wp_reset_postdata();

        $pagination = '';
        if (!empty($attributes['enablePagination']) && $query->max_num_pages > 1) {
            $pagination = self::build_pagination($query, $filters);
        }

        $filter_options = [
            'klub_parlamentarny' => [],
            'okreg_wyborczy' => [],
        ];

        if (!empty($attributes['enableFilters'])) {
            $filter_options['klub_parlamentarny'] = self::get_distinct_meta_values([
                'klub_parlamentarny',
                'club',
            ]);
            $filter_options['okreg_wyborczy'] = self::get_distinct_meta_values([
                'okreg_wyborczy',
                'district',
            ]);
        }

        $template_data = [
            'context' => $context['context'] ?? 'block',
            'cards' => $cards,
            'filters' => [
                'enabled' => (bool) $attributes['enableFilters'],
                'options' => $filter_options,
                'current' => $filters,
                'action' => self::get_filter_action($context),
                'reset_url' => self::get_filter_action($context),
            ],
            'pagination' => $pagination,
            'has_results' => !empty($cards),
        ];

        return self::render_template('parts/mp-grid.php', $template_data);
    }

    protected static function build_query_args(array $attributes, array $filters): array
    {
        $pagination_enabled = !empty($attributes['enablePagination']);
        $posts_per_page = (int) $attributes['postsPerPage'];
        $posts_per_page = $posts_per_page > 0 ? $posts_per_page : -1;
        if (!$pagination_enabled) {
            $posts_per_page = -1;
        }

        $paged = self::get_paged();

        $args = [
            'post_type' => 'mp',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => !$pagination_enabled || $posts_per_page === -1,
        ];

        if ($pagination_enabled && $posts_per_page !== -1) {
            $args['paged'] = $paged;
        }

        // Filters use meta_query to keep the implementation lightweight and compatible with existing data.
        $meta_query = [];

        if (!empty($filters['klub_parlamentarny'])) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key' => 'klub_parlamentarny',
                    'value' => $filters['klub_parlamentarny'],
                    'compare' => '=',
                ],
                [
                    'key' => 'club',
                    'value' => $filters['klub_parlamentarny'],
                    'compare' => '=',
                ],
            ];
        }

        if (!empty($filters['okreg_wyborczy'])) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key' => 'okreg_wyborczy',
                    'value' => $filters['okreg_wyborczy'],
                    'compare' => '=',
                ],
                [
                    'key' => 'district',
                    'value' => $filters['okreg_wyborczy'],
                    'compare' => '=',
                ],
            ];
        }

        if (!empty($filters['imie_nazwisko'])) {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key' => 'pelne_imie_i_nazwisko',
                    'value' => $filters['imie_nazwisko'],
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'imie',
                    'value' => $filters['imie_nazwisko'],
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'nazwisko',
                    'value' => $filters['imie_nazwisko'],
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'first_name',
                    'value' => $filters['imie_nazwisko'],
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'last_name',
                    'value' => $filters['imie_nazwisko'],
                    'compare' => 'LIKE',
                ],
            ];
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    protected static function get_filters_from_request(): array
    {
        $club = isset($_GET['klub_parlamentarny']) ? sanitize_text_field(wp_unslash($_GET['klub_parlamentarny'])) : '';
        $district = isset($_GET['okreg_wyborczy']) ? sanitize_text_field(wp_unslash($_GET['okreg_wyborczy'])) : '';
        $name = isset($_GET['imie_nazwisko']) ? sanitize_text_field(wp_unslash($_GET['imie_nazwisko'])) : '';

        return [
            'klub_parlamentarny' => $club,
            'okreg_wyborczy' => $district,
            'imie_nazwisko' => $name,
        ];
    }

    protected static function get_paged(): int
    {
        $mp_page = (int) get_query_var('mp_page');
        if ($mp_page > 0) {
            return $mp_page;
        }

        $paged = (int) get_query_var('paged');
        if ($paged < 1) {
            $paged = (int) get_query_var('page');
        }

        return max(1, $paged);
    }

    protected static function build_pagination(WP_Query $query, array $filters): string
    {
        // paginate_links keeps URLs consistent across archives and pages embedding the block.
        $filter_args = array_filter([
            'klub_parlamentarny' => $filters['klub_parlamentarny'],
            'okreg_wyborczy' => $filters['okreg_wyborczy'],
            'imie_nazwisko' => $filters['imie_nazwisko'],
        ], static function ($value) {
            return $value !== '';
        });

        $pagination_base = self::get_pagination_base();

        return (string) paginate_links([
            'base' => $pagination_base['base'],
            'format' => $pagination_base['format'],
            'current' => self::get_paged(),
            'total' => (int) $query->max_num_pages,
            'type' => 'list',
            'add_args' => $filter_args,
        ]);
    }

    protected static function get_pagination_base(): array
    {
        if (is_post_type_archive('mp')) {
            return [
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '',
            ];
        }

        $action = self::get_filter_action(['context' => 'block']);
        $base_page_id = Base_Page::get_base_page_id();
        $current_id = (int) get_queried_object_id();

        if ($base_page_id > 0 && $current_id === $base_page_id && get_option('permalink_structure')) {
            return [
                'base' => trailingslashit($action) . 'page/%#%/',
                'format' => '',
            ];
        }

        $base = add_query_arg('mp_page', '%#%', $action);

        return [
            'base' => $base,
            'format' => '',
        ];
    }

    protected static function get_filter_action(array $context): string
    {
        if (($context['context'] ?? '') === 'archive') {
            $link = get_post_type_archive_link('mp');
            if (is_string($link) && $link !== '') {
                return $link;
            }
        }

        $permalink = get_permalink();

        return is_string($permalink) && $permalink !== '' ? $permalink : home_url('/');
    }

    protected static function get_distinct_meta_values(array $keys): array
    {
        global $wpdb;

        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '%s'));
        $sql = "
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'mp'
                AND p.post_status = 'publish'
                AND pm.meta_key IN ($placeholders)
                AND pm.meta_value <> ''
            ORDER BY pm.meta_value ASC
        ";

        $prepared = $wpdb->prepare($sql, $keys);
        $values = $wpdb->get_col($prepared);

        if (!is_array($values)) {
            return [];
        }

        $values = array_map('trim', $values);
        $values = array_values(array_unique(array_filter($values)));

        return $values;
    }

    protected static function render_template(string $relative_path, array $data): string
    {
        $template = MP_IMPORTER_PATH . 'templates/' . ltrim($relative_path, '/');

        if (!file_exists($template)) {
            return '';
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $template;

        return (string) ob_get_clean();
    }
}
