# MP Importer

Wtyczka WordPress do importu i prezentacji poslow na Sejm RP na podstawie publicznego API Sejmu.

## Najwazniejsze funkcje
- Rejestruje CPT `mp` (posel).
- Importuje dane z API Sejmu i zapisuje je w ACF.
- Dodaje blok Gutenberga **MPs Grid** (siatka poslow).
- Renderuje czytelne strony pojedynczego posla.
- Dziala z motywami klasycznymi, builderami i motywami Blade/Sage.

## Wymagania
- WordPress 6.x
- PHP 8.0+
- Advanced Custom Fields (wersja darmowa)

## Instalacja
1. Skopiuj katalog `mp-importer` do `wp-content/plugins/`.
2. Aktywuj wtyczke w panelu WordPress.
3. Upewnij sie, ze ACF jest aktywne.

## Jak uzywac
### Import danych
W panelu: **Poslowie → MP Importer**  
Kliknij **Import / Refresh MPs** i poczekaj na zakonczenie.

### Siatka poslow (blok)
Dodaj blok **MPs Grid** na dowolnej stronie.
Blok wspiera:
- paginacje
- filtry (klub, okreg, imie i nazwisko)

### Strony pojedynczych poslow
Link do posla ma postac:
- `/mp/{slug}` (domyslnie)
- lub `/twoja-strona/{slug}` jezeli blok jest na stronie bazowej

## Struktura danych (ACF)
Wtyczka rejestruje pola w jezyku polskim, m.in.:
- imie, nazwisko, pelne_imie_i_nazwisko
- data_urodzenia, miejsce_urodzenia, wojewodztwo
- okreg_wyborczy, klub_parlamentarny, status_mandatu, liczba_glosow
- wyksztalcenie, zawod
- komisje_sejmowe, funkcje_parlamentarne
- email, link_do_profilu_sejmowego

## Dostosowanie
- Zmiana kadencji: filtr `mp_importer_term`
- Zmiana bazowego adresu profilu Sejmu: `mp_importer_public_profile_base`
- Zmiana URL API: `mp_importer_base_url`

## Uwagi techniczne
- Szablony sa w `templates/` oraz `views/` (dla motywow Blade).
- Logika jest w `includes/`.
- Wtyczka nie wymaga page template.

## Support
W razie problemow: sprawdz logi PHP oraz status API `api.sejm.gov.pl`.
