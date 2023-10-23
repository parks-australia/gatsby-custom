<?php

namespace Drupal\gatsby_endpoints\Commands;

use Drush\Commands\DrushCommands;
use Drupal\gatsby_endpoints\GatsbyEndpointManager;
use Drupal\gatsby_endpoints\GatsbyEndpointTrigger;

/**
 * A drush command file.
 *
 * @package Drupal\gatsby_endpoints\Commands
 */
class GatsbyEndpointCommands extends DrushCommands {

  /**
   * Drupal\gatsby_endpoints\GatsbyEndpointManager definition.
   *
   * @var \Drupal\gatsby_endpoints\GatsbyEndpointManager
   */
  protected $gatsbyEndpointManager;

  /**
   * Drupal\gatsby_endpoints\GatsbyEndpointTrigger definition.
   *
   * @var \Drupal\gatsby_endpoints\GatsbyEndpointTrigger
   */
  protected $gatsbyEndpointTrigger;

  /**
   * Constructs a new GatsbyEndpointsCommands object.
   */
  public function __construct(gatsbyEndpointManager $gatsby_endpoint_manager, gatsbyEndpointTrigger $gatsby_endpoint_trigger) {
    parent::__construct();
    $this->gatsbyEndpointManager = $gatsby_endpoint_manager;
    $this->gatsbyEndpointTrigger = $gatsby_endpoint_trigger;
  }

  /**
   * Drush command to trigger builds for Gatsby Endpoints.
   *
   * @command gatsby_endpoints:build
   * @aliases gatsbuild
   * @option endpoint_id An optional Gatsby Endpoint Id to build.
   * @usage gatsby_endpoints:build
   * @usage gatsby_endpoints:build --endpoint_id=test_endpoint
   */
  public function generate($options = ['endpoint_id' => self::REQ]) {
    if ($options['endpoint_id']) {
      $endpoint = $this->gatsbyEndpointManager->getEndpoint($options['endpoint_id']);

      if ($endpoint) {
        $this->gatsbyEndpointTrigger->triggerBuildUrls($endpoint);
      }

    }
    else {
      $endpoints = $this->gatsbyEndpointManager->getEndpoints();
      foreach ($endpoints as $endpoint) {
        if ($endpoint->getBuildTrigger() === 'manual') {
          $this->gatsbyEndpointTrigger->triggerBuildUrls($endpoint);
        }
      }
    }

  }

}
