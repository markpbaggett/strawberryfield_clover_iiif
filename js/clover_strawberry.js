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
 *     nodeuuid:                           string,
 *     manifesturl:                        string,
 *     width:                              string  (e.g. '720px' or '100%'),
 *     height:                             number  (px),
 *     canvas_height:                      string  (e.g. '500px'),
 *     canvas_background_color:            string,
 *     background:                         string,
 *     show_title:                         boolean,
 *     show_iiif_badge:                    boolean,
 *     show_download:                      boolean,
 *     cross_origin:                       string|null,
 *     with_credentials:                   boolean,
 *     request_headers:                    object|null,
 *     open_seadragon:                     object|null,
 *     annotations_motivations:            string[],
 *     ignore_caption_labels:              string[],
 *     content_search_enabled:             boolean,
 *     information_panel_open:             boolean,
 *     information_panel_render_about:     boolean,
 *     information_panel_render_annotation:boolean,
 *     information_panel_render_supplementing: boolean,
 *     information_panel_render_content_search: boolean,
 *     information_panel_render_toggle:    boolean,
 *     information_panel_default_tab:      string,
 *     custom_theme:                       object|null,
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

        if (typeof window.CloverViewer === 'undefined' ||
            typeof window.CloverViewer.Viewer === 'undefined' ||
            typeof window.CloverViewer.ReactDOM === 'undefined') {
          console.error('Clover IIIF: window.CloverViewer not found. Was the bundle loaded?');
          return;
        }

        const { Viewer, React, ReactDOM } = window.CloverViewer;

        element.style.height = config.height + 'px';
        if (config.width !== '100%') {
          element.style.width = config.width;
        }

        var options = {
          canvasHeight: config.canvas_height,
          canvasBackgroundColor: config.canvas_background_color,
          showTitle: config.show_title,
          showIIIFBadge: config.show_iiif_badge,
          showDownload: config.show_download,
          contentSearch: {
            enabled: config.content_search_enabled,
          },
          informationPanel: {
            open: config.information_panel_open,
            renderAbout: config.information_panel_render_about,
            renderAnnotation: config.information_panel_render_annotation,
            renderSupplementing: config.information_panel_render_supplementing,
            renderContentSearch: config.information_panel_render_content_search,
            renderToggle: config.information_panel_render_toggle,
          },
        };

        if (config.background) {
          options.background = config.background;
        }

        if (config.cross_origin !== null && config.cross_origin !== undefined) {
          options.crossOrigin = config.cross_origin;
        }

        if (config.with_credentials) {
          options.withCredentials = true;
        }

        if (config.request_headers && typeof config.request_headers === 'object') {
          options.requestHeaders = config.request_headers;
        }

        if (config.open_seadragon && typeof config.open_seadragon === 'object') {
          options.openSeadragon = config.open_seadragon;
        }

        if (config.annotations_motivations && config.annotations_motivations.length > 0) {
          options.annotations = { motivations: config.annotations_motivations };
        }

        if (config.ignore_caption_labels && config.ignore_caption_labels.length > 0) {
          options.ignoreCaptionLabels = config.ignore_caption_labels;
        }

        if (config.information_panel_default_tab) {
          options.informationPanel.defaultTab = config.information_panel_default_tab;
        }

        var viewerProps = {
          iiifContent: config.manifesturl,
          options: options,
        };

        if (config.custom_theme && typeof config.custom_theme === 'object') {
          viewerProps.customTheme = config.custom_theme;
        }

        const root = ReactDOM.createRoot(element);
        root.render(React.createElement(Viewer, viewerProps));
      });
    }
  };

})(Drupal, once, drupalSettings);
