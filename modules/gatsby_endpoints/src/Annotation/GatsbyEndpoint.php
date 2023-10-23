<?php

namespace Drupal\gatsby_endpoints\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Gatsby endpoint annotation object.
 *
 * @see \Drupal\gatsby_endpoints\Plugin\GatsbyEndpointsManager
 * @see plugin_api
 *
 * @Annotation
 */
class GatsbyEndpoint extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
