<?php

namespace Drupal\gatsby_endpoints\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Gatsby endpoint plugins.
 */
interface GatsbyEndpointInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface {

}
