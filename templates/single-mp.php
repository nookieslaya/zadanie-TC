<?php

if (!defined('ABSPATH')) {
    exit;
}

$content = (static function (): string {
    ob_start();
    ?>
    <div class="mp-single">
        <div class="mp-container">
            <?php echo MP_Importer\Single_Renderer::render(); ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
})();

if (MP_Importer\Theme_Compat::render_with_blade('single-mp', $content)) {
    return;
}

MP_Importer\Theme_Compat::header();
?>

<main id="main" class="main">
    <?php echo $content; ?>
</main>

<?php
MP_Importer\Theme_Compat::footer();
