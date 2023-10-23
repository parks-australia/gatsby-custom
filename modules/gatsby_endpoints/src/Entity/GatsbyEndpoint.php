<?php

namespace Drupal\gatsby_endpoints\Entity;

use Drupal\gatsby_endpoints\GatsbyEndpointPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the Gatsby Endpoint entity.
 *
 * @ConfigEntityType(
 *   id = "gatsby_endpoint",
 *   label = @Translation("Gatsby endpoint"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\gatsby_endpoints\GatsbyEndpointListBuilder",
 *     "form" = {
 *       "default" = "Drupal\gatsby_endpoints\Form\GatsbyEndpointForm",
 *       "edit" = "Drupal\gatsby_endpoints\Form\GatsbyEndpointForm",
 *       "delete" = "Drupal\gatsby_endpoints\Form\GatsbyEndpointDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\gatsby_endpoints\GatsbyEndpointHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "gatsby_endpoint",
 *   admin_permission = "manage gatsby endpoints",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/gatsby/endpoint/{gatsby_endpoint}",
 *     "add-form" = "/admin/config/services/gatsby/endpoint/add",
 *     "edit-form" = "/admin/config/services/gatsby/endpoint/{gatsby_endpoint}/edit",
 *     "delete-form" = "/admin/config/services/gatsby/endpoint/{gatsby_endpoint}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "provider",
 *     "plugin",
 *     "settings",
 *     "build_entity_types",
 *     "included_entity_types",
 *     "preview_urls",
 *     "build_urls",
 *     "build_trigger"
 *   },
 * )
 */
class GatsbyEndpoint extends ConfigEntityBase implements GatsbyEndpointInterface, EntityWithPluginCollectionInterface {

  /**
   * The Gatsby Endpoint ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin collection that holds the endpoint plugin for this entity.
   *
   * @var \Drupal\gatsby_endpoints\GatsbyEndpointPluginCollection
   */
  protected $pluginCollection;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The Gatsby endpoint label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Gatsby endpoint build entity types.
   *
   * @var array
   */
  protected $build_entity_types;


  /**
   * The Gatsby endpoint preview Urls.
   *
   * @var array
   */
  protected $preview_urls;

  /**
   * The Gatsby endpoint build Urls.
   *
   * @var array
   */
  protected $build_urls;

  /**
   * The Gatsby endpoint build trigger.
   *
   * @var string
   */
  protected $build_trigger;

  /**
   * The weight of the endpoint.
   *
   * @var string
   */
  protected $weight;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes($key) {
    if ($key === 'build') {
      return $this->build_entity_types;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildEntityTypes() {
    return $this->build_entity_types;
  }

  /**
   * {@inheritDoc}
   */
  public function getBuildEntityType($entity_type) {
    foreach ($this->build_entity_types as $build_type) {
      if (!empty($build_type['entity_type']) && $build_type['entity_type'] === $entity_type) {
        return $build_type;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildEntityTypes($build_entity_types) {
    $this->build_entity_types = $build_entity_types;
  }

  /**
   * {@inheritDoc}
   */
  public function getIncludedEntityTypes($build_type) {
    $included_types = [];

    if (!empty($build_type['include_entities'])) {
      foreach ($build_type['include_entities'] as $type => $id) {
        if ($type === $id) {
          $included_types[] = $type;
        }
      }
    }

    return $included_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls($key) {
    if ($key === 'preview') {
      return $this->preview_urls;
    }
    elseif ($key === 'build') {
      return $this->build_urls;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewUrls() {
    return $this->preview_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewUrls($preview_urls) {
    $this->preview_urls = $preview_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildUrls() {
    return $this->build_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildUrls($build_urls) {
    $this->build_urls = $build_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildTrigger() {
    return $this->build_trigger;
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildTrigger($build_trigger) {
    $this->build_trigger = $build_trigger;
  }

  /**
   * Encapsulates creation of the Gatsby endpoint's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The Gatsby endpoint's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new GatsbyEndpointPluginCollection(
        \Drupal::service('plugin.manager.gatsby_endpoint'), $this->plugin, $this->get('settings'), $this->id()
      );
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

}
