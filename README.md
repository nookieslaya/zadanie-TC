# WordPress Sejm API – Recruitment Task

Wtyczka WordPress do importu i prezentacji posłów na Sejm RP na podstawie publicznego API Sejmu.

## Najważniejsze funkcje
- Rejestruje CPT `mp` (poseł).
- Importuje dane z API Sejmu i zapisuje je w ACF.
- Dodaje blok Gutenberga **MPs Grid** (siatka posłów).
- Renderuje czytelne strony pojedynczego posła.
- Działa z motywami klasycznymi, builderami i motywami Blade/Sage.

## Wymagania
- WordPress 6.x
- PHP 8.0+
- Advanced Custom Fields (wersja darmowa)

## Instalacja
1. Skopiuj katalog `wp-sejm-api` do `wp-content/plugins/`.
2. Aktywuj wtyczkę w panelu WordPress.
3. Upewnij się, że ACF jest aktywne.

## Jak używać
### Import danych
W panelu: **Posłowie → WordPress Sejm API**  
Kliknij **Import / Refresh MPs** i poczekaj na zakończenie.

### Siatka posłów (blok)
Dodaj blok **MPs Grid** na dowolnej stronie.
Blok wspiera:
- paginację
- filtry (klub, okręg, imię i nazwisko)

### Strony pojedynczych posłów
Link do posła ma postać:
- `/mp/{slug}` (domyślnie)
- lub `/twoja-strona/{slug}` jeżeli blok jest na stronie bazowej

## Struktura danych (ACF)
Wtyczka rejestruje pola w języku polskim, m.in.:
- imię, nazwisko, pełne_imie_i_nazwisko
- data_urodzenia, miejsce_urodzenia, województwo
- okręg_wyborczy, klub_parlamentarny, status_mandatu, liczba_głosów
- wykształcenie, zawód
- komisje_sejmowe, funkcje_parlamentarne
- email, link_do_profilu_sejmowego

## Dostosowanie
- Zmiana kadencji: filtr `wp_sejm_api_term`
- Zmiana bazowego adresu profilu Sejmu: `wp_sejm_api_public_profile_base`
- Zmiana URL API: `wp_sejm_api_base_url`

## Uwagi techniczne
- Szablony są w `templates/` oraz `views/` (dla motywów Blade).
- Logika jest w `includes/`.
- Wtyczka nie wymaga page template.

## Wsparcie
W razie problemów: sprawdź logi PHP oraz status API `api.sejm.gov.pl`.
