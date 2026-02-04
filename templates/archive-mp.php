<?php

if (!defined('ABSPATH')) {
    exit;
}

$content = (static function (): string {
    ob_start();
    ?>
    <div class="mp-archive">
        <header class="mp-archive__header mp-container">
            <h1 class="mp-title">
                <?php echo esc_html(post_type_archive_title('', false) ?: 'Poslowie'); ?>
            </h1>
            <p class="mp-subtitle">Publiczne dane pobrane z API Sejmu.</p>
        </header>

        <?php echo WP_Sejm_API\Grid_Renderer::render_archive(); ?>
    </div>
    <?php
    return (string) ob_get_clean();
})();

if (WP_Sejm_API\Theme_Compat::render_with_blade('archive-mp', $content)) {
    return;
}

WP_Sejm_API\Theme_Compat::header();
?>

<main id="main" class="main">
    <?php echo $content; ?>
</main>

<?php
WP_Sejm_API\Theme_Compat::footer();
