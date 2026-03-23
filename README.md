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

## Rebuilding the JavaScript Bundle

The Clover IIIF viewer is bundled as a self-contained UMD library. To rebuild after updating dependencies:

```shell
cd build && npm install && npm run build
```

This outputs the compiled bundle to `js/clover-viewer.bundle.js`.
