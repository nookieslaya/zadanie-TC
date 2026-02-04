<?php


namespace MP_Importer;

use WP_Error;

class Api_Client
{
    public function get_term(): int
    {
        $term = (int) apply_filters('mp_importer_term', 10);

        if ($term < 1) {
            $term = 10;
        }

        return $term;
    }

    public function get_base_url(): string
    {
        $base = (string) apply_filters('mp_importer_base_url', 'https://api.sejm.gov.pl/sejm/');

        return untrailingslashit($base);
    }

    public function build_url(string $path): string
    {
        $path = ltrim($path, '/');

        return $this->get_base_url() . '/term' . $this->get_term() . '/' . $path;
    }

    public function get(string $path): array|WP_Error
    {
        $url = $this->build_url($path);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'MP Importer/' . MP_IMPORTER_VERSION . '; ' . home_url('/'),
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($status < 200 || $status >= 300) {
            return new WP_Error('mp_importer_http', 'API request failed.', [
                'status' => $status,
                'body' => $body,
            ]);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('mp_importer_json', 'API response was not valid JSON.');
        }

        return is_array($data) ? $data : [];
    }

    public function get_mps(): array|WP_Error
    {
        return $this->get('MP');
    }

    public function get_mp(int $id): array|WP_Error
    {
        return $this->get('MP/' . $id);
    }

    public function get_photo_url(int $id, string $size = 'photo'): string
    {
        $suffix = $size === 'mini' ? 'photo-mini' : 'photo';

        return $this->build_url('MP/' . $id . '/' . $suffix);
    }

    public function get_public_profile_url(int $id): string
    {
        $id = (int) $id;
        if ($id < 1) {
            return '';
        }

        $term = $this->get_term();
        $base = (string) apply_filters('mp_importer_public_profile_base', 'https://www.sejm.gov.pl/');
        $base = untrailingslashit($base);

        return $base . '/Sejm' . $term . '.nsf/posel.xsp?id=' . $id;
    }
}
