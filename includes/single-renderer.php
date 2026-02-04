<?php

namespace MP_Importer;

class Single_Renderer
{
    protected static bool $rendering = false;

    public static function render(): string
    {
        if (self::$rendering) {
            return '';
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        self::$rendering = true;

        $profile = Template_Tags::get_profile($post_id);
        $sections = self::build_sections($profile, $post_id);
        $schema_json = Template_Tags::get_schema_json($post_id);
        $back_link = Template_Tags::get_back_link();

        $html = self::render_template('parts/single-mp.php', [
            'profile' => $profile,
            'sections' => $sections,
            'schema_json' => $schema_json,
            'back_link' => $back_link,
        ]);

        self::$rendering = false;

        return $html;
    }

    public static function is_rendering(): bool
    {
        return self::$rendering;
    }

    protected static function build_sections(array $profile, int $post_id): array
    {
        $filter_values = static function (array $items): array {
            return array_filter($items, static function ($value) {
                return $value !== '' && $value !== null;
            });
        };

        $content = get_post_field('post_content', $post_id);
        $content = is_string($content) ? trim($content) : '';
        if ($content !== '') {
            $content = apply_filters('the_content', $content);
        }

        return [
            'dane_podstawowe' => $filter_values([
                'Data urodzenia' => $profile['data_urodzenia'],
                'Miejsce urodzenia' => $profile['miejsce_urodzenia'],
                'Województwo' => $profile['wojewodztwo'],
                'Okręg wyborczy' => $profile['okreg_wyborczy'],
            ]),
            'mandat' => $filter_values([
                'Klub parlamentarny' => $profile['klub_parlamentarny'],
                'Status mandatu' => $profile['status_mandatu'],
                'Liczba głosów' => $profile['liczba_glosow'],
                'Kadencja' => $profile['kadencja'],
            ]),
            'wyksztalcenie' => $filter_values([
                'Wykształcenie' => $profile['wyksztalcenie'],
                'Zawód' => $profile['zawod'],
            ]),
            'komisje' => $profile['komisje_sejmowe'],
            'funkcje' => $profile['funkcje_parlamentarne'],
            'kontakt' => $filter_values([
                'Email' => $profile['email'],
                'Link do profilu sejmowego' => $profile['link_do_profilu_sejmowego'],
            ]),
            'content' => $content,
        ];
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
