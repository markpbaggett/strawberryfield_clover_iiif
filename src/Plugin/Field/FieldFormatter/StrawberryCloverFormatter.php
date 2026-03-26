<?php

namespace Drupal\strawberryfield_clover\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\format_strawberryfield\EmbargoResolverInterface;
use Drupal\format_strawberryfield\Plugin\Field\FieldFormatter\StrawberryBaseFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clover IIIF React Viewer Strawberry Field formatter.
 *
 * @FieldFormatter(
 *   id = "strawberry_clover_formatter",
 *   label = @Translation("Strawberry Field Clover IIIF Viewer"),
 *   class = "\Drupal\strawberryfield_clover\Plugin\Field\FieldFormatter\StrawberryCloverFormatter",
 *   field_types = {
 *     "strawberryfield_field"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class StrawberryCloverFormatter extends StrawberryBaseFormatter implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    EmbargoResolverInterface $embargo_resolver
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $config_factory,
      $embargo_resolver,
      $current_user
    );
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('format_strawberryfield.embargo_resolver')
    );
  }

  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    unset($settings['hide_on_embargo']);
    return $settings + [
      // Manifest source.
      'mediasource' => [
        'metadataexposeentity' => 'metadataexposeentity',
      ],
      'main_mediasource' => 'metadataexposeentity',
      'metadataexposeentity_source' => NULL,
      'manifesturl_json_key_source' => 'iiifmanifest',
      'manifestnodelist_json_key_source' => 'isrelatedto',
      'max_width' => 0,
      'max_height' => 600,
      'canvas_height' => '500px',
      'background' => '',
      'canvas_background_color' => '#1a1d1e',
      'show_title' => TRUE,
      'show_iiif_badge' => TRUE,
      'show_download' => TRUE,
      'information_panel_open' => TRUE,
      'information_panel_render_about' => TRUE,
      'information_panel_render_annotation' => TRUE,
      'information_panel_render_supplementing' => TRUE,
      'information_panel_render_content_search' => TRUE,
      'information_panel_render_toggle' => TRUE,
      'information_panel_default_tab' => '',
      'content_search_enabled' => TRUE,
      'cross_origin' => 'anonymous',
      'with_credentials' => FALSE,
      'request_headers' => '',
      'open_seadragon' => '',
      'annotations_motivations' => '',
      'ignore_caption_labels' => '',
      'custom_theme' => '',
      'hide_on_embargo' => FALSE,
    ];
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $entity = NULL;
    if ($this->getSetting('metadataexposeentity_source')) {
      $entity = $this->entityTypeManager->getStorage('metadataexpose_entity')
        ->load($this->getSetting('metadataexposeentity_source'));
    }

    $options_for_mainsource = is_array($this->getSetting('mediasource')) && !empty($this->getSetting('mediasource'))
      ? $this->getSetting('mediasource')
      : self::defaultSettings()['mediasource'];

    if (($triggering_element = $form_state->getTriggeringElement()) && isset($triggering_element['#ajax']['callback'])) {
      if ($triggering_element['#ajax']['callback'][0] == get_class($this)) {
        $parents = array_slice($triggering_element['#parents'], 0, -1);
        $options_for_mainsource = $form_state->getValue($parents);
      }
    }

    $all_source_options = [
      'metadataexposeentity' => $this->t('A IIIF Manifest generated by a Metadata Display template'),
      'manifesturl' => $this->t('Strawberryfield JSON Key with one or more Manifest URLs'),
      'manifestnodelist' => $this->t('Strawberryfield JSON Key with one or more Node IDs or UUIDs'),
    ];

    $options_for_mainsource = array_filter($options_for_mainsource);
    $options_for_mainsource = array_intersect_key($options_for_mainsource, $all_source_options);

    $ajax = [
      'callback' => [get_class($this), 'ajaxCallbackMainSource'],
      'wrapper' => 'clover-main-mediasource-ajax-container',
    ];

    $default_main = ($this->getSetting('main_mediasource') && array_key_exists($this->getSetting('main_mediasource'), $options_for_mainsource))
      ? $this->getSetting('main_mediasource')
      : reset($options_for_mainsource);

    $settings_form = [

      // ----------------------------------------------------------------
      // Manifest source
      // ----------------------------------------------------------------
      'mediasource' => [
        '#type' => 'checkboxes',
        '#title' => $this->t('Source for your IIIF Manifest URLs.'),
        '#options' => $all_source_options,
        '#default_value' => $this->getSetting('mediasource') ?? [],
        '#required' => TRUE,
        '#attributes' => ['data-clover-formatter-selector' => 'mediasource'],
        '#ajax' => $ajax,
      ],
      'main_mediasource' => [
        '#type' => 'select',
        '#title' => $this->t('Select which Source will be handled as the primary one.'),
        '#options' => $options_for_mainsource,
        '#default_value' => $default_main,
        '#required' => FALSE,
        '#prefix' => '<div id="clover-main-mediasource-ajax-container">',
        '#suffix' => '</div>',
      ],
      'metadataexposeentity_source' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'metadataexpose_entity',
        '#title' => $this->t('Select which Exposed Metadata Endpoint will generate the Manifests'),
        '#description' => $this->t('Used for Metadata Exposed Entities and Node List sources.'),
        '#selection_handler' => 'default',
        '#validate_reference' => TRUE,
        '#default_value' => $entity,
        '#states' => [
          ['visible' => [':input[data-clover-formatter-selector="mediasource"][value="metadataexposeentity"]' => ['checked' => TRUE]]],
          ['visible' => [':input[data-clover-formatter-selector="mediasource"][value="manifestnodelist"]' => ['checked' => TRUE]]],
        ],
      ],
      'manifesturl_json_key_source' => [
        '#type' => 'textfield',
        '#title' => $this->t('JSON Key from where to fetch one or more IIIF Manifest URLs.'),
        '#default_value' => $this->getSetting('manifesturl_json_key_source'),
        '#states' => [
          'visible' => [':input[data-clover-formatter-selector="mediasource"][value="manifesturl"]' => ['checked' => TRUE]],
        ],
      ],
      'manifestnodelist_json_key_source' => [
        '#type' => 'textfield',
        '#title' => $this->t('JSON Key with one or more Node IDs or UUIDs.'),
        '#default_value' => $this->getSetting('manifestnodelist_json_key_source'),
        '#states' => [
          'visible' => [':input[data-clover-formatter-selector="mediasource"][value="manifestnodelist"]' => ['checked' => TRUE]],
        ],
      ],

      'max_width' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum width'),
        '#description' => $this->t('Use 0 to force 100% width.'),
        '#default_value' => $this->getSetting('max_width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
        '#required' => TRUE,
      ],
      'max_height' => [
        '#type' => 'number',
        '#title' => $this->t('Viewer container height'),
        '#default_value' => $this->getSetting('max_height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 100,
        '#required' => TRUE,
      ],
      'canvas_height' => [
        '#type' => 'textfield',
        '#title' => $this->t('Canvas height'),
        '#description' => $this->t('CSS value for the media canvas area, e.g. <em>500px</em> or <em>60vh</em>. Maps to Clover <code>options.canvasHeight</code>.'),
        '#default_value' => $this->getSetting('canvas_height'),
        '#size' => 10,
        '#required' => TRUE,
      ],

      'background' => [
        '#type' => 'textfield',
        '#title' => $this->t('Viewer background'),
        '#description' => $this->t('CSS value for the outer viewer background, e.g. <em>#ffffff</em> or <em>transparent</em>. Maps to <code>options.background</code>. Leave empty for default.'),
        '#default_value' => $this->getSetting('background'),
        '#size' => 20,
      ],
      'canvas_background_color' => [
        '#type' => 'textfield',
        '#title' => $this->t('Canvas background color'),
        '#description' => $this->t('Hex color for the canvas area, e.g. <em>#1a1d1e</em>. Maps to <code>options.canvasBackgroundColor</code>.'),
        '#default_value' => $this->getSetting('canvas_background_color'),
        '#size' => 10,
      ],
      'show_title' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show manifest title (<code>options.showTitle</code>)'),
        '#default_value' => $this->getSetting('show_title'),
      ],
      'show_iiif_badge' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show IIIF badge (<code>options.showIIIFBadge</code>)'),
        '#default_value' => $this->getSetting('show_iiif_badge'),
      ],
      'show_download' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show download button (<code>options.showDownload</code>)'),
        '#default_value' => $this->getSetting('show_download'),
      ],

      'information_panel_open' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Information panel open by default (<code>options.informationPanel.open</code>)'),
        '#default_value' => $this->getSetting('information_panel_open'),
      ],
      'information_panel_render_toggle' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show information panel toggle button (<code>options.informationPanel.renderToggle</code>)'),
        '#default_value' => $this->getSetting('information_panel_render_toggle'),
      ],
      'information_panel_render_about' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Render About tab (<code>options.informationPanel.renderAbout</code>)'),
        '#default_value' => $this->getSetting('information_panel_render_about'),
      ],
      'information_panel_render_annotation' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Render Annotation tab (<code>options.informationPanel.renderAnnotation</code>)'),
        '#default_value' => $this->getSetting('information_panel_render_annotation'),
      ],
      'information_panel_render_supplementing' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Render Supplementing resources tab (<code>options.informationPanel.renderSupplementing</code>)'),
        '#default_value' => $this->getSetting('information_panel_render_supplementing'),
      ],
      'information_panel_render_content_search' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Render Content Search tab (<code>options.informationPanel.renderContentSearch</code>)'),
        '#default_value' => $this->getSetting('information_panel_render_content_search'),
      ],
      'information_panel_default_tab' => [
        '#type' => 'select',
        '#title' => $this->t('Default active tab (<code>options.informationPanel.defaultTab</code>)'),
        '#options' => [
          '' => $this->t('— Clover default —'),
          'about' => $this->t('About'),
          'annotation' => $this->t('Annotation'),
          'supplementing' => $this->t('Supplementing'),
          'search' => $this->t('Search'),
        ],
        '#default_value' => $this->getSetting('information_panel_default_tab'),
      ],

      'content_search_enabled' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable IIIF Content Search (<code>options.contentSearch.enabled</code>)'),
        '#default_value' => $this->getSetting('content_search_enabled'),
      ],

      'cross_origin' => [
        '#type' => 'select',
        '#title' => $this->t('Cross-origin policy (<code>options.crossOrigin</code>)'),
        '#options' => [
          'anonymous' => $this->t('anonymous'),
          'use-credentials' => $this->t('use-credentials'),
          '' => $this->t('undefined (no CORS header)'),
        ],
        '#default_value' => $this->getSetting('cross_origin'),
      ],
      'with_credentials' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Include credentials in requests (<code>options.withCredentials</code>)'),
        '#default_value' => $this->getSetting('with_credentials'),
      ],
      'request_headers' => [
        '#type' => 'textarea',
        '#title' => $this->t('Custom request headers (<code>options.requestHeaders</code>)'),
        '#description' => $this->t('JSON object of HTTP headers to include in manifest/resource requests, e.g. <em>{"Authorization": "Bearer token"}</em>. Leave empty for defaults.'),
        '#default_value' => $this->getSetting('request_headers'),
        '#rows' => 3,
        '#element_validate' => [[$this, 'validateJSON']],
      ],

      'open_seadragon' => [
        '#type' => 'textarea',
        '#title' => $this->t('OpenSeadragon options (<code>options.openSeadragon</code>)'),
        '#description' => $this->t('JSON object of <a href="https://openseadragon.github.io/docs/OpenSeadragon.html#Options">OpenSeadragon configuration overrides</a>. Leave empty for defaults.'),
        '#default_value' => $this->getSetting('open_seadragon'),
        '#rows' => 4,
        '#element_validate' => [[$this, 'validateJSON']],
      ],
      'annotations_motivations' => [
        '#type' => 'textfield',
        '#title' => $this->t('Annotation motivations filter (<code>options.annotations.motivations</code>)'),
        '#description' => $this->t('Comma-separated list of IIIF motivation values to display, e.g. <em>painting,supplementing</em>. Leave empty to show all.'),
        '#default_value' => $this->getSetting('annotations_motivations'),
      ],
      'ignore_caption_labels' => [
        '#type' => 'textfield',
        '#title' => $this->t('Ignore caption labels (<code>options.ignoreCaptionLabels</code>)'),
        '#description' => $this->t('Comma-separated list of caption label strings to exclude from display.'),
        '#default_value' => $this->getSetting('ignore_caption_labels'),
      ],
      'custom_theme' => [
        '#type' => 'textarea',
        '#title' => $this->t('Custom theme (<code>customTheme</code>)'),
        '#description' => $this->t('JSON object of CSS custom property overrides for Clover\'s design tokens. See <a href="https://samvera-labs.github.io/clover-iiif/docs/viewer#custom-theme">Clover theme docs</a>.'),
        '#default_value' => $this->getSetting('custom_theme'),
        '#rows' => 4,
        '#element_validate' => [[$this, 'validateJSON']],
      ],

      'hide_on_embargo' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide the viewer in the presence of an embargo.'),
        '#description' => $this->t('If unchecked, embargo handling is delegated to the IIIF Manifest.'),
        '#default_value' => $this->getSetting('hide_on_embargo') ?? FALSE,
      ],

    ] + parent::settingsForm($form, $form_state);

    if (empty($options_for_mainsource)) {
      $settings_form['main_mediasource']['#empty_option'] = $this->t('- No Source selected. Please check one above. -');
    }

    return $settings_form;
  }

  /**
   * Ajax callback for mediasource checkboxes.
   */
  public static function ajaxCallbackMainSource(array $form, FormStateInterface $form_state) {
    $form_parents = $form_state->getTriggeringElement()['#array_parents'];
    $form_parents = array_slice($form_parents, 0, -2);
    $form_parents[] = 'main_mediasource';
    return NestedArray::getValue($form, $form_parents);
  }

  public function settingsSummary() {
    $summary[] = $this->t('Displays IIIF content using the Clover IIIF React viewer.');

    $main_mediasource = $this->getSetting('main_mediasource') ?: NULL;

    if ($this->getSetting('mediasource') && is_array($this->getSetting('mediasource'))) {
      foreach ($this->getSetting('mediasource') as $source => $enabled) {
        $on = (string) $enabled;
        if ($on === 'metadataexposeentity') {
          $label = '(none set)';
          if ($this->getSetting('metadataexposeentity_source')) {
            $entity = $this->entityTypeManager->getStorage('metadataexpose_entity')
              ->load($this->getSetting('metadataexposeentity_source'));
            $label = $entity ? $entity->label() : $this->getSetting('metadataexposeentity_source') . ' (missing)';
          }
          $summary[] = $this->t('Manifest from Endpoint: %label%primary', [
            '%label' => $label,
            '%primary' => ($main_mediasource === $on) ? ' (PRIMARY)' : '',
          ]);
        }
        if ($on === 'manifesturl') {
          $summary[] = $this->t('Manifest URL from JSON key: %key%primary', [
            '%key' => $this->getSetting('manifesturl_json_key_source'),
            '%primary' => ($main_mediasource === $on) ? ' (PRIMARY)' : '',
          ]);
        }
        if ($on === 'manifestnodelist') {
          $summary[] = $this->t('Node list from JSON key: %key%primary', [
            '%key' => $this->getSetting('manifestnodelist_json_key_source'),
            '%primary' => ($main_mediasource === $on) ? ' (PRIMARY)' : '',
          ]);
        }
      }
    }

    $max_width = (int) $this->getSetting('max_width');
    $summary[] = $this->t('Size: %width x %height | Canvas: %canvas', [
      '%width' => $max_width === 0 ? '100%' : $max_width . 'px',
      '%height' => $this->getSetting('max_height') . 'px',
      '%canvas' => $this->getSetting('canvas_height'),
    ]);

    $flags = [];
    if (!$this->getSetting('show_title')) $flags[] = 'no title';
    if (!$this->getSetting('show_iiif_badge')) $flags[] = 'no badge';
    if (!$this->getSetting('show_download')) $flags[] = 'no download';
    if (!$this->getSetting('information_panel_open')) $flags[] = 'panel closed';
    if (!$this->getSetting('content_search_enabled')) $flags[] = 'search off';
    if (!empty($flags)) {
      $summary[] = $this->t('Options: @flags', ['@flags' => implode(', ', $flags)]);
    }

    return array_merge($summary, parent::settingsSummary());
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $max_width = $this->getSetting('max_width');
    $max_width_css = empty($max_width) || $max_width == 0 ? '100%' : $max_width . 'px';
    $max_height = $this->getSetting('max_height');
    $mediasource = is_array($this->getSetting('mediasource')) ? $this->getSetting('mediasource') : [];
    $main_mediasource = $this->getSetting('main_mediasource');
    $hide_on_embargo = $this->getSetting('hide_on_embargo') ?? FALSE;

    $open_seadragon = $this->parseJsonSetting('open_seadragon');
    $request_headers = $this->parseJsonSetting('request_headers');
    $custom_theme = $this->parseJsonSetting('custom_theme');

    $annotations_motivations = array_values(array_filter(
      array_map('trim', explode(',', $this->getSetting('annotations_motivations') ?? ''))
    ));
    $ignore_caption_labels = array_values(array_filter(
      array_map('trim', explode(',', $this->getSetting('ignore_caption_labels') ?? ''))
    ));

    $cross_origin = $this->getSetting('cross_origin');

    $embargo_context = [];
    $embargo_tags = [];
    $embargo_info = [];
    $embargoed = FALSE;

    $nodeuuid = $items->getEntity()->uuid();

    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getMainPropertyName();
      $value = $item->{$main_property};
      if (empty($value)) {
        continue;
      }

      $jsondata = json_decode($item->value, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        return ['#markup' => $this->t('ERROR: could not decode Strawberry field JSON.')];
      }

      if ($hide_on_embargo) {
        $embargo_info = $this->embargoResolver->embargoInfo($item->getEntity(), $jsondata);
        if (is_array($embargo_info)) {
          $embargoed = $embargo_info[0];
          $embargo_tags[] = 'format_strawberryfield:all_embargo';
          if ($embargo_info[1]) {
            $embargo_tags[] = 'format_strawberryfield:embargo:' . $embargo_info[1];
          }
          if ($embargo_info[2] || ($embargo_info[3] == FALSE)) {
            $embargo_context[] = 'ip';
          }
        }
      }

      if (!$embargoed || ($embargoed && !$hide_on_embargo)) {
        $manifests = [];
        foreach ($mediasource as $iiifsource) {
          switch ((string) $iiifsource) {
            case 'metadataexposeentity':
              $manifests['metadataexposeentity'] = $this->processManifestforMetadataExposeEntity($jsondata, $item);
              break;
            case 'manifesturl':
              $manifests['manifesturl'] = $this->processManifestforURL($jsondata, $item);
              break;
            case 'manifestnodelist':
              $manifests['manifestnodelist'] = $this->processManifestforNodeList($jsondata, $item);
              break;
          }
        }

        if (isset($manifests[$main_mediasource]) && !empty($manifests[$main_mediasource])) {
          $main_manifesturl = array_shift($manifests[$main_mediasource]);
        }
        else {
          $all_manifesturl = array_reduce($manifests, 'array_merge', []);
          $main_manifesturl = array_shift($all_manifesturl);
        }

        if (!empty($main_manifesturl)) {
          $htmlid = 'iiif-' . $item->getName() . '-' . $nodeuuid . '-' . $delta . '-clover';

          $elements[$delta]['media'] = [
            '#type' => 'container',
            '#default_value' => $htmlid,
            '#attributes' => [
              'id' => $htmlid,
              'class' => ['strawberry-clover-item', 'field-iiif'],
              'style' => "width:{$max_width_css}; height:{$max_height}px",
              'data-iiif-infojson' => '',
            ],
          ];

          $clover_settings = [
            'nodeuuid' => $nodeuuid,
            'manifesturl' => $main_manifesturl,
            'width' => $max_width_css,
            'height' => $max_height,
            'canvas_height' => $this->getSetting('canvas_height'),
            'canvas_background_color' => $this->getSetting('canvas_background_color'),
            'background' => $this->getSetting('background'),
            'show_title' => (bool) $this->getSetting('show_title'),
            'show_iiif_badge' => (bool) $this->getSetting('show_iiif_badge'),
            'show_download' => (bool) $this->getSetting('show_download'),
            'cross_origin' => $cross_origin ?: NULL,
            'with_credentials' => (bool) $this->getSetting('with_credentials'),
            'request_headers' => $request_headers,
            'open_seadragon' => $open_seadragon,
            'annotations_motivations' => $annotations_motivations,
            'ignore_caption_labels' => $ignore_caption_labels,
            'content_search_enabled' => (bool) $this->getSetting('content_search_enabled'),
            'information_panel_open' => (bool) $this->getSetting('information_panel_open'),
            'information_panel_render_about' => (bool) $this->getSetting('information_panel_render_about'),
            'information_panel_render_annotation' => (bool) $this->getSetting('information_panel_render_annotation'),
            'information_panel_render_supplementing' => (bool) $this->getSetting('information_panel_render_supplementing'),
            'information_panel_render_content_search' => (bool) $this->getSetting('information_panel_render_content_search'),
            'information_panel_render_toggle' => (bool) $this->getSetting('information_panel_render_toggle'),
            'information_panel_default_tab' => $this->getSetting('information_panel_default_tab'),
            'custom_theme' => $custom_theme,
          ];

          $elements[$delta]['media']['#attached']['drupalSettings']['format_strawberryfield']['clover'][$htmlid] = $clover_settings;
          $elements[$delta]['#attached']['library'][] = 'strawberryfield_clover/clover_strawberry';
        }
      }

      if (empty($elements[$delta])) {
        $elements[$delta] = [
          '#markup' => '<i class="d-none field-iiif-no-viewer"></i>',
          '#prefix' => '<span>',
          '#suffix' => '</span>',
        ];
      }

      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        unset($item->_attributes);
      }

      if (isset($elements[$delta]['#attributes']) && empty($elements[$delta]['#attributes'])) {
        unset($elements[$delta]['#attributes']);
      }
    }

    $elements['#cache'] = [
      'context' => Cache::mergeContexts(
        $items->getEntity()->getCacheContexts(),
        ['user.permissions', 'user.roles'],
        $embargo_context
      ),
      'tags' => Cache::mergeTags(
        $items->getEntity()->getCacheTags(),
        $embargo_tags,
        ['config:format_strawberryfield.embargo_settings']
      ),
    ];

    if (isset($embargo_info[3]) && $embargo_info[3] === FALSE) {
      $elements['#cache']['max-age'] = 0;
    }

    return $elements;
  }

  /**
   * Decodes a JSON textarea setting, returning the decoded value or NULL.
   */
  protected function parseJsonSetting(string $key) {
    $raw = trim($this->getSetting($key) ?? '');
    if (empty($raw)) {
      return NULL;
    }
    $decoded = json_decode($raw, TRUE);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : NULL;
  }

  /**
   * Generates a Manifest URL from a Metadata Expose Entity for the current node.
   */
  public function processManifestforMetadataExposeEntity(array $jsondata, FieldItemInterface $item) {
    $manifests = [];
    $nodeuuid = $item->getEntity()->uuid();
    if ($this->getSetting('metadataexposeentity_source')) {
      $entity = $this->entityTypeManager->getStorage('metadataexpose_entity')
        ->load($this->getSetting('metadataexposeentity_source'));
      if ($entity) {
        $manifests[] = $entity->getUrlForItemFromNodeUUID($nodeuuid, TRUE);
      }
    }
    return $manifests;
  }

  /**
   * Fetches Manifest URLs from a JSON Key.
   */
  public function processManifestforURL(array $jsondata, FieldItemInterface $item) {
    $manifests = [];
    $jsonkey = $this->getSetting('manifesturl_json_key_source');
    if ($jsonkey && isset($jsondata[$jsonkey])) {
      $urls = is_array($jsondata[$jsonkey]) ? $jsondata[$jsonkey] : [$jsondata[$jsonkey]];
      foreach ($urls as $url) {
        if (is_string($url) && UrlHelper::isValid($url, TRUE)) {
          $manifests[] = $url;
        }
      }
    }
    return $manifests;
  }

  /**
   * Generates Manifest URLs from a JSON Key containing Node IDs or UUIDs.
   */
  public function processManifestforNodeList(array $jsondata, FieldItemInterface $item) {
    $manifests = [];
    $jsonkey = $this->getSetting('manifestnodelist_json_key_source');
    if (!$jsonkey || !$this->getSetting('metadataexposeentity_source')) {
      return $manifests;
    }
    $entity = $this->entityTypeManager->getStorage('metadataexpose_entity')
      ->load($this->getSetting('metadataexposeentity_source'));
    if (!$entity || !isset($jsondata[$jsonkey])) {
      return $manifests;
    }
    $nodelist = is_array($jsondata[$jsonkey]) ? $jsondata[$jsonkey] : [$jsondata[$jsonkey]];
    $cleannodelist = array_filter($nodelist, 'is_integer');
    $access_manager = \Drupal::service('access_manager');
    foreach ($this->entityTypeManager->getStorage('node')->loadMultiple($cleannodelist) as $node) {
      $has_access = $access_manager->checkNamedRoute(
        'format_strawberryfield.metadatadisplay_caster',
        [
          'node' => $node->uuid(),
          'metadataexposeconfig_entity' => $entity->id(),
          'format' => 'manifest.jsonld',
        ],
        $this->currentUser
      );
      if ($has_access) {
        $manifests[] = $entity->getUrlForItemFromNodeUUID($node->uuid(), TRUE);
      }
    }
    return $manifests;
  }

}
