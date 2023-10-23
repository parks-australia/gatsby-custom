<?php

namespace Drupal\gatsby_endpoints\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for route titles.
 */
class GatsbyEndpointTitleController extends ControllerBase {

  /**
   * Get the title of Gatsby Endpoint from current route.
   *
   * @return string
   *   The name of the Gatsby endpoint.
   */
  public function gatsbyEndpointTitle() {
    $path = \Drupal::request()->getpathInfo();
    $arg = explode('/', $path);
    $config = \Drupal::config('gatsby_endpoints.gatsby_endpoint.' . $arg[4]);
    return $this->t('@endpoint endpoint', ['@endpoint' => $config->get('label')]);
  }

}
