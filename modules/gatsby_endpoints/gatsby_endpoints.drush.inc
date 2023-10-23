<?php

/**
 * @file
 * Contains gatsby_endpoints drush 8 commands.
 */

/**
 * Implements hook_drush_command().
 */
function gatsby_endpoints_drush_command() {

  $commands['gatsby-endpoints-build'] = [
    'description' => 'Trigger Gatsby Endpoint builds.',
    'aliases' => ['gatsbybuild'],
    'options' => [
      'endpoint_id' => 'An optional Gatsby Endpoint Id to limit build trigger.',
    ],
    'examples' => [
      'drush gatsbybuild' => 'Trigger builds for all Gatsby Endpoints.',
      'drush gatsbybuild --endpoint_id=test_endpoint' => 'Trigger builds for test_endpoint Gatsby Endpoint',
    ],
  ];

  return $commands;
}

/**
 * Trigger builds for Gatsby Endpoints.
 */
function drush_gatsby_endpoints_build() {
  $endpoint_id = drush_get_option('endpoint_id', FALSE);
  $gatsbyEndpointManager = \Drupal::service('gatsby.gatsby_endpoint_manager');
  $gatsbyEndpointTrigger = \Drupal::service('gatsby.gatsby_endpoint_trigger');

  if ($endpoint_id) {
    $endpoint = $gatsbyEndpointManager->getEndpoint($endpoint_id);

    if ($endpoint) {
      $gatsbyEndpointTrigger->triggerBuildUrls($endpoint);
    }

  }
  else {
    $endpoints = $gatsbyEndpointManager->getEndpoints();
    foreach ($endpoints as $endpoint) {
      if ($endpoint->getBuildTrigger() === 'manual') {
        $gatsbyEndpointTrigger->triggerBuildUrls($endpoint);
      }
    }
  }
}
