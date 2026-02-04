<?php

if (!defined('ABSPATH')) {
    exit;
}

$dane_podstawowe = $sections['dane_podstawowe'] ?? [];
$mandat = $sections['mandat'] ?? [];
$wyksztalcenie = $sections['wyksztalcenie'] ?? [];
$komisje = $sections['komisje'] ?? [];
$funkcje = $sections['funkcje'] ?? [];
$kontakt = $sections['kontakt'] ?? [];
$content = $sections['content'] ?? '';
$back_link = $back_link ?? [];
$photo = $profile['photo'] ?? [];
?>

<article class="mp-profile">
    <?php if (!empty($back_link['url'])) : ?>
        <a class="mp-back-link mp-contact-link mp-contact-link--profile" href="<?php echo esc_url($back_link['url']); ?>">
            Powrot
        </a>
    <?php endif; ?>
    <header class="mp-profile__header">
        <div class="mp-profile__photo">
            <?php if (!empty($photo['url'])) : ?>
                <img
                    src="<?php echo esc_url($photo['url']); ?>"
                    alt="<?php echo esc_attr($profile['pelne_imie_i_nazwisko']); ?>"
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
                <div class="mp-profile__photo-placeholder"></div>
            <?php endif; ?>
        </div>
        <div class="mp-profile__headline">
            <h1 class="mp-profile__name"><?php echo esc_html($profile['pelne_imie_i_nazwisko']); ?></h1>
            <?php if (!empty($profile['klub_parlamentarny'])) : ?>
                <p class="mp-profile__meta"><span>Klub:</span> <?php echo esc_html($profile['klub_parlamentarny']); ?></p>
            <?php endif; ?>
            <?php if (!empty($profile['okreg_wyborczy'])) : ?>
                <p class="mp-profile__meta"><span>Okreg:</span> <?php echo esc_html($profile['okreg_wyborczy']); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!empty($dane_podstawowe)) : ?>
        <section class="mp-profile__section">
            <h2>Dane podstawowe</h2>
            <dl class="mp-profile__details">
                <?php foreach ($dane_podstawowe as $label => $value) : ?>
                    <div>
                        <dt><?php echo esc_html($label); ?></dt>
                        <dd><?php echo esc_html($value); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>

    <?php if (!empty($mandat)) : ?>
        <section class="mp-profile__section">
            <h2>Mandat</h2>
            <dl class="mp-profile__details">
                <?php foreach ($mandat as $label => $value) : ?>
                    <div>
                        <dt><?php echo esc_html($label); ?></dt>
                        <dd><?php echo esc_html($value); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>

    <?php if (!empty($wyksztalcenie)) : ?>
        <section class="mp-profile__section">
            <h2>Wyksztalcenie i zawod</h2>
            <dl class="mp-profile__details">
                <?php foreach ($wyksztalcenie as $label => $value) : ?>
                    <div>
                        <dt><?php echo esc_html($label); ?></dt>
                        <dd><?php echo esc_html($value); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
    <?php endif; ?>

    <?php if (!empty($komisje) || !empty($funkcje)) : ?>
        <section class="mp-profile__section">
            <h2>Aktywnosc parlamentarna</h2>
            <?php if (!empty($komisje)) : ?>
                <h3>Komisje sejmowe</h3>
                <ul class="mp-profile__list">
                    <?php foreach ($komisje as $committee) : ?>
                        <li><?php echo esc_html($committee); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($funkcje)) : ?>
                <h3>Funkcje parlamentarne</h3>
                <ul class="mp-profile__list">
                    <?php foreach ($funkcje as $function) : ?>
                        <li><?php echo esc_html($function); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($content !== '') : ?>
        <section class="mp-profile__section">
            <h2>Opis</h2>
            <div class="mp-profile__content">
                <?php echo $content; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($kontakt)) : ?>
        <section class="mp-profile__section">
            <div class="mp-profile__contact">
                <?php if (!empty($profile['email'])) : ?>
                    <a class="mp-contact-link mp-contact-link--email" href="mailto:<?php echo esc_attr($profile['email']); ?>">
                        Email: <?php echo esc_html(antispambot($profile['email'])); ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($profile['link_do_profilu_sejmowego'])) : ?>
                    <a class="mp-contact-link mp-contact-link--profile" href="<?php echo esc_url($profile['link_do_profilu_sejmowego']); ?>" target="_blank" rel="noopener">
                        Zobacz profil w sejm.gov.pl
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<?php if (!empty($schema_json)) : ?>
    <script type="application/ld+json">
        <?php echo $schema_json; ?>
    </script>
<?php endif; ?>
