/**
 * @file
 * Data-attribute-driven behavior for mounting Clover IIIF components.
 *
 * Works in any Twig context without a PHP field formatter. Mount any of the
 * three Clover components by adding a div with the appropriate class and
 * data attributes.
 *
 * ── Viewer ────────────────────────────────────────────────────────────────
 * <div class="clover-twig-viewer"
 *      data-iiif-manifest="https://example.com/manifest.json"
 *      data-canvas-height="500px"
 *      data-canvas-background-color="#1a1d1e"
 *      data-show-title="true"
 *      data-show-iiif-badge="true"
 *      data-show-download="true"
 *      data-information-panel-open="true"
 *      data-content-search-enabled="true"
 *      data-cross-origin="anonymous"
 *      data-height="600">
 * </div>
 *
 * ── Scroll ────────────────────────────────────────────────────────────────
 * <div class="clover-twig-scroll"
 *      data-iiif-manifest="https://example.com/manifest.json"
 *      data-figure-display="image-viewer"
 *      data-figure-aspect-ratio="0.6667"
 *      data-scroll-offset="0">
 * </div>
 *
 * ── Slider ────────────────────────────────────────────────────────────────
 * <div class="clover-twig-slider"
 *      data-iiif-collection="https://example.com/collection.json"
 *      data-credentials="omit"
 *      data-custom-view-all="https://example.com/browse">
 * </div>
 *
 * All data attributes are optional except the manifest/collection URL.
 * Boolean attributes accept "true" or "false" strings.
 */
(function (Drupal, once) {

  'use strict';

  /**
   * Reads a boolean data attribute. Returns defaultValue if not set.
   */
  function dataBool(el, attr, defaultValue) {
    if (!el.dataset[attr]) return defaultValue;
    return el.dataset[attr] !== 'false';
  }

  /**
   * Reads a string data attribute. Returns defaultValue if empty.
   */
  function dataStr(el, attr, defaultValue) {
    return el.dataset[attr] || defaultValue;
  }

  /**
   * Reads a float data attribute. Returns defaultValue if not a valid number.
   */
  function dataFloat(el, attr, defaultValue) {
    var v = parseFloat(el.dataset[attr]);
    return isNaN(v) ? defaultValue : v;
  }

  // ── Viewer ──────────────────────────────────────────────────────────────

  Drupal.behaviors.strawberryfield_clover_twig_viewer = {
    attach: function (context) {
      once('clover-twig-viewer', '.clover-twig-viewer[data-iiif-manifest]', context)
        .forEach(function (element) {
          if (typeof window.CloverViewer === 'undefined' || !window.CloverViewer.Viewer) {
            console.error('Clover IIIF: window.CloverViewer.Viewer not loaded.');
            return;
          }

          const { Viewer, React, ReactDOM } = window.CloverViewer;
          const d = element.dataset;

          var height = parseInt(d.height, 10) || 600;
          element.style.height = height + 'px';

          var options = {
            canvasHeight: dataStr(element, 'canvasHeight', '500px'),
            canvasBackgroundColor: dataStr(element, 'canvasBackgroundColor', '#1a1d1e'),
            showTitle: dataBool(element, 'showTitle', true),
            showIIIFBadge: dataBool(element, 'showIiifBadge', true),
            showDownload: dataBool(element, 'showDownload', true),
            contentSearch: {
              enabled: dataBool(element, 'contentSearchEnabled', true),
            },
            informationPanel: {
              open: dataBool(element, 'informationPanelOpen', true),
              renderToggle: dataBool(element, 'informationPanelRenderToggle', true),
            },
          };

          var crossOrigin = dataStr(element, 'crossOrigin', 'anonymous');
          if (crossOrigin) options.crossOrigin = crossOrigin;

          var props = { iiifContent: d.iiifManifest, options: options };

          ReactDOM.createRoot(element).render(React.createElement(Viewer, props));
        });
    }
  };

  // ── Scroll ──────────────────────────────────────────────────────────────

  Drupal.behaviors.strawberryfield_clover_twig_scroll = {
    attach: function (context) {
      once('clover-twig-scroll', '.clover-twig-scroll[data-iiif-manifest]', context)
        .forEach(function (element) {
          if (typeof window.CloverViewer === 'undefined' || !window.CloverViewer.Scroll) {
            console.error('Clover IIIF: window.CloverViewer.Scroll not loaded.');
            return;
          }

          const { Scroll, React, ReactDOM } = window.CloverViewer;

          var figure = {
            display: dataStr(element, 'figureDisplay', 'image-viewer'),
          };
          var aspectRatio = dataFloat(element, 'figureAspectRatio', 0);
          if (aspectRatio > 0) figure.aspectRatio = aspectRatio;

          var options = { figure: figure };

          var offset = parseInt(element.dataset.scrollOffset, 10) || 0;
          if (offset > 0) options.offset = offset;

          var props = { iiifContent: element.dataset.iiifManifest, options: options };

          ReactDOM.createRoot(element).render(React.createElement(Scroll, props));
        });
    }
  };

  // ── Slider ──────────────────────────────────────────────────────────────

  Drupal.behaviors.strawberryfield_clover_twig_slider = {
    attach: function (context) {
      once('clover-twig-slider', '.clover-twig-slider[data-iiif-collection]', context)
        .forEach(function (element) {
          if (typeof window.CloverViewer === 'undefined' || !window.CloverViewer.Slider) {
            console.error('Clover IIIF: window.CloverViewer.Slider not loaded.');
            return;
          }

          const { Slider, React, ReactDOM } = window.CloverViewer;

          var options = {};
          var credentials = dataStr(element, 'credentials', 'omit');
          if (credentials && credentials !== 'omit') options.credentials = credentials;

          var viewAll = dataStr(element, 'customViewAll', '');
          if (viewAll) options.customViewAll = viewAll;

          var props = { iiifContent: element.dataset.iiifCollection };
          if (Object.keys(options).length > 0) props.options = options;

          ReactDOM.createRoot(element).render(React.createElement(Slider, props));
        });
    }
  };

})(Drupal, once);
