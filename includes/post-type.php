<?php


namespace WP_Sejm_API;

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
            'name' => 'Posłowie',
            'singular_name' => 'Poseł',
            'add_new_item' => 'Dodaj posła',
            'edit_item' => 'Edytuj posła',
            'new_item' => 'Nowy poseł',
            'view_item' => 'Zobacz posła',
            'search_items' => 'Szukaj posłów',
            'not_found' => 'Nie znaleziono posłów',
            'not_found_in_trash' => 'Brak posłów w koszu',
            'all_items' => 'Wszyscy posłowie',
            'archives' => 'Archiwum posłów',
            'menu_name' => 'Posłowie',
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
