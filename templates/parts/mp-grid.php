<?php

if (!defined('ABSPATH')) {
    exit;
}

$filters_enabled = $filters['enabled'] ?? false;
$filter_options = $filters['options'] ?? [];
$filter_current = $filters['current'] ?? [];
$filter_action = $filters['action'] ?? '';
$filter_reset = $filters['reset_url'] ?? $filter_action;
?>

<section class="mp-grid-wrapper alignfull">
    <div class="mp-container alignfull">
        <?php if ($filters_enabled) : ?>
            <form class="mp-grid-filters" method="get" action="<?php echo esc_url($filter_action); ?>">
                <div class="mp-grid-filters__field">
                    <label for="mp-filter-name">Imie i nazwisko</label>
                    <input
                        id="mp-filter-name"
                        type="text"
                        name="imie_nazwisko"
                        value="<?php echo esc_attr($filter_current['imie_nazwisko'] ?? ''); ?>"
                        placeholder="np. Adam Lubonski"
                    />
                </div>
                <div class="mp-grid-filters__field">
                    <label for="mp-filter-club">Klub parlamentarny</label>
                    <select id="mp-filter-club" name="klub_parlamentarny">
                        <option value="">Wszystkie kluby</option>
                        <?php foreach (($filter_options['klub_parlamentarny'] ?? []) as $option) : ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php echo selected($option, $filter_current['klub_parlamentarny'] ?? '', false); ?>>
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mp-grid-filters__field">
                    <label for="mp-filter-district">Okreg wyborczy</label>
                    <select id="mp-filter-district" name="okreg_wyborczy">
                        <option value="">Wszystkie okregi</option>
                        <?php foreach (($filter_options['okreg_wyborczy'] ?? []) as $option) : ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php echo selected($option, $filter_current['okreg_wyborczy'] ?? '', false); ?>>
                                <?php echo esc_html($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mp-grid-filters__actions">
                    <button type="submit" class="button button-primary">Filtruj</button>
                    <?php if (!empty($filter_current['klub_parlamentarny']) || !empty($filter_current['okreg_wyborczy']) || !empty($filter_current['imie_nazwisko'])) : ?>
                        <a class="mp-grid-filters__reset" href="<?php echo esc_url($filter_reset); ?>">Wyczysc filtry</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($has_results) : ?>
            <div class="mp-grid">
                <?php foreach ($cards as $card) : ?>
                    <?php $photo = $card['photo'] ?? []; ?>
                    <article class="mp-card">
                        <a class="mp-card__link" href="<?php echo esc_url($card['permalink']); ?>">
                            <?php if (!empty($photo['url'])) : ?>
                                <img
                                    class="mp-card__photo"
                                    src="<?php echo esc_url($photo['url']); ?>"
                                    alt="<?php echo esc_attr($card['name']); ?>"
                                    loading="lazy"
                                    decoding="async"
                                    <?php if (!empty($photo['srcset'])) : ?>
                                        srcset="<?php echo esc_attr($photo['srcset']); ?>"
                                    <?php endif; ?>
                                    <?php if (!empty($photo['sizes'])) : ?>
                                        sizes="<?php echo esc_attr($photo['sizes']); ?>"
                                    <?php endif; ?>
                                    <?php if (!empty($photo['width'])) : ?>
                                        width="<?php echo (int) $photo['width']; ?>"
                                    <?php endif; ?>
                                    <?php if (!empty($photo['height'])) : ?>
                                        height="<?php echo (int) $photo['height']; ?>"
                                    <?php endif; ?>
                                />
                            <?php else : ?>
                                <div class="mp-card__photo mp-card__photo--placeholder"></div>
                            <?php endif; ?>
                            <div class="mp-card__body">
                                <h2 class="mp-card__name"><?php echo esc_html($card['name']); ?></h2>
                                <?php if (!empty($card['club'])) : ?>
                                    <p class="mp-card__meta"><?php echo esc_html($card['club']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($card['district'])) : ?>
                                    <p class="mp-card__meta"><?php echo esc_html($card['district']); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p>Brak poslow do wyswietlenia.</p>
        <?php endif; ?>

        <?php if (!empty($pagination)) : ?>
            <nav class="mp-pagination" aria-label="Nawigacja stron">
                <?php echo $pagination; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
