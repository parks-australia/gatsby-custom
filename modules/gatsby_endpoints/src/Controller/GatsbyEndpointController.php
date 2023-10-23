<?php

namespace Drupal\gatsby_endpoints\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class for serving Gatsby endpoint routes.
 */
class GatsbyEndpointController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\gatsby_endpoints\GatsbyEndpointManager definition.
   *
   * @var \Drupal\gatsby_endpoints\GatsbyEndpointManager
   */
  protected $gatsbyEndpointManager;

  /**
   * Drupal\gatsby_endpoints\GatsbyEndpointGenerator definition.
   *
   * @var \Drupal\gatsby_endpoints\GatsbyEndpointGenerator
   */
  protected $gatsbyEndpointGenerator;

  /**
   * Config Interface for accessing site configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $jsonApiConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $config = $container->get('config.factory');
    $instance->jsonApiConfig = $config->get('jsonapi_extras.settings');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->gatsbyEndpointManager = $container->get('gatsby.gatsby_endpoint_manager');
    $instance->gatsbyEndpointGenerator = $container->get('gatsby.gatsby_endpoint_generator');
    return $instance;
  }

  /**
   * Gatsby Endpoint callback that generates correct JSON output.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JsonResponse with all of the content changes since last fetch.
   */
  public function sync(string $endpoint_id, Request $request) {
    $sync_data = [
      'timestamp' => time(),
    ];

    $base_url = $request->getSchemeAndHttpHost();
    $path_prefix = $this->jsonApiConfig->get('path_prefix');

    $endpoint = $this->gatsbyEndpointManager->getEndpoint($endpoint_id);

    if ($endpoint) {
      $links = $this->gatsbyEndpointGenerator->getEndpointLinks($endpoint);
      foreach ($links as $key => $link) {
        $sync_data['links'][$key]['href'] = $base_url . '/' . $path_prefix . '/' . $link;
      }
    }

    return new JsonResponse($sync_data);
  }

}
