/**
 * @file
 * Drupal behavior that mounts the Clover IIIF Slider component as a React app.
 *
 * The Slider requires a IIIF Collection URL (not a single-item Manifest).
 *
 * drupalSettings shape (set by StrawberryCloverSliderFormatter::viewElements):
 *   drupalSettings.format_strawberryfield.clover_slider[elementId] = {
 *     nodeuuid:       string,
 *     collectionurl:  string,
 *     width:          string  (e.g. '100%' or '720px'),
 *     credentials:    string  ('omit' | 'same-origin' | 'include'),
 *     custom_view_all: string|null,
 *   }
 */
(function (Drupal, once, drupalSettings) {

  'use strict';

  Drupal.behaviors.strawberryfield_clover_slider = {
    attach: function (context, settings) {

      const elements = once(
        'clover_slider',
        '.strawberry-clover-slider-item[data-iiif-infojson]',
        context
      );

      elements.forEach(function (element) {
        var elementId = element.getAttribute('id');
        var config = (drupalSettings.format_strawberryfield &&
          drupalSettings.format_strawberryfield.clover_slider &&
          drupalSettings.format_strawberryfield.clover_slider[elementId])
          ? drupalSettings.format_strawberryfield.clover_slider[elementId]
          : null;

        if (!config) {
          console.warn('Clover IIIF Slider: no config found for element #' + elementId);
          return;
        }

        if (typeof window.CloverViewer === 'undefined' ||
            typeof window.CloverViewer.Slider === 'undefined' ||
            typeof window.CloverViewer.ReactDOM === 'undefined') {
          console.error('Clover IIIF Slider: window.CloverViewer.Slider not found. Was the bundle loaded?');
          return;
        }

        const { Slider, React, ReactDOM } = window.CloverViewer;

        if (config.width !== '100%') {
          element.style.width = config.width;
        }

        var options = {};

        if (config.credentials && config.credentials !== 'omit') {
          options.credentials = config.credentials;
        }

        if (config.custom_view_all) {
          options.customViewAll = config.custom_view_all;
        }

        var sliderProps = {
          iiifContent: config.collectionurl,
        };

        if (Object.keys(options).length > 0) {
          sliderProps.options = options;
        }

        const root = ReactDOM.createRoot(element);
        root.render(React.createElement(Slider, sliderProps));
      });
    }
  };

})(Drupal, once, drupalSettings);
