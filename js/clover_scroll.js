/**
 * @file
 * Drupal behavior that mounts the Clover IIIF Scroll component as a React app.
 *
 * drupalSettings shape (set by StrawberryCloverScrollFormatter::viewElements):
 *   drupalSettings.format_strawberryfield.clover_scroll[elementId] = {
 *     nodeuuid:               string,
 *     manifesturl:            string,
 *     width:                  string  (e.g. '100%' or '720px'),
 *     figure_display:         string  ('image-viewer' | 'thumbnail'),
 *     figure_aspect_ratio:    number|null,
 *     annotations_motivations: string[],
 *     scroll_offset:          number,
 *   }
 */
(function (Drupal, once, drupalSettings) {

  'use strict';

  Drupal.behaviors.strawberryfield_clover_scroll = {
    attach: function (context, settings) {

      const elements = once(
        'clover_scroll',
        '.strawberry-clover-scroll-item[data-iiif-infojson]',
        context
      );

      elements.forEach(function (element) {
        var elementId = element.getAttribute('id');
        var config = (drupalSettings.format_strawberryfield &&
          drupalSettings.format_strawberryfield.clover_scroll &&
          drupalSettings.format_strawberryfield.clover_scroll[elementId])
          ? drupalSettings.format_strawberryfield.clover_scroll[elementId]
          : null;

        if (!config) {
          console.warn('Clover IIIF Scroll: no config found for element #' + elementId);
          return;
        }

        if (typeof window.CloverViewer === 'undefined' ||
            typeof window.CloverViewer.Scroll === 'undefined' ||
            typeof window.CloverViewer.ReactDOM === 'undefined') {
          console.error('Clover IIIF Scroll: window.CloverViewer.Scroll not found. Was the bundle loaded?');
          return;
        }

        const { Scroll, React, ReactDOM } = window.CloverViewer;

        if (config.width !== '100%') {
          element.style.width = config.width;
        }

        var figure = {
          display: config.figure_display,
        };
        if (config.figure_aspect_ratio) {
          figure.aspectRatio = config.figure_aspect_ratio;
        }

        var options = {
          figure: figure,
        };

        if (config.scroll_offset > 0) {
          options.offset = config.scroll_offset;
        }

        if (config.annotations_motivations && config.annotations_motivations.length > 0) {
          options.annotations = { motivations: config.annotations_motivations };
        }

        var scrollProps = {
          iiifContent: config.manifesturl,
          options: options,
        };

        const root = ReactDOM.createRoot(element);
        root.render(React.createElement(Scroll, scrollProps));
      });
    }
  };

})(Drupal, once, drupalSettings);
