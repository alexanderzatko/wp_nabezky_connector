# WP Na bezky! Connector - Translation Files

This directory contains the translation files for the WP Na bezky! Connector plugin.

## Files

- `wp-nabezky-connector.pot` - Translation template file (source for all translations)
- `wp-nabezky-connector-sk_SK.po` - Slovak translation (source)
- `wp-nabezky-connector-sk_SK.mo` - Slovak translation (compiled)
- `wp-nabezky-connector-cs_CZ.po` - Czech translation (source)
- `wp-nabezky-connector-cs_CZ.mo` - Czech translation (compiled)

## Supported Languages

- **Slovak (sk_SK)** - Complete translation
- **Czech (cs_CZ)** - Complete translation

## How Translations Work

The plugin uses WordPress's built-in internationalization (i18n) system:

1. All user-facing strings in the plugin are wrapped with `__()` or `_e()` functions
2. The text domain is `wp-nabezky-connector`
3. Translation files are automatically loaded from this `/languages/` directory
4. JavaScript strings are localized using `wp_localize_script()`

## Adding New Translations

To add support for a new language:

1. Copy `wp-nabezky-connector.pot` to `wp-nabezky-connector-{locale}.po`
2. Translate all `msgstr ""` entries to your target language
3. Compile the .po file to .mo format using:
   ```bash
   msgfmt wp-nabezky-connector-{locale}.po -o wp-nabezky-connector-{locale}.mo
   ```

## Updating Translations

When the plugin is updated with new translatable strings:

1. Regenerate the .pot file from the source code
2. Update existing .po files using:
   ```bash
   msgmerge -U wp-nabezky-connector-{locale}.po wp-nabezky-connector.pot
   ```
3. Translate any new strings
4. Recompile .mo files

## WordPress Language Detection

WordPress will automatically load the appropriate translation file based on:
- Site language setting (Settings > General > Site Language)
- User language preference (if logged in)
- Language files available in this directory

The plugin will fall back to English if no translation is available for the user's language.

