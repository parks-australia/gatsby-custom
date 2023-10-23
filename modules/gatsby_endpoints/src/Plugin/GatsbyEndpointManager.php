<?php

namespace Drupal\gatsby_endpoints\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Gatsby endpoint plugin manager.
 */
class GatsbyEndpointManager extends DefaultPluginManager {

  /**
   * Constructs a new GatsbyEndpointManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/GatsbyEndpoint',
      $namespaces,
      $module_handler,
      'Drupal\gatsby_endpoints\Plugin\GatsbyEndpointInterface',
      'Drupal\gatsby_endpoints\Annotation\GatsbyEndpoint'
    );

    $this->alterInfo('gatsby_endpoints_endpoint_info');
    $this->setCacheBackend($cache_backend, 'gatsby_endpoints_endpoint_plugins');
  }

}
