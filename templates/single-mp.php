<?php

if (!defined('ABSPATH')) {
    exit;
}

$content = (static function (): string {
    ob_start();
    ?>
    <div class="mp-single">
        <div class="mp-container">
            <?php echo WP_Sejm_API\Single_Renderer::render(); ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
})();

if (WP_Sejm_API\Theme_Compat::render_with_blade('single-mp', $content)) {
    return;
}

WP_Sejm_API\Theme_Compat::header();
?>

<main id="main" class="main">
    <?php echo $content; ?>
</main>

<?php
WP_Sejm_API\Theme_Compat::footer();
