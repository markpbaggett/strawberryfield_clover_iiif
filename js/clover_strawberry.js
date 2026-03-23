/**
 * @file
 * Drupal behavior that mounts Clover IIIF as a React app.
 *
 * Clover IIIF UMD build exposes its Viewer component via the global
 * CloverIIIF object. React 18 createRoot API is used so the viewer
 * is mounted as a proper React tree and supports all Clover features.
 *
 * drupalSettings shape (set by StrawberryCloverFormatter::viewElements):
 *   drupalSettings.format_strawberryfield.clover[elementId] = {
 *     nodeuuid:          string,
 *     manifesturl:       string,
 *     width:             string  (e.g. '720px' or '100%'),
 *     height:            number  (px),
 *     canvas_height:     string  (e.g. '500px'),
 *     information_panel: boolean,
 *     download_enabled:  boolean,
 *     background_color:  string  (e.g. '#ffffff'),
 *   }
 */
(function (Drupal, once, drupalSettings) {

  'use strict';

  Drupal.behaviors.strawberryfield_clover_initiate = {
    attach: function (context, settings) {

      const elements = once(
        'clover_strawberry',
        '.strawberry-clover-item[data-iiif-infojson]',
        context
      );

      elements.forEach(function (element) {
        var elementId = element.getAttribute('id');
        var config = (drupalSettings.format_strawberryfield &&
          drupalSettings.format_strawberryfield.clover &&
          drupalSettings.format_strawberryfield.clover[elementId])
          ? drupalSettings.format_strawberryfield.clover[elementId]
          : null;

        if (!config) {
          console.warn('Clover IIIF: no config found for element #' + elementId);
          return;
        }

        // Verify that the bundle exposed window.CloverViewer correctly.
        if (typeof window.CloverViewer === 'undefined' ||
            typeof window.CloverViewer.Viewer === 'undefined' ||
            typeof window.CloverViewer.ReactDOM === 'undefined') {
          console.error('Clover IIIF: window.CloverViewer not found. Was the bundle loaded?');
          return;
        }

        const { Viewer, React, ReactDOM } = window.CloverViewer;

        console.log('Clover IIIF: mounting on #' + elementId, config.manifesturl);

        // Apply sizes to the container.
        element.style.height = config.height + 'px';
        if (config.width !== '100%') {
          element.style.width = config.width;
        }

        // Mount with minimal options first to isolate any options-related issues.
        const root = ReactDOM.createRoot(element);
        root.render(
          React.createElement(Viewer, {
            iiifContent: config.manifesturl,
          })
        );

        console.log('Clover IIIF: render() called for #' + elementId);
      });
    }
  };

})(Drupal, once, drupalSettings);
