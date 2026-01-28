# PRD: Smart Phone Validation for WooCommerce

## Overview

Lekki plugin WordPress walidujący numery telefonów w WooCommerce przy użyciu biblioteki libphonenumber-for-php. Plugin umożliwia konfigurację domyślnego kraju walidacji, obsługuje numery międzynarodowe i lokalne, oraz normalizuje numery do jednolitego formatu (E.164). Głównym celem jest zwiększenie jakości danych kontaktowych bez pogarszania UX checkoutu.

## Goals

- Walidacja numerów telefonów w polach `billing_phone` i `shipping_phone` przy użyciu libphonenumber
- Konfigurowalny domyślny kraj dla numerów bez prefiksu międzynarodowego
- Normalizacja i zapis numerów w wybranym formacie (E.164, INTERNATIONAL, NATIONAL)
- Czas walidacji poniżej 20ms
- Pełne wsparcie i18n dla języków PL i EN w pierwszej wersji
- Brak zmian w UI checkoutu WooCommerce

## Quality Gates

These commands must pass for every user story:
- `composer run phpcs` - PHP CodeSniffer (coding standards)

## User Stories

### US-001: Struktura projektu i autoloading
As a developer, I want a properly structured plugin with PSR-4 autoloading so that classes are organized and loaded automatically.

**Acceptance Criteria:**
- [ ] Utworzona struktura katalogów: `/src/Validation`, `/src/Formatter`, `/src/Admin`, `/src/Config`
- [ ] Plik `composer.json` z zależnością `giggsey/libphonenumber-for-php`
- [ ] Konfiguracja PSR-4 autoloading dla namespace `Shift64\SmartPhoneValidation`
- [ ] Główny plik pluginu `verify-phone-number-shift64.php` z headerami WordPress
- [ ] Plugin ładuje autoloader composera

### US-002: Sprawdzanie zależności WooCommerce
As a site admin, I want to see a clear message when WooCommerce is not active so that I understand why the plugin doesn't work.

**Acceptance Criteria:**
- [ ] Plugin sprawdza czy WooCommerce jest aktywny przy ładowaniu
- [ ] Jeśli WooCommerce nieaktywny, wyświetla admin notice z informacją o brakującej zależności
- [ ] Plugin nie wykonuje żadnej logiki walidacji gdy WooCommerce nieaktywny
- [ ] Komunikat jest przetłumaczalny (i18n)

### US-003: Strona ustawień w WooCommerce
As a site admin, I want a settings page in WooCommerce settings so that I can configure phone validation behavior.

**Acceptance Criteria:**
- [ ] Nowa zakładka/sekcja w WooCommerce → Ustawienia
- [ ] Pole select "Default country" z listą krajów ISO-2 (PL, DE, FR, US, etc.)
- [ ] Pole select "Validation mode": "Default country + international" / "International only"
- [ ] Pole select "Output format": E.164 / INTERNATIONAL / NATIONAL
- [ ] Checkbox "Enable formatting on save"
- [ ] Checkbox "Enable validation" (globalny przełącznik)
- [ ] Ustawienia zapisywane w `wp_options`
- [ ] Etykiety pól są przetłumaczalne (i18n)

### US-004: Klasa normalizacji numeru telefonu
As a developer, I want a Normalizer class so that phone input is cleaned before parsing.

**Acceptance Criteria:**
- [ ] Klasa `Shift64\SmartPhoneValidation\Validation\Normalizer`
- [ ] Metoda usuwa białe znaki, myślniki, nawiasy, kropki
- [ ] Metoda zamienia prefix "00" na "+"
- [ ] Metoda wykonuje trim na wejściu
- [ ] Metoda zwraca znormalizowany string

### US-005: Klasa walidacji numeru telefonu
As a developer, I want a PhoneValidator class that validates phone numbers using libphonenumber so that invalid numbers are rejected.

**Acceptance Criteria:**
- [ ] Klasa `Shift64\SmartPhoneValidation\Validation\PhoneValidator`
- [ ] Metoda przyjmuje numer i opcjonalny kod kraju
- [ ] Wykrywa numery międzynarodowe (zaczynające się od "+")
- [ ] Dla numerów bez prefiksu używa default country z ustawień
- [ ] W trybie "International only" odrzuca numery bez prefiksu "+"
- [ ] Używa `libphonenumber` do parsowania i `isValidNumber()` do walidacji
- [ ] Zwraca obiekt wyniku z informacją o sukcesie/błędzie i sparsowanym numerem

### US-006: Klasa formatowania numeru telefonu
As a developer, I want a PhoneFormatter class so that valid numbers are formatted according to settings.

**Acceptance Criteria:**
- [ ] Klasa `Shift64\SmartPhoneValidation\Formatter\PhoneFormatter`
- [ ] Metoda formatuje numer do E.164 (np. +48224100500)
- [ ] Metoda formatuje numer do INTERNATIONAL (np. +48 22 410 05 00)
- [ ] Metoda formatuje numer do NATIONAL (np. 22 410 05 00)
- [ ] Format wybierany na podstawie ustawień pluginu

### US-007: Integracja z checkout WooCommerce - billing_phone
As a customer, I want my billing phone number validated at checkout so that only valid numbers are accepted.

**Acceptance Criteria:**
- [ ] Hook na walidację pola `billing_phone` w checkout WooCommerce
- [ ] Walidacja uruchamiana tylko gdy plugin jest włączony w ustawieniach
- [ ] Niepoprawny numer blokuje checkout z komunikatem błędu
- [ ] Poprawny numer jest formatowany zgodnie z ustawieniami przed zapisem
- [ ] Komunikaty błędów: "Podaj poprawny numer telefonu" / "Numer musi zawierać prefiks kraju"
- [ ] Komunikaty są przetłumaczalne (i18n)

### US-008: Integracja z checkout WooCommerce - shipping_phone
As a customer, I want my shipping phone number validated at checkout so that only valid numbers are accepted.

**Acceptance Criteria:**
- [ ] Hook na walidację pola `shipping_phone` w checkout WooCommerce
- [ ] Walidacja uruchamiana tylko gdy plugin jest włączony w ustawieniach
- [ ] Logika walidacji identyczna jak dla `billing_phone`
- [ ] Pole `shipping_phone` walidowane tylko gdy jest wypełnione (pole opcjonalne)

### US-009: Pliki tłumaczeń PL i EN
As a site admin, I want the plugin to support Polish and English languages so that messages display in the correct language.

**Acceptance Criteria:**
- [ ] Plik `.pot` template z wszystkimi stringami do tłumaczenia
- [ ] Plik `.po` i `.mo` dla języka polskiego (pl_PL)
- [ ] Plik `.po` i `.mo` dla języka angielskiego (en_US)
- [ ] Text domain pluginu prawidłowo zarejestrowany
- [ ] Wszystkie user-facing stringi używają funkcji `__()` lub `_e()`

### US-010: Wyłączenie pluginu przywraca domyślne zachowanie
As a site admin, I want WooCommerce to work normally when the plugin is disabled so that there are no side effects.

**Acceptance Criteria:**
- [ ] Deaktywacja pluginu usuwa wszystkie hooki walidacji
- [ ] Checkout WooCommerce działa z domyślną walidacją po deaktywacji
- [ ] Ustawienia pluginu pozostają w bazie (nie są usuwane przy deaktywacji)
- [ ] Ponowna aktywacja przywraca konfigurację

## Functional Requirements

- FR-1: Plugin musi walidować numery telefonów używając biblioteki libphonenumber-for-php
- FR-2: Numery zaczynające się od "+" muszą być parsowane jako międzynarodowe
- FR-3: Numery bez prefiksu muszą być parsowane z domyślnym krajem (jeśli tryb to pozwala)
- FR-4: Prefix "00" musi być automatycznie zamieniany na "+"
- FR-5: W trybie "International only" numery bez "+" muszą być odrzucane
- FR-6: Poprawne numery muszą być zapisywane w wybranym formacie (E.164/INTERNATIONAL/NATIONAL)
- FR-7: Plugin musi wyświetlać komunikat gdy WooCommerce jest nieaktywny
- FR-8: Wszystkie komunikaty użytkownika muszą być przetłumaczalne

## Non-Goals (Out of Scope)

- UI select country na froncie checkout
- Integracje SMS / bramki SMS
- Walidacja innych pól niż telefon
- Integracja z kontem użytkownika WordPress
- Auto-formatowanie numeru w trakcie pisania
- Automatyczne wykrywanie kraju na podstawie IP
- Wsparcie dla więcej niż 2 języków w pierwszej wersji

## Technical Considerations

- PHP >= 8.1 wymagane
- WordPress >= 6.x wymagane
- WooCommerce >= 7.x wymagane
- Zależność: `giggsey/libphonenumber-for-php` przez Composer
- Namespace: `Shift64\SmartPhoneValidation`
- Czas walidacji musi być poniżej 20ms
- Plugin musi działać z cache (brak dynamicznych zapytań przy każdym request)
- Obsługa WordPress Multisite
- Pliki `.gitignore` muszą wykluczać foldery `tasks/` i `.ralph-tui/`

## Success Metrics

- Wszystkie acceptance criteria spełnione
- Brak fatal errors PHP
- Czas walidacji pojedynczego numeru < 20ms
- Plugin przechodzi PHP CodeSniffer bez błędów
- Brak konfliktów z domyślną walidacją WooCommerce

## Open Questions

- Czy w przyszłości dodać wsparcie dla walidacji w REST API WooCommerce?
- Czy dodać opcję logowania odrzuconych numerów do debugowania?