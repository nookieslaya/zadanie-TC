<?php


namespace MP_Importer;

use WP_Error;

class Importer
{
    protected Api_Client $api;

    public function __construct(?Api_Client $api = null)
    {
        $this->api = $api ?: new Api_Client();
    }

    public static function blank_stats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => [],
        ];
    }

    public function import(): array
    {
        $stats = self::blank_stats();
        $list = $this->api->get_mps();

        if ($list instanceof WP_Error) {
            $stats['errors']++;
            $stats['error_messages'][] = $list->get_error_message();
            return $stats;
        }

        if (!is_array($list)) {
            $stats['errors']++;
            $stats['error_messages'][] = 'Unexpected API response for MP list.';
            return $stats;
        }

        $ids = $this->collect_ids($list);

        if (empty($ids)) {
            $stats['errors']++;
            $stats['error_messages'][] = 'No MPs returned by the API.';
            return $stats;
        }

        return $this->import_ids($ids, $stats);
    }

    public function import_ids(array $ids, ?array $stats = null): array
    {
        $stats = $stats ?? self::blank_stats();

        foreach ($ids as $mp_id) {
            $mp_id = (int) $mp_id;

            if ($mp_id < 1) {
                $stats['skipped']++;
                continue;
            }

            $details = $this->api->get_mp($mp_id);

            if ($details instanceof WP_Error || !is_array($details)) {
                $stats['errors']++;
                $stats['error_messages'][] = $details instanceof WP_Error
                    ? $details->get_error_message()
                    : 'Invalid MP details response.';
                continue;
            }

            $post_id = $this->find_post_id($mp_id);
            $post_data = $this->build_post_data($details, $post_id);
            $post_data = apply_filters('mp_importer_post_data', $post_data, $details, $post_id);

            if ($post_id) {
                $post_data['ID'] = $post_id;
                $result = wp_update_post($post_data, true);
                $action = 'updated';
            } else {
                $result = wp_insert_post($post_data, true);
                $action = 'created';
            }

            if ($result instanceof WP_Error) {
                $stats['errors']++;
                $stats['error_messages'][] = $result->get_error_message();
                continue;
            }

            $post_id = (int) $result;

            $this->update_fields($post_id, $details, $mp_id);
            $this->maybe_set_thumbnail($post_id, $mp_id);

            $stats[$action]++;
        }

        return $stats;
    }

    public function collect_ids(array $list): array
    {
        $ids = [];

        foreach ($list as $item) {
            $mp_id = $this->extract_id($item);

            if ($mp_id) {
                $ids[] = $mp_id;
            }
        }

        $ids = array_values(array_unique($ids));

        return $ids;
    }

    protected function extract_id(mixed $item): int
    {
        if (!is_array($item)) {
            return 0;
        }

        foreach (['id', 'ID', 'mpId', 'mp_id'] as $key) {
            if (isset($item[$key])) {
                return (int) $item[$key];
            }
        }

        return 0;
    }

    protected function find_post_id(int $mp_id): int
    {
        $posts = get_posts([
            'post_type' => 'mp',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => 'mp_api_id',
            'meta_value' => $mp_id,
            'no_found_rows' => true,
        ]);

        return $posts ? (int) $posts[0] : 0;
    }

    protected function build_post_data(array $details, int $existing_post_id): array
    {
        $first_name = (string) ($details['firstName'] ?? '');
        $last_name = (string) ($details['lastName'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);

        if (!empty($details['firstLastName'])) {
            $full_name = (string) $details['firstLastName'];
        }

        if ($full_name === '') {
            $full_name = 'Member of Parliament #' . (string) ($details['id'] ?? '');
        }

        $status = !empty($details['active']) ? 'publish' : 'draft';

        return [
            'post_type' => 'mp',
            'post_title' => $full_name,
            'post_name' => sanitize_title($full_name),
            'post_status' => $status,
        ];
    }

    protected function update_fields(int $post_id, array $details, int $mp_id): void
    {
        $district = '';
        $district_num = $details['districtNum'] ?? '';
        $district_name = $details['districtName'] ?? '';

        if ($district_num && $district_name) {
            $district = $district_num . ' - ' . $district_name;
        } elseif ($district_name) {
            $district = (string) $district_name;
        } elseif ($district_num) {
            $district = (string) $district_num;
        }

        $first_name = (string) ($details['firstName'] ?? '');
        $last_name = (string) ($details['lastName'] ?? '');
        $full_name = (string) ($details['firstLastName'] ?? '');
        $full_name = $full_name !== '' ? $full_name : trim($first_name . ' ' . $last_name);

        // Status mandatu jest mapowany na czytelne, polskie wartosci.
        $status_mandatu = '';
        if (array_key_exists('active', $details)) {
            $status_mandatu = !empty($details['active']) ? 'aktywny' : 'nieaktywny';
        }

        $fields = [
            'mp_api_id' => $mp_id,
            // Nowy, zlokalizowany model danych.
            'imie' => $first_name,
            'nazwisko' => $last_name,
            'pelne_imie_i_nazwisko' => $full_name,
            'data_urodzenia' => (string) ($details['birthDate'] ?? ''),
            'miejsce_urodzenia' => (string) ($details['birthLocation'] ?? ''),
            'wojewodztwo' => (string) ($details['voivodeship'] ?? ''),
            'okreg_wyborczy' => $district,
            'klub_parlamentarny' => (string) ($details['club'] ?? ''),
            'status_mandatu' => $status_mandatu,
            'liczba_glosow' => (string) ($details['numberOfVotes'] ?? ''),
            'kadencja' => (string) $this->api->get_term(),
            'wyksztalcenie' => (string) ($details['educationLevel'] ?? ''),
            'zawod' => (string) ($details['profession'] ?? ''),
            'komisje_sejmowe' => $this->normalize_list($details['committees'] ?? ''),
            'funkcje_parlamentarne' => $this->normalize_list($details['functions'] ?? ($details['roles'] ?? '')),
            'email' => (string) ($details['email'] ?? ''),
            'link_do_profilu_sejmowego' => $this->api->get_public_profile_url($mp_id),
            // Pola legacy pozostawione dla kompatybilnosci wstecznej.
            'first_name' => $first_name,
            'last_name' => $last_name,
            'club' => (string) ($details['club'] ?? ''),
            'district' => $district,
            'email' => (string) ($details['email'] ?? ''),
            'education' => (string) ($details['educationLevel'] ?? ''),
            'profession' => (string) ($details['profession'] ?? ''),
            'committees' => $this->normalize_list($details['committees'] ?? ''),
            'functions' => $this->normalize_list($details['functions'] ?? ($details['roles'] ?? '')),
            'social_links' => $this->normalize_social_links($details['socialLinks'] ?? ($details['socialMedia'] ?? '')),
            'source_url' => $this->api->build_url('MP/' . $mp_id),
        ];

        $fields = apply_filters('mp_importer_mapped_fields', $fields, $details, $post_id);

        foreach ($fields as $key => $value) {
            $this->update_field_value($post_id, $key, $value);
        }
    }

    protected function normalize_list(mixed $value): string
    {
        if (is_array($value)) {
            $value = array_filter(array_map('trim', $value));
            return implode("\n", $value);
        }

        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    protected function normalize_social_links(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            return wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return '';
    }

    protected function update_field_value(int $post_id, string $field, mixed $value): void
    {
        if (function_exists('update_field')) {
            update_field($field, $value, $post_id);
            return;
        }

        update_post_meta($post_id, $field, $value);
    }

    protected function maybe_set_thumbnail(int $post_id, int $mp_id): void
    {
        $should_set = (bool) apply_filters('mp_importer_set_featured_image', true, $post_id, $mp_id);

        if (!$should_set) {
            return;
        }

        $overwrite = (bool) apply_filters('mp_importer_overwrite_thumbnails', false, $post_id, $mp_id);

        if (has_post_thumbnail($post_id) && !$overwrite) {
            return;
        }

        $photo_url = $this->api->get_photo_url($mp_id, 'photo');

        if (!wp_http_validate_url($photo_url)) {
            return;
        }

        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = media_sideload_image($photo_url, $post_id, null, 'id');

        if ($attachment_id instanceof WP_Error) {
            return;
        }

        if (is_int($attachment_id) && $attachment_id > 0) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
}
