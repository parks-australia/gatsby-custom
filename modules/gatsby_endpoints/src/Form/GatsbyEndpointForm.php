<?php

namespace Drupal\gatsby_endpoints\Form;

use Drupal\gatsby_endpoints\Plugin\GatsbyEndpointInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing Gatsby Endpoint entities.
 */
class GatsbyEndpointForm extends EntityForm {

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The entity bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * GatsbyEndpointForm constructor.
   *
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(PluginFormFactoryInterface $plugin_form_manager,
      EntityTypeBundleInfo $entity_type_bundle_info) {

    $this->pluginFormFactory = $plugin_form_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin_form.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Gatsby endpoint."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\gatsby_endpoints\Entity\GatsbyEndpoint::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    // Render the Preview URLs AJAX enabled fieldset.
    $preview_description = $this->t("Enter any Gatsby Live Preview URLs to trigger for this endpoint.");
    $this->addAjaxFieldset($form, $form_state, 'preview', $preview_description);

    // Render the Build URLs AJAX enabled fieldset.
    $build_description = $this->t("Enter any Gatsby Build Hooks or Build URLs to trigger for this endpoint.");
    $this->addAjaxFieldset($form, $form_state, 'build', $build_description);

    $build_entities_description = $this->t("Select which entities should trigger builds/previews for this endpoint.");
    $this->addEntityAjaxFieldset($form, $form_state, 'build', $build_entities_description);

    $form['build_trigger'] = [
      '#type' => 'select',
      '#options' => [
        'incremental' => $this->t("Trigger builds when selected build entities change"),
        'cron' => $this->t("Trigger builds on cron runs"),
        'manual' => $this->t("Trigger builds manually with the built-in drush command"),
      ],
      '#title' => $this->t("Build Trigger"),
      '#default_value' => $entity->getBuildTrigger() ? $entity->getBuildTrigger() : 'incremental',
      '#description' => $this->t('Select how Gatsby build URLs should be
        triggered. 
        This setting has no effect if there are no build URLs entered above.',
        [
          '@gatsby-link' => 'https://gatsbyjs.com',
        ]
      ),
      '#required' => TRUE,
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#max' => 100,
      '#min' => -100,
      '#size' => 3,
      '#default_value' => $entity->getWeight() ? $entity->getWeight() : 0,
      '#description' => $this->t("Set the weight, lighter endpoints will be rendered first."),
      '#required' => TRUE,
    ];

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($entity->getPlugin())
      ->buildConfigurationForm($form['settings'], $subform_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove empty Build and Preview URLs.
    $this->removeEmptyUrls($form_state, 'preview');
    $this->removeEmptyUrls($form_state, 'build');

    parent::submitForm($form, $form_state);

    /** @var \Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface $entity */
    $entity = $this->entity;

    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);

    // Call the plugin submit handler.
    $this->getPluginForm($entity->getPlugin())
      ->submitConfigurationForm($form, $sub_form_state);

    $entity->save();

    $this->messenger()->addStatus($this->t('The Gatsby endpoint configuration has been saved.'));
    $form_state->setRedirectUrl(Url::fromRoute('gatsby_endpoints.gatsby_endpoints_collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginForm(GatsbyEndpointInterface $gatsbyEndpoint) {
    if ($gatsbyEndpoint instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($gatsbyEndpoint, 'configure');
    }
    return $gatsbyEndpoint;
  }

  /**
   * Adds a Form Element to an AJAX Fieldset.
   */
  public function addFormElement(array &$form, FormStateInterface $form_state, $key, $element) {
    $cnt = $form_state->get($key . '_' . $element . '_cnt');
    $form_state->set($key . '_' . $element . '_cnt', $cnt + 1);
    $form_state->setRebuild();
  }

  /**
   * Removes a Form Element from an AJAX Fieldset.
   */
  public function removeFormElement(array &$form, FormStateInterface $form_state, $key, $element) {
    $cnt = $form_state->get($key . '_' . $element . '_cnt');
    if ($cnt > 1) {
      $form_state->set($key . '_' . $element . '_cnt', $cnt - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * Adds a Build Url.
   */
  public function addBuildUrl(array &$form, FormStateInterface $form_state) {
    $this->addFormElement($form, $form_state, 'build', 'url');
  }

  /**
   * Removes a Build Url.
   */
  public function removeBuildUrl(array &$form, FormStateInterface $form_state) {
    $this->removeFormElement($form, $form_state, 'build', 'url');
  }

  /**
   * Ajax callback for Build Urls that returns the correct fieldset.
   */
  public function buildUrlCallback(array &$form, FormStateInterface $form_state) {
    return $form['build_urls'];
  }

  /**
   * Adds a Preview Url.
   */
  public function addPreviewUrl(array &$form, FormStateInterface $form_state) {
    $this->addFormElement($form, $form_state, 'preview', 'url');
  }

  /**
   * Removes a Preview Url.
   */
  public function removePreviewUrl(array &$form, FormStateInterface $form_state) {
    $this->removeFormElement($form, $form_state, 'preview', 'url');
  }

  /**
   * Ajax callback for Preview Urls that returns the correct fieldset.
   */
  public function previewUrlCallback(array &$form, FormStateInterface $form_state) {
    return $form['preview_urls'];
  }

  /**
   * Renders AJAX fieldsets for Build and Preview URLs.
   */
  public function addAjaxFieldset(array &$form, FormStateInterface $form_state, $key, $description) {
    $label = ucfirst($key);
    $url_cnt = $form_state->get($key . '_url_cnt');

    /** @var \Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface $entity */
    $entity = $this->entity;

    // If there is no count yet, then this is the first time rendering.
    if ($url_cnt === NULL) {
      $urls = $entity->getUrls($key);
      $url_cnt = !empty($urls) && !empty($urls[$key . '_url']) ? count($urls[$key . '_url']) : 0;
      if ($url_cnt === 0) {
        $url_cnt = 1;
      }
      $form_state->set($key . '_url_cnt', $url_cnt);
    }

    $form[$key . '_urls'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Gatsby @label URLs", ['@label' => $label]),
      '#prefix' => '<div id="' . $key . '-urls-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form[$key . '_urls']['description'] = [
      '#markup' => $description,
      '#weight' => -100,
    ];

    for ($i = 0; $i < $url_cnt; $i++) {
      $form[$key . '_urls'][$key . '_url'][$i] = [
        '#type' => 'url',
        '#title' => $this->t('Gatsby @label URL #@cnt', [
          '@label' => $label,
          '@cnt' => $i + 1,
        ]),
        '#maxlength' => 255,
        '#default_value' => !empty($urls[$key . '_url'][$i]) ? $urls[$key . '_url'][$i] : "",
        '#required' => FALSE,
      ];
    }
    $form[$key . '_urls']['actions'] = [
      '#type' => 'actions',
    ];
    $form[$key . '_urls']['actions']['add_' . $key . '_url'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Additional @label URL', ['@label' => $label]),
      '#submit' => ['::add' . $label . 'Url'],
      '#ajax' => [
        'callback' => '::' . $key . 'UrlCallback',
        'wrapper' => $key . '-urls-fieldset-wrapper',
      ],
    ];
    if ($url_cnt > 1) {
      $form[$key . '_urls']['actions']['remove_' . $key . '_url'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove @label URL', ['@label' => $label]),
        '#submit' => ['::remove' . $label . 'Url'],
        '#ajax' => [
          'callback' => '::' . $key . 'UrlCallback',
          'wrapper' => $key . '-urls-fieldset-wrapper',
        ],
      ];
    }
  }

  /**
   * Removes empty URLs from preview and build fieldsets.
   */
  public function removeEmptyUrls(FormStateInterface $form_state, $key) {
    $values = $form_state->getValue($key . '_urls');
    $values[$key . '_url'] = array_values(array_filter($values[$key . '_url']));
    $form_state->setValue($key . '_urls', $values);
  }

  /**
   * Adds a Build Entity Fieldset.
   */
  public function addBuildEntityFieldset(array &$form, FormStateInterface $form_state) {
    $this->addFormElement($form, $form_state, 'build', 'entity');
  }

  /**
   * Removes a Build Entity Fieldset Url.
   */
  public function removeBuildEntityFieldset(array &$form, FormStateInterface $form_state) {
    $this->removeFormElement($form, $form_state, 'build', 'entity');
  }

  /**
   * Ajax callback for Build Entity Fieldsets that returns the correct fieldset.
   */
  public function buildEntityCallback(array &$form, FormStateInterface $form_state) {
    return $form['build_entity_types'];
  }

  /**
   * Ajax callback for build entities that returns the correct bundles.
   */
  public function buildEntityBundleCallback(array &$form, FormStateInterface $form_state) {
    // Determine what element triggered this callback.
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_elements = explode('-', $triggering_element['#ajax']['wrapper']);
    $element_id = intval(array_pop($wrapper_elements));

    $form['build_entity_types'][$element_id]['#open'] = TRUE;
    return $form['build_entity_types'][$element_id];
  }

  /**
   * Renders AJAX fieldsets for Entity selection.
   */
  public function addEntityAjaxFieldset(array &$form, FormStateInterface $form_state, $key, $description) {
    $label = ucfirst($key);
    $entity_cnt = $form_state->get($key . '_entity_cnt');

    /** @var \Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface $entity */
    $entity = $this->entity;

    // If there is no count yet, then this is the first time rendering.
    if ($entity_cnt === NULL) {
      $entities = $entity->getEntityTypes($key);
      $entity_cnt = !empty($entities) ? count($entities) - 1 : 0;
      if ($entity_cnt === 0) {
        $entity_cnt = 1;
      }
      $form_state->set($key . '_entity_cnt', $entity_cnt);
    }

    $form[$key . '_entity_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Gatsby @label Entities", ['@label' => $label]),
      '#prefix' => '<div id="' . $key . '-entity-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form[$key . '_entity_types']['description'] = [
      '#markup' => $description,
      '#weight' => -100,
    ];

    for ($i = 0; $i < $entity_cnt; $i++) {
      // Get the default entity type.
      $entity_type = !empty($entities[$i]['entity_type']) ?
        $entities[$i]['entity_type'] : "";

      // Check if there was an entity type set in the form state.
      $form_values = $form_state->getValues();
      if (!empty($form_values[$key . '_entity_types'][$i]['entity_type'])) {
        $entity_type = $form_values[$key . '_entity_types'][$i]['entity_type'];
      }

      $content_entity_types = $this->getContentEntityTypes();

      $form[$key . '_entity_types'][$i] = [
        '#type' => 'details',
        '#title' => $this->t("Gatsby @label Entity #@cnt @entity", [
          '@label' => $label,
          '@cnt' => $i + 1,
          '@entity' => !empty($entity_type) ? "(" . $content_entity_types[$entity_type] . ")" : "",
        ]),
        '#prefix' => '<div id="' . $key . '-entity-fieldset-' . $i . '">',
        '#suffix' => '</div>',
        '#open' => empty($entity_type) ? TRUE : FALSE,
      ];

      $entity_types = ['' => $this->t("-- Select Entity Type --")] + $content_entity_types;

      $form[$key . '_entity_types'][$i]['entity_type'] = [
        '#type' => 'select',
        '#options' => $entity_types,
        '#title' => $this->t("Entity Type"),
        '#default_value' => $entity_type,
      ];

      $form[$key . '_entity_types'][$i]['entity_type']['#ajax'] = [
        'callback' => '::' . $key . 'EntityBundleCallback',
        'wrapper' => $key . '-entity-fieldset-' . $i,
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading bundles...'),
        ],
      ];

      if ($entity_type) {
        $entity_bundles = !empty($entities[$i]['entity_bundles']) ?
          $entities[$i]['entity_bundles'] : [];
        $include_entities = !empty($entities[$i]['include_entities']) ?
          $entities[$i]['include_entities'] : [];

        $form[$key . '_entity_types'][$i]['entity_bundles'] = [
          '#type' => 'checkboxes',
          '#options' => $this->getContentEntityBundles($entity_type),
          '#title' => $this->t("Entity Bundle(s)"),
          '#default_value' => $entity_bundles,
        ];

        if ($entity_type == 'node') {
          $build_published = !empty($entities[$i]['build_published']) ?
            $entities[$i]['build_published'] : FALSE;
          $form[$key . '_entity_types'][$i]['build_published'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Only trigger builds for published content'),
            '#description' => $this->t('Depending on your content workflow, you may only
              want builds to be triggered for published content. By checking this box
              only published content will trigger a build.'),
            '#default_value' => $build_published,
            '#weight' => 3,
          ];
        }

        $included_entities_description = $this->t("Select which entities should not trigger builds/previews but should be
          included in builds/previews. This is commonly used for entities such as media, files, or paragraphs where you don't
          want these items to trigger a new build, but you do want to make sure they are sent to Gatsby if they are
          attached to an entity that triggers a build.");
        $form[$key . '_entity_types'][$i]['include_entities'] = [
          '#type' => 'checkboxes',
          '#options' => $content_entity_types,
          '#title' => $this->t("Include Entities"),
          '#description' => $included_entities_description,
          '#default_value' => $include_entities,
          '#weight' => 4,
        ];
      }
    }
    $form[$key . '_entity_types']['actions'] = [
      '#type' => 'actions',
    ];
    $form[$key . '_entity_types']['actions']['add_' . $key . '_entity'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Additional @label Entity', ['@label' => $label]),
      '#submit' => ['::add' . $label . 'EntityFieldset'],
      '#ajax' => [
        'callback' => '::' . $key . 'EntityCallback',
        'wrapper' => $key . '-entity-fieldset-wrapper',
      ],
    ];
    if ($entity_cnt > 1) {
      $form[$key . '_entity_types']['actions']['remove_' . $key . '_entity'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove @label Entity', ['@label' => $label]),
        '#submit' => ['::remove' . $label . 'EntityFieldset'],
        '#ajax' => [
          'callback' => '::' . $key . 'EntityCallback',
          'wrapper' => $key . '-entity-fieldset-wrapper',
        ],
      ];
    }
  }

  /**
   * Gets a list of all the defined content entities in the system.
   *
   * @return array
   *   An array of content entities definitions.
   */
  private function getContentEntityTypes() {
    $content_entity_types = [];
    $allEntityTypes = $this->entityTypeManager->getDefinitions();

    foreach ($allEntityTypes as $entity_type_id => $entity_type) {
      // Add all content entity types but not the gatsby log entity provided
      // by the gatsby_fastbuilds module (if it exists).
      if ($entity_type instanceof ContentEntityTypeInterface &&
        $entity_type_id !== 'gatsby_log_entity') {

        $content_entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    return $content_entity_types;
  }

  /**
   * Gets a list of all the defined bundles for a content entity type.
   *
   * @return array
   *   An array of bundles for a specific content entity type.
   */
  private function getContentEntityBundles($entity_type) {
    $bundle_definitions = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

    $bundles = [];
    foreach ($bundle_definitions as $bundle => $bundle_definition) {
      $bundles[$bundle] = $bundle_definition['label'];
    }

    return $bundles;
  }

}
