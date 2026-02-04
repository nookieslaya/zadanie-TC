<?php
/**
 * Plugin Name: WordPress Sejm API - Recruitment Task
 * Description: Demonstration plugin showing integration with the official Polish Sejm API using CPT, ACF and Gutenberg blocks.
 * Version: 1.0.0
 * Author: R.D
 * Requires PHP: 8.0
 * Text Domain: wp-sejm-api
 */


if (!defined('ABSPATH')) {
    exit;
}

define('WP_SEJM_API_VERSION', '1.0.0');
define('WP_SEJM_API_PATH', plugin_dir_path(__FILE__));
define('WP_SEJM_API_URL', plugin_dir_url(__FILE__));
define('WP_SEJM_API_BASENAME', plugin_basename(__FILE__));

require_once WP_SEJM_API_PATH . 'includes/post-type.php';
require_once WP_SEJM_API_PATH . 'includes/acf-fields.php';
require_once WP_SEJM_API_PATH . 'includes/api-client.php';
require_once WP_SEJM_API_PATH . 'includes/importer.php';
require_once WP_SEJM_API_PATH . 'includes/admin-page.php';
require_once WP_SEJM_API_PATH . 'includes/base-page.php';
require_once WP_SEJM_API_PATH . 'includes/theme-compat.php';
require_once WP_SEJM_API_PATH . 'includes/seo.php';
require_once WP_SEJM_API_PATH . 'includes/template-loader.php';
require_once WP_SEJM_API_PATH . 'includes/template-tags.php';
require_once WP_SEJM_API_PATH . 'includes/block.php';
require_once WP_SEJM_API_PATH . 'includes/grid-renderer.php';
require_once WP_SEJM_API_PATH . 'includes/single-renderer.php';

WP_Sejm_API\Post_Type::init();
WP_Sejm_API\ACF_Fields::init();
WP_Sejm_API\Admin_Page::init();
WP_Sejm_API\Base_Page::init();
WP_Sejm_API\SEO::init();
WP_Sejm_API\Template_Loader::init();
WP_Sejm_API\Block::init();

register_activation_hook(__FILE__, function (): void {
    WP_Sejm_API\Post_Type::register();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
