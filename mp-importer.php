<?php
/**
 * Plugin Name: MP Importer
 * Description: Imports Polish Members of Parliament from the public API and exposes them as a custom post type.
 * Version: 1.0.0
 * Author: R.D
 * Requires PHP: 8.0
 */


if (!defined('ABSPATH')) {
    exit;
}

define('MP_IMPORTER_VERSION', '1.0.0');
define('MP_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('MP_IMPORTER_URL', plugin_dir_url(__FILE__));
define('MP_IMPORTER_BASENAME', plugin_basename(__FILE__));

require_once MP_IMPORTER_PATH . 'includes/post-type.php';
require_once MP_IMPORTER_PATH . 'includes/acf-fields.php';
require_once MP_IMPORTER_PATH . 'includes/api-client.php';
require_once MP_IMPORTER_PATH . 'includes/importer.php';
require_once MP_IMPORTER_PATH . 'includes/admin-page.php';
require_once MP_IMPORTER_PATH . 'includes/base-page.php';
require_once MP_IMPORTER_PATH . 'includes/theme-compat.php';
require_once MP_IMPORTER_PATH . 'includes/seo.php';
require_once MP_IMPORTER_PATH . 'includes/template-loader.php';
require_once MP_IMPORTER_PATH . 'includes/template-tags.php';
require_once MP_IMPORTER_PATH . 'includes/block.php';
require_once MP_IMPORTER_PATH . 'includes/grid-renderer.php';
require_once MP_IMPORTER_PATH . 'includes/single-renderer.php';

MP_Importer\Post_Type::init();
MP_Importer\ACF_Fields::init();
MP_Importer\Admin_Page::init();
MP_Importer\Base_Page::init();
MP_Importer\SEO::init();
MP_Importer\Template_Loader::init();
MP_Importer\Block::init();

register_activation_hook(__FILE__, function (): void {
    MP_Importer\Post_Type::register();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
