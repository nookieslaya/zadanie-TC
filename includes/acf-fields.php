<?php

namespace MP_Importer;

class ACF_Fields
{
    public static function init(): void
    {
        add_action('acf/init', [__CLASS__, 'register']);
        add_action('admin_notices', [__CLASS__, 'maybe_notice']);
    }

    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_mp_importer',
            'title' => 'Dane posla',
            'fields' => [
                [
                    'key' => 'field_mp_importer_tab_podstawowe',
                    'label' => 'Dane podstawowe',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_mp_importer_api_id',
                    'label' => 'ID w API',
                    'name' => 'mp_api_id',
                    'type' => 'number',
                    'required' => 1,
                    'readonly' => 1,
                    'min' => 1,
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_imie',
                    'label' => 'Imie',
                    'name' => 'imie',
                    'type' => 'text',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_nazwisko',
                    'label' => 'Nazwisko',
                    'name' => 'nazwisko',
                    'type' => 'text',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_pelne_imie_i_nazwisko',
                    'label' => 'Pelne imie i nazwisko',
                    'name' => 'pelne_imie_i_nazwisko',
                    'type' => 'text',
                    'readonly' => 1,
                    'wrapper' => ['width' => '50'],
                    'instructions' => 'Wypelniane automatycznie na podstawie danych z API.',
                ],
                [
                    'key' => 'field_mp_importer_data_urodzenia',
                    'label' => 'Data urodzenia',
                    'name' => 'data_urodzenia',
                    'type' => 'text',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_miejsce_urodzenia',
                    'label' => 'Miejsce urodzenia',
                    'name' => 'miejsce_urodzenia',
                    'type' => 'text',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_wojewodztwo',
                    'label' => 'Wojewodztwo',
                    'name' => 'wojewodztwo',
                    'type' => 'text',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_okreg_wyborczy',
                    'label' => 'Okreg wyborczy',
                    'name' => 'okreg_wyborczy',
                    'type' => 'text',
                    'wrapper' => ['width' => '50'],
                ],
                [
                    'key' => 'field_mp_importer_tab_mandat',
                    'label' => 'Mandat',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_mp_importer_klub_parlamentarny',
                    'label' => 'Klub parlamentarny',
                    'name' => 'klub_parlamentarny',
                    'type' => 'text',
                    'wrapper' => ['width' => '50'],
                ],
                [
                    'key' => 'field_mp_importer_status_mandatu',
                    'label' => 'Status mandatu',
                    'name' => 'status_mandatu',
                    'type' => 'text',
                    'readonly' => 1,
                    'wrapper' => ['width' => '25'],
                    'instructions' => 'Wypelniane automatycznie na podstawie pola active z API.',
                ],
                [
                    'key' => 'field_mp_importer_liczba_glosow',
                    'label' => 'Liczba glosow',
                    'name' => 'liczba_glosow',
                    'type' => 'number',
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_kadencja',
                    'label' => 'Kadencja',
                    'name' => 'kadencja',
                    'type' => 'text',
                    'readonly' => 1,
                    'wrapper' => ['width' => '25'],
                ],
                [
                    'key' => 'field_mp_importer_tab_wyksztalcenie',
                    'label' => 'Wyksztalcenie i zawod',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_mp_importer_wyksztalcenie',
                    'label' => 'Wyksztalcenie',
                    'name' => 'wyksztalcenie',
                    'type' => 'textarea',
                    'rows' => 3,
                ],
                [
                    'key' => 'field_mp_importer_zawod',
                    'label' => 'Zawod',
                    'name' => 'zawod',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_mp_importer_tab_aktywnosc',
                    'label' => 'Aktywnosc parlamentarna',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_mp_importer_komisje_sejmowe',
                    'label' => 'Komisje sejmowe',
                    'name' => 'komisje_sejmowe',
                    'type' => 'textarea',
                    'rows' => 4,
                    'instructions' => 'Po jednej komisji w linii.',
                ],
                [
                    'key' => 'field_mp_importer_funkcje_parlamentarne',
                    'label' => 'Funkcje parlamentarne',
                    'name' => 'funkcje_parlamentarne',
                    'type' => 'textarea',
                    'rows' => 4,
                    'instructions' => 'Po jednej funkcji w linii.',
                ],
                [
                    'key' => 'field_mp_importer_tab_kontakt',
                    'label' => 'Kontakt i zrodla',
                    'type' => 'tab',
                    'placement' => 'top',
                ],
                [
                    'key' => 'field_mp_importer_email',
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'wrapper' => ['width' => '50'],
                ],
                [
                    'key' => 'field_mp_importer_link_do_profilu_sejmowego',
                    'label' => 'Link do profilu sejmowego',
                    'name' => 'link_do_profilu_sejmowego',
                    'type' => 'url',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'mp',
                    ],
                ],
            ],
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ]);
    }

    public static function maybe_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (function_exists('acf_add_local_field_group')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo 'MP Importer wymaga wtyczki Advanced Custom Fields (free), aby wyswietlic dodatkowe pola.';
        echo '</p></div>';
    }
}
