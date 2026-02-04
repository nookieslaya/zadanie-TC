<?php

namespace MP_Importer;

class Template_Tags
{
    public static function get_full_name(int $post_id): string
    {
        $full = self::get_first_meta($post_id, ['pelne_imie_i_nazwisko']);

        if ($full !== '') {
            return $full;
        }

        $imie = self::get_first_meta($post_id, ['imie', 'first_name']);
        $nazwisko = self::get_first_meta($post_id, ['nazwisko', 'last_name']);
        $name = trim($imie . ' ' . $nazwisko);

        if ($name !== '') {
            return $name;
        }

        return get_the_title($post_id);
    }

    public static function get_archive_card(int $post_id): array
    {
        $photo = self::get_photo_data($post_id, 'grid');

        return [
            'name' => self::get_full_name($post_id),
            'club' => self::get_first_meta($post_id, ['klub_parlamentarny', 'club']),
            'district' => self::get_first_meta($post_id, ['okreg_wyborczy', 'district']),
            'photo_url' => $photo['url'] ?? '',
            'photo' => $photo,
        ];
    }

    public static function get_profile(int $post_id): array
    {
        $imie = self::get_first_meta($post_id, ['imie', 'first_name']);
        $nazwisko = self::get_first_meta($post_id, ['nazwisko', 'last_name']);
        $pelne = self::get_first_meta($post_id, ['pelne_imie_i_nazwisko']);

        if ($pelne === '') {
            $pelne = trim($imie . ' ' . $nazwisko);
        }
        if ($pelne === '') {
            $pelne = get_the_title($post_id);
        }

        $status = self::get_first_meta($post_id, ['status_mandatu']);
        if ($status === '') {
            $status = get_post_status($post_id) === 'publish' ? 'aktywny' : 'nieaktywny';
        }

        $profile = [
            'pelne_imie_i_nazwisko' => $pelne,
            'imie' => $imie,
            'nazwisko' => $nazwisko,
            'data_urodzenia' => self::get_first_meta($post_id, ['data_urodzenia']),
            'miejsce_urodzenia' => self::get_first_meta($post_id, ['miejsce_urodzenia']),
            'wojewodztwo' => self::get_first_meta($post_id, ['wojewodztwo']),
            'okreg_wyborczy' => self::get_first_meta($post_id, ['okreg_wyborczy', 'district']),
            'klub_parlamentarny' => self::get_first_meta($post_id, ['klub_parlamentarny', 'club']),
            'status_mandatu' => $status,
            'liczba_glosow' => self::get_first_meta($post_id, ['liczba_glosow']),
            'kadencja' => self::get_first_meta($post_id, ['kadencja']),
            'wyksztalcenie' => self::get_first_meta($post_id, ['wyksztalcenie', 'education']),
            'zawod' => self::get_first_meta($post_id, ['zawod', 'profession']),
            'komisje_sejmowe' => self::split_lines(self::get_first_meta($post_id, ['komisje_sejmowe', 'committees'])),
            'funkcje_parlamentarne' => self::split_lines(self::get_first_meta($post_id, ['funkcje_parlamentarne', 'functions'])),
            'email' => self::get_first_meta($post_id, ['email']),
            'link_do_profilu_sejmowego' => self::get_profile_link($post_id),
            'photo_url' => self::get_photo_url($post_id),
            'photo' => self::get_photo_data($post_id, 'single'),
        ];

        return $profile;
    }

    public static function get_meta_description(int $post_id): string
    {
        $profile = self::get_profile($post_id);

        $parts = array_filter([
            $profile['klub_parlamentarny'] ? 'Klub: ' . $profile['klub_parlamentarny'] : '',
            $profile['okreg_wyborczy'] ? 'Okreg: ' . $profile['okreg_wyborczy'] : '',
            $profile['zawod'] ? 'Zawod: ' . $profile['zawod'] : '',
            $profile['wyksztalcenie'] ? 'Wyksztalcenie: ' . $profile['wyksztalcenie'] : '',
        ]);

        $text = $profile['pelne_imie_i_nazwisko'] ?: get_the_title($post_id);

        if (!empty($parts)) {
            $text .= ' - ' . implode(' | ', $parts);
        }

        $text = wp_strip_all_tags($text);

        return wp_html_excerpt($text, 160, '...');
    }

    public static function get_schema_json(int $post_id): string
    {
        $profile = self::get_profile($post_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Politician',
            'name' => $profile['pelne_imie_i_nazwisko'] ?: get_the_title($post_id),
            'image' => $profile['photo_url'],
            'affiliation' => $profile['klub_parlamentarny'],
            'email' => $profile['email'] ? 'mailto:' . $profile['email'] : '',
            'jobTitle' => 'Posel na Sejm',
            'url' => get_permalink($post_id),
        ];

        $schema = array_filter($schema);

        return (string) wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function get_back_link(): array
    {
        $page_id = Base_Page::get_base_page_id();
        if ($page_id > 0) {
            return [
                'url' => get_permalink($page_id),
                'label' => 'Powrot do: ' . get_the_title($page_id),
            ];
        }

        $archive = get_post_type_archive_link('mp');
        if (is_string($archive) && $archive !== '') {
            return [
                'url' => $archive,
                'label' => 'Powrot do listy poslow',
            ];
        }

        $referer = wp_get_referer();
        if ($referer) {
            return [
                'url' => $referer,
                'label' => 'Powrot',
            ];
        }

        return [
            'url' => home_url('/'),
            'label' => 'Powrot na strone glowna',
        ];
    }

    protected static function get_first_meta(int $post_id, array $fields): string
    {
        foreach ($fields as $field) {
            $value = self::get_meta_value($post_id, $field);

            if (is_array($value)) {
                continue;
            }

            if ($value === 0 || $value === '0') {
                return '0';
            }

            $value = is_scalar($value) ? trim((string) $value) : '';

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected static function get_meta_value(int $post_id, string $field): mixed
    {
        if (function_exists('get_field')) {
            return get_field($field, $post_id);
        }

        return get_post_meta($post_id, $field, true);
    }

    protected static function get_profile_link(int $post_id): string
    {
        $value = self::get_first_meta($post_id, ['link_do_profilu_sejmowego', 'source_url']);

        if ($value !== '' && stripos($value, 'api.sejm.gov.pl') === false) {
            return $value;
        }

        $api_id = (int) self::get_meta_value($post_id, 'mp_api_id');
        if ($api_id < 1) {
            return $value;
        }

        $client = new Api_Client();
        $public_url = $client->get_public_profile_url($api_id);

        return $public_url !== '' ? $public_url : $value;
    }

    protected static function split_lines(mixed $value): array
    {
        if (is_array($value)) {
            $lines = array_map('trim', $value);
            return array_values(array_filter($lines));
        }

        if (!is_string($value)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $value);
        $lines = array_map('trim', $lines ?: []);

        return array_values(array_filter($lines));
    }

    protected static function parse_social_links(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $value = trim($value);
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $links = [];
            foreach ($decoded as $item) {
                if (is_string($item)) {
                    $links[] = [
                        'label' => self::pretty_url($item),
                        'url' => self::normalize_url($item),
                    ];
                    continue;
                }

                if (is_array($item)) {
                    $url = $item['url'] ?? ($item['link'] ?? '');
                    $label = $item['label'] ?? ($item['name'] ?? $url);

                    if ($url) {
                        $links[] = [
                            'label' => (string) $label,
                            'url' => self::normalize_url((string) $url),
                        ];
                    }
                }
            }

            return $links;
        }

        $lines = preg_split('/\r\n|\r|\n/', $value);
        $links = [];

        foreach ($lines ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (strpos($line, '|') !== false) {
                [$label, $url] = array_map('trim', explode('|', $line, 2));
            } else {
                $label = self::pretty_url($line);
                $url = $line;
            }

            $links[] = [
                'label' => $label ?: $url,
                'url' => self::normalize_url($url),
            ];
        }

        return $links;
    }

    protected static function normalize_url(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    protected static function pretty_url(string $url): string
    {
        $url = preg_replace('~^https?://~i', '', $url);
        $url = rtrim($url, '/');

        return $url ?: 'Link';
    }

    protected static function get_photo_url(int $post_id): string
    {
        $thumb = get_the_post_thumbnail_url($post_id, 'large');

        if ($thumb) {
            return $thumb;
        }

        $api_id = (int) self::get_meta_value($post_id, 'mp_api_id');

        if ($api_id < 1) {
            return '';
        }

        $client = new Api_Client();

        return $client->get_photo_url($api_id, 'photo');
    }

    protected static function get_photo_data(int $post_id, string $context = 'grid'): array
    {
        $attachment_id = get_post_thumbnail_id($post_id);
        if ($attachment_id) {
            $size = $context === 'single' ? 'full' : 'large';
            $src = wp_get_attachment_image_src($attachment_id, $size);

            if (is_array($src)) {
                return [
                    'url' => $src[0] ?? '',
                    'width' => $src[1] ?? 0,
                    'height' => $src[2] ?? 0,
                    'srcset' => wp_get_attachment_image_srcset($attachment_id, $size) ?: '',
                    'sizes' => wp_get_attachment_image_sizes($attachment_id, $size) ?: '',
                    'is_external' => false,
                ];
            }
        }

        $api_id = (int) self::get_meta_value($post_id, 'mp_api_id');
        if ($api_id < 1) {
            return [];
        }

        $client = new Api_Client();
        $photo = $client->get_photo_url($api_id, 'photo');
        if ($photo === '') {
            return [];
        }

        $mini = $client->get_photo_url($api_id, 'mini');
        $srcset = '';
        if ($mini !== '') {
            $srcset = $mini . ' 200w, ' . $photo . ' 400w';
        }

        $sizes = $context === 'single'
            ? '(min-width: 960px) 320px, 60vw'
            : '(min-width: 960px) 260px, 50vw';

        return [
            'url' => $photo,
            'width' => 0,
            'height' => 0,
            'srcset' => $srcset,
            'sizes' => $sizes,
            'is_external' => true,
        ];
    }
}
