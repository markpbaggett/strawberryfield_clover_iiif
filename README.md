# Strawberry Field Clover IIIF

A Drupal 10/11 module that provides [Clover IIIF](https://samvera-labs.github.io/clover-iiif/) as an optional viewer for
Archipelago.

## Features

- Renders IIIF manifests using the Clover IIIF Viewer (OpenSeadragon 6 powered zoom)
- Renders IIIF Collections as a horizontally scrolling carousel (Embla Carousel)
- Renders IIIF manifests as a vertically scrolling gallery of canvases
- Configurable display options such as width, height, canvas height, and background color
- Optional information panel and IIIF buttons
- Resource icons (video/audio/PDF badges) on time-based canvases
- Canvas summary display in the Information Panel
- CSS custom property theming (`--clover-color-*`) or legacy `customTheme` (deprecated)
- Item search/filter for Slider component
- Hides content based on field-level embargo settings
- Built with React 18 and bundled via Vite for self-contained frontend delivery

## Requirements

- Drupal 10 or 11
- [Strawberry Field](https://github.com/esmero/strawberryfield) module
- [Format Strawberry Field](https://github.com/esmero/format_strawberryfield) module

## Installation

1. Copy this module into your Drupal site's modules directory:
   ```shell
   cp -r strawberryfield_clover_iiif /path/to/drupal/web/modules/contrib/
   ```

2. Enable the module via Drush:
   ```shell
   drush en strawberryfield_clover_iiif
   ```
   Or enable it through the Drupal admin UI at **Extend** (`/admin/modules`).

The `strawberryfield` and `format_strawberryfield` modules must already be installed before enabling this module.

## Rebuilding the JavaScript Bundle

The Clover IIIF viewer is bundled as a self-contained UMD library. To rebuild after updating dependencies:

```shell
cd build && npm install && npm run build
```

This outputs the compiled bundle to `js/clover-viewer.bundle.js`.

## Theming

### CSS Custom Properties (recommended)

Clover 3.16.0+ uses CSS custom properties for theming instead of the deprecated `customTheme` prop. Available variables include:

- `--clover-color-primary`, `--clover-color-primary-muted`, `--clover-color-primary-alt`
- `--clover-color-accent`, `--clover-color-accent-muted`, `--clover-color-accent-alt`
- `--clover-color-secondary`, `--clover-color-secondary-muted`, `--clover-color-secondary-alt`
- `--clover-radius`, `--clover-radius-pill`
- `--clover-thumbnail-width`, `--clover-thumbnail-height`

Use the **Custom CSS variables** setting in the formatter configuration, or the `data-custom-css-variables` attribute in Twig templates, to provide raw CSS declarations. For example:

```css
--clover-color-primary: #ff0000;
--clover-radius: 5px;
```

### Legacy Custom Theme (deprecated)

The `customTheme` setting is deprecated in Clover 3.16.0. It remains functional but will emit a `console.warn` in the browser console. Migrate to CSS custom properties by using the **Custom CSS variables** setting instead — replace your `customTheme` JSON tokens (e.g., `{"colors": {"primary": "#ff0000"}}`) with CSS custom property declarations (e.g., `--clover-color-primary: #ff0000;`).

See the [Clover IIIF documentation](https://samvera-labs.github.io/clover-iiif/docs/viewer) for full theming details.
