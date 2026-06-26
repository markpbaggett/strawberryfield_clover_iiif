# Strawberry Field Clover IIIF

A Drupal 10/11 module that provides [Clover IIIF](https://samvera-labs.github.io/clover-iiif/) as an optional viewer for
Archipelago.

## Features

- Renders IIIF manifests using the Clover IIIF viewer.
- Configurable display options such as width, height, canvas height, and background color
- Optional information panel and IIIF buttons
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
