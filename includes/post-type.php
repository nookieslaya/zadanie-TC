<?php


namespace MP_Importer;

class Post_Type
{
    public static function init(): void
    {
        add_action('init', [__CLASS__, 'register']);
    }

    public static function register(): void
    {
        $slug = Base_Page::get_base_slug();
        $has_base_page = $slug !== '' && $slug !== 'mp';
        $slug = $slug !== '' ? $slug : 'mp';

        $labels = [
            'name' => 'Members of Parliament',
            'singular_name' => 'Member of Parliament',
            'add_new_item' => 'Add New MP',
            'edit_item' => 'Edit MP',
            'new_item' => 'New MP',
            'view_item' => 'View MP',
            'search_items' => 'Search MPs',
            'not_found' => 'No MPs found',
            'not_found_in_trash' => 'No MPs found in Trash',
            'all_items' => 'All MPs',
            'archives' => 'MP Archives',
            'menu_name' => 'MPs',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'has_archive' => $has_base_page ? false : true,
            'rewrite' => [
                'slug' => $slug,
                'with_front' => false,
            ],
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-id',
        ];

        register_post_type('mp', $args);
    }
}
