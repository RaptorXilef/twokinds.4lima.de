# Die 7-1 Ordnerstruktur

- `sass/`
  - `main.scss` (Die Hauptdatei, die alles importiert)
    - `abstracts/` (Variablen, Mixins, Funktionen)
    - `vendors/` (Externe Bibliotheken, z.B. Summernote-Overrides, Normalize)
    - `base/` (Reset, Typography, globale HTML-Elemente)
    - `layout/` (Header, Footer, Grid, Sidebar, Main-Container)
    - `components/` (Buttons, Modals, Comic-Nav, Cookie-Banner, Character-Cards)
    - `pages/` (Spezifische Styles für Archiv, FAQ, ...)
    - `themes/` (Weggelassen, da über CSS-Variablen gelöst)

## Zusammenfassung der Dateistruktur

- **sass/**
  - **abstracts/**
    - `_functions.scss` (Neu erstellt)
    - `_mixins.scss`
    - `_placeholders.scss` (Neu erstellt)
    - `_variables.scss`

  - **base/**
    - `_reset.scss`
    - `_typography.scss`

  - **components/**
    - `_buttons.scss`
    - `_cookie-banner.scss`
    - `_modals.scss`
    - `_navigation.scss`

  - **layout/**
    - `_footer.scss`
    - `_grid.scss`
    - `_header.scss`
    - `_sidebar.scss`

  - **pages/**
    - `_admin.scss`
    - `_archive.scss`
    - `_characters.scss`
    - `_comic.scss`
    - `_fanart.scss`
    - `_misc.scss`

  - **vendors/**
    - `_normalize.scss`
    - `_summernote.scss`

  - `main.scss`
