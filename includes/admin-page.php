<?php

namespace MP_Importer;

class Admin_Page
{
    protected static ?string $hook_suffix = null;

    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_post_mp_importer_run', [__CLASS__, 'handle_import']);
        add_action('wp_ajax_mp_importer_start', [__CLASS__, 'ajax_start']);
        add_action('wp_ajax_mp_importer_step', [__CLASS__, 'ajax_step']);
    }

    public static function register_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'edit.php?post_type=mp',
            'MP Importer',
            'MP Importer',
            'manage_options',
            'mp-importer',
            [__CLASS__, 'render_page']
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if ($hook !== self::$hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'mp-importer-admin',
            MP_IMPORTER_URL . 'assets/admin.css',
            [],
            MP_IMPORTER_VERSION
        );

        wp_enqueue_script(
            'mp-importer-admin',
            MP_IMPORTER_URL . 'assets/admin.js',
            [],
            MP_IMPORTER_VERSION,
            true
        );

        wp_localize_script('mp-importer-admin', 'MPImporterAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mp_importer_ajax'),
            'batchSize' => 15,
            'strings' => [
                'starting' => 'Starting import...',
                'running' => 'Import in progress...',
                'done' => 'Import complete.',
                'error' => 'Import failed. Please try again.',
            ],
        ]);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notice = self::get_notice();

        echo '<div class="wrap mp-importer-admin">';
        echo '<h1>MP Importer</h1>';
        echo '<p>Import Members of Parliament from the public Sejm API into the MP custom post type.</p>';
        echo '<p>Run this manually whenever you want to refresh the dataset.</p>';

        if ($notice) {
            echo '<div class="notice notice-' . esc_attr($notice['type']) . '"><p>';
            echo esc_html($notice['message']);
            echo '</p></div>';
        }

        echo '<div class="mp-importer-actions">';
        echo '<button type="button" class="button button-primary" id="mp-importer-run">Import / Refresh MPs</button>';
        echo '</div>';

        echo '<div id="mp-importer-progress" class="mp-progress" hidden>';
        echo '<div class="mp-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">';
        echo '<span class="mp-progress__bar-fill"></span>';
        echo '</div>';
        echo '<div class="mp-progress__label" id="mp-importer-progress-label">0%</div>';
        echo '<div class="mp-progress__status" id="mp-importer-status"></div>';
        echo '</div>';

        echo '<noscript>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="mp_importer_run" />';
        wp_nonce_field('mp_importer_run');
        submit_button('Import / Refresh MPs');
        echo '</form>';
        echo '</noscript>';

        echo '<hr />';
        echo '<h2>Notes</h2>';
        echo '<ul style="list-style: disc; padding-left: 20px;">';
        echo '<li>MP Importer registers the MP custom post type and ACF fields automatically.</li>';
        echo '<li>You can change the Sejm term via the mp_importer_term filter.</li>';
        echo '<li>Drafts are created for inactive MPs.</li>';
        echo '</ul>';
        echo '</div>';
    }

    public static function handle_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('You are not allowed to run this import.');
        }

        check_admin_referer('mp_importer_run');

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $importer = new Importer();
        $result = $importer->import();

        $message = sprintf(
            'Import finished. Created: %d, Updated: %d, Skipped: %d, Errors: %d.',
            (int) $result['created'],
            (int) $result['updated'],
            (int) $result['skipped'],
            (int) $result['errors']
        );

        if (!empty($result['error_messages'])) {
            $message .= ' Last error: ' . sanitize_text_field($result['error_messages'][0]);
        }

        self::set_notice([
            'type' => $result['errors'] > 0 ? 'warning' : 'success',
            'message' => $message,
        ]);

        wp_safe_redirect(self::menu_url());
        exit;
    }

    public static function ajax_start(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        check_ajax_referer('mp_importer_ajax', 'nonce');

        $api = new Api_Client();
        $list = $api->get_mps();

        if ($list instanceof \WP_Error) {
            wp_send_json_error(['message' => $list->get_error_message()], 500);
        }

        if (!is_array($list)) {
            wp_send_json_error(['message' => 'Unexpected API response.'], 500);
        }

        $importer = new Importer($api);
        $ids = $importer->collect_ids($list);

        if (empty($ids)) {
            wp_send_json_error(['message' => 'No MPs returned by the API.'], 500);
        }

        $token = wp_generate_password(12, false, false);
        $state = [
            'ids' => $ids,
            'total' => count($ids),
            'position' => 0,
            'stats' => Importer::blank_stats(),
        ];

        set_transient(self::state_key($token), $state, HOUR_IN_SECONDS);

        wp_send_json_success([
            'token' => $token,
            'total' => $state['total'],
            'processed' => 0,
            'stats' => $state['stats'],
        ]);
    }

    public static function ajax_step(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        check_ajax_referer('mp_importer_ajax', 'nonce');

        $token = isset($_POST['token']) ? sanitize_text_field((string) $_POST['token']) : '';

        if ($token === '') {
            wp_send_json_error(['message' => 'Missing import token.'], 400);
        }

        $state = get_transient(self::state_key($token));

        if (!$state || !is_array($state)) {
            wp_send_json_error(['message' => 'Import session expired. Please start again.'], 410);
        }

        $batch_size = isset($_POST['batchSize']) ? (int) $_POST['batchSize'] : 15;
        $batch_size = max(1, min(50, $batch_size));

        $offset = (int) ($state['position'] ?? 0);
        $ids = array_slice($state['ids'], $offset, $batch_size);

        if (empty($ids)) {
            delete_transient(self::state_key($token));

            wp_send_json_success([
                'done' => true,
                'processed' => $state['total'],
                'total' => $state['total'],
                'stats' => $state['stats'],
            ]);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $importer = new Importer();
        $batch_stats = $importer->import_ids($ids);

        $state['stats'] = self::merge_stats($state['stats'], $batch_stats);
        $state['position'] = $offset + count($ids);

        $processed = $state['position'];
        $done = $processed >= $state['total'];

        if ($done) {
            delete_transient(self::state_key($token));
        } else {
            set_transient(self::state_key($token), $state, HOUR_IN_SECONDS);
        }

        wp_send_json_success([
            'done' => $done,
            'processed' => $processed,
            'total' => $state['total'],
            'stats' => $state['stats'],
        ]);
    }

    protected static function merge_stats(array $current, array $incoming): array
    {
        foreach (['created', 'updated', 'skipped', 'errors'] as $key) {
            $current[$key] = (int) ($current[$key] ?? 0) + (int) ($incoming[$key] ?? 0);
        }

        if (!isset($current['error_messages'])) {
            $current['error_messages'] = [];
        }

        if (!empty($incoming['error_messages']) && is_array($incoming['error_messages'])) {
            $current['error_messages'] = array_merge($current['error_messages'], $incoming['error_messages']);
            $current['error_messages'] = array_slice($current['error_messages'], 0, 5);
        }

        return $current;
    }

    protected static function state_key(string $token): string
    {
        return 'mp_importer_state_' . $token;
    }

    protected static function menu_url(): string
    {
        return (string) menu_page_url('mp-importer', false);
    }

    protected static function set_notice(array $notice): void
    {
        $user_id = get_current_user_id();
        set_transient('mp_importer_notice_' . $user_id, $notice, 60);
    }

    protected static function get_notice(): ?array
    {
        $user_id = get_current_user_id();
        $notice = get_transient('mp_importer_notice_' . $user_id);

        if (!$notice) {
            return null;
        }

        delete_transient('mp_importer_notice_' . $user_id);

        return is_array($notice) ? $notice : null;
    }
}
