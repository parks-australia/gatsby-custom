<?php

namespace Drupal\gatsby_endpoints\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the block instance add form.
 */
class GatsbyEndpointAddController extends ControllerBase {

  /**
   * Add the Gatsby ednpoint form.
   *
   * @param string $plugin_id
   *   The plugin id of the Gatsby endpoint.
   *
   * @return array
   *   The form to add and configure a Gatsby Endpoint entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function gatsbyEndpointAddConfigureForm($plugin_id) {
    // Create a Gatsby endpoint entity.
    $entity = $this->entityTypeManager()
      ->getStorage('gatsby_endpoint')
      ->create(['plugin' => $plugin_id]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
