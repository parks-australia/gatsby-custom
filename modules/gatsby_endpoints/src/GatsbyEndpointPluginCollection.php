<?php

namespace Drupal\gatsby_endpoints;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of GatsbyEndpoint plugins.
 */
class GatsbyEndpointPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The Gatsby endpoint ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $endpointId;

  /**
   * Constructs a new GatsbyEndpointPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $endpoint_id
   *   The unique ID of the Gatsby Endpoint entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $endpoint_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->endpointId = $endpoint_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The Gatsby endpoint '{$this->endpointId}' did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      $module = $this->configuration['provider'];
      // Ignore blocks belonging to uninstalled modules, but re-throw valid
      // exceptions when the module is installed and the plugin is
      // misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
