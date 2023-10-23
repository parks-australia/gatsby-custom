<?php

namespace Drupal\gatsby_endpoints;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface;
use Drupal\gatsby_instantpreview\GatsbyInstantPreview;

/**
 * Class GatsbyEndpointTrigger.
 *
 * Triggers Gatsby previews and incremental builds.
 */
class GatsbyEndpointTrigger {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Drupal\Core\Entity\EntityRepository definition.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  private $entityRepository;

  /**
   * Drupal\gatsby_instantpreview\GatsbyInstantPreview definition.
   *
   * @var \Drupal\gatsby_instantpreview\GatsbyInstantPreview
   */
  private $gatsbyInstantPreview;

  /**
   * Constructs a new GatsbyPreview object.
   */
  public function __construct(ClientInterface $http_client,
      EntityTypeManagerInterface $entity_type_manager,
      LoggerChannelFactoryInterface $logger,
      EntityRepository $entity_repository,
      GatsbyInstantPreview $gatsby_instant_preview) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger->get('gatsby');
    $this->entityRepository = $entity_repository;
    $this->gatsbyInstantPreview = $gatsby_instant_preview;
  }

  /**
   * Prepares Gatsby Data to send to the preview and build servers.
   *
   * By preparing the data in a separate step we prevent multiple requests from
   * being sent to the preview or incremental builds servers if mulutiple
   * Drupal entities are update/inserted/deleted in a single request.
   */
  public function gatsbyPrepareData(GatsbyEndpointInterface $endpoint,
    ContentEntityInterface $entity = NULL,
    string $action = 'update'
  ) {

    $json = $this->gatsbyInstantPreview->getJson($entity);
    if (!$json) {
      return;
    }
    $json['id'] = $entity->uuid();
    $json['action'] = $action;
    $build_type = $endpoint->getBuildEntityType($entity->getEntityTypeId());

    // If there is a secret key we add it to the JSON.
    $secret = $this->getSecretKey($endpoint);
    if ($secret) {
      $json['secret'] = $secret;
    }

    // Build the entity relationships to send along with the data.
    if (!empty($json['data']['relationships'])) {
      // Generate JSON for all related entities to send to Gatsby.
      $entity_data = [];
      $included_types = $endpoint->getIncludedEntityTypes($build_type);
      $this->gatsbyInstantPreview->buildRelationshipJson($json['data']['relationships'], $entity_data, $included_types);

      if (!empty($entity_data)) {
        // Remove the uuid keys from the array.
        $entity_data = array_values($entity_data);

        $original_data = $json['data'];
        $entity_data[] = $original_data;
        $json['data'] = $entity_data;
      }
    }

    $preview_path = "/__refresh";
    $preview_urls = $endpoint->getPreviewUrls();
    if (!empty($preview_urls) && !empty($preview_urls['preview_url'])) {
      foreach ($preview_urls['preview_url'] as $preview_url) {
        $preview_json = $this->gatsbyInstantPreview->bundleData('preview', $preview_url, $json);
        $this->gatsbyInstantPreview->updateData('preview', $preview_url, $preview_json, $preview_path);
      }
    }

    // Only send build data for incremental builds.
    if ($endpoint->getBuildTrigger() !== 'incremental') {
      return;
    }

    // Verify build URLs are set.
    $build_urls = $endpoint->getBuildUrls();
    if (empty($build_urls) || empty($build_urls['build_url'])) {
      return;
    }

    // Don't build if the published checkbox is set and the entity is
    // a content entity that is not published.
    if (!empty($build_type['build_published']) && $build_type['build_published']) {
      if (($entity instanceof NodeInterface) && !$entity->isPublished()) {
        return;
      }
    }

    foreach ($build_urls['build_url'] as $build_url) {
      $build_json = $this->gatsbyInstantPreview->bundleData('incrementalbuild', $build_url, $json);
      $this->gatsbyInstantPreview->updateData('incrementalbuild', $build_url, $build_json);
    }
  }

  /**
   * Triggers the refreshing of Gatsby preview and incremental builds.
   */
  public function gatsbyPrepareDelete(GatsbyEndpointInterface $endpoint,
    ContentEntityInterface $entity = NULL
  ) {

    $json = [
      'id' => $entity->uuid(),
      'action' => 'delete',
    ];

    // If there is a secret key we add it to the JSON.
    $secret = $this->getSecretKey($endpoint);
    if ($secret) {
      $json['secret'] = $secret;
    }

    $preview_path = "/__refresh";
    $preview_urls = $endpoint->getPreviewUrls();
    if (!empty($preview_urls) && !empty($preview_urls['preview_url'])) {
      foreach ($preview_urls['preview_url'] as $preview_url) {
        $this->gatsbyInstantPreview->updateData('preview', $preview_url, $json, $preview_path);
      }
    }

    $build_urls = $endpoint->getBuildUrls();
    if (!empty($build_urls) && !empty($build_urls['build_url'])) {
      foreach ($build_urls['build_url'] as $build_url) {
        $this->gatsbyInstantPreview->updateData('incrementalbuild', $build_url, $json);
      }
    }
  }

  /**
   * Triggers the refreshing of Gatsby preview and incremental builds.
   */
  public function gatsbyUpdate() {
    $this->gatsbyInstantPreview->gatsbyUpdate();
  }

  /**
   * Triggers build urls for a Gatsby Endpoint.
   */
  public function triggerBuildUrls(GatsbyEndpointInterface $endpoint) {
    $build_urls = $endpoint->getBuildUrls();
    if (!empty($build_urls) && !empty($build_urls['build_url'])) {
      foreach ($build_urls['build_url'] as $build_url) {
        $this->gatsbyInstantPreview->triggerRefresh($build_url);
      }
    }
  }

  /**
   * Tries to get the secret key for a Gatsby Endpoint if it exists.
   */
  private function getSecretKey(GatsbyEndpointInterface $endpoint) {
    $settings = $endpoint->getSettings();

    if (!empty($settings['secret_key'])) {
      return $settings['secret_key'];
    }

    return FALSE;
  }

}
