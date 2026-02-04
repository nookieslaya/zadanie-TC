<?php


namespace WP_Sejm_API;

class SEO
{
    public static function init(): void
    {
        add_filter('document_title_parts', [__CLASS__, 'filter_document_title']);
        add_filter('wp_title', [__CLASS__, 'filter_wp_title'], 10, 2);
        add_action('wp_head', [__CLASS__, 'output_meta_description']);
    }

    public static function filter_document_title(array $parts): array
    {
        if (is_singular('mp')) {
            $name = Template_Tags::get_full_name(get_queried_object_id());
            if ($name) {
                $parts['title'] = $name;
            }
        }

        if (is_post_type_archive('mp')) {
            $parts['title'] = 'Poslowie';
        }

        return $parts;
    }

    public static function filter_wp_title(string $title, string $sep): string
    {
        if (is_singular('mp')) {
            $name = Template_Tags::get_full_name(get_queried_object_id());
            if ($name) {
                return $name . ' ' . $sep . ' ' . get_bloginfo('name');
            }
        }

        return $title;
    }

    public static function output_meta_description(): void
    {
        if (!is_singular('mp')) {
            return;
        }

        $description = Template_Tags::get_meta_description(get_queried_object_id());

        if (!$description) {
            return;
        }

        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
    }
}
