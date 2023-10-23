<?php

namespace Drupal\gatsby_endpoints;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface;

/**
 * Provides a service to manage Gatsby endpoints.
 */
class GatsbyEndpointManager {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new GatsbyEndpointManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
      EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets an Endpoint object from an endpoint_id string.
   */
  public function getEndpoint(string $endpoint_id) {
    $query = $this->entityTypeManager->getStorage('gatsby_endpoint');
    $endpoint_id = $query->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $endpoint_id)
      ->execute();

    if (!empty($endpoint_id)) {
      return $this->entityTypeManager
        ->getStorage('gatsby_endpoint')
        ->load(reset($endpoint_id));
    }

    return FALSE;
  }

  /**
   * Gets all available Gatsby Endpoints.
   */
  public function getEndpoints() {
    $query = $this->entityTypeManager->getStorage('gatsby_endpoint');
    $endpoint_ids = $query->getQuery()
      ->accessCheck(FALSE)
      ->sort('weight')
      ->execute();

    $endpoints = [];

    if (!empty($endpoint_ids)) {
      $endpoints = $this->entityTypeManager->getStorage('gatsby_endpoint')
        ->loadMultiple($endpoint_ids);
    }

    return $endpoints;
  }

  /**
   * Checks an entity to see if it should be handled by the endpoint.
   */
  public function checkEntity(GatsbyEndpointInterface $endpoint, ContentEntityInterface $entity, $op) {
    // Check if this entity is selected in the build types.
    if (!$this->checkBuildEntityTypeAndBundle($endpoint->getBuildEntityTypes(), $entity->getEntityTypeId(), $entity->bundle())) {
      return FALSE;
    }

    // If this entity doesn't have an endpoint field then the endpoint
    // needs to track this entity so return the original operation.
    $reference_field = $this->getGatsbyReferenceField($entity->getEntityTypeId(), $entity->bundle());
    if (!$reference_field) {
      return $op;
    }

    // If this entity is selected, the endpoint needs to track the entity.
    $in_selected = $this->checkEndpointValues(
      $entity->get($reference_field)->getValue(),
      $endpoint->id()
    );
    if ($in_selected) {
      return $op;
    }

    // If this endpoint wasn't selected, we check if it was previously
    // selected and needs to be deleted.
    if ($op == 'update') {
      $in_original = $this->checkEndpointValues(
        $entity->original->get($reference_field)->getValue(),
        $endpoint->id()
      );

      if ($in_original) {
        return 'delete';
      }
    }

    // The endpoint doesn't care about this specific entity.
    return FALSE;
  }

  /**
   * Gets any Gatsby Reference fields for a specific entity type.
   */
  public function getGatsbyReferenceField($entity_type, $entity_bundle) {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle);

    foreach ($definitions as $field) {
      $field_type = $field->getType();
      $field_name = $field->getName();

      if ($field_type == 'gatsby_endpoint_reference' && !empty($field_name)) {
        return $field_name;
      }

    }

    return FALSE;
  }

  /**
   * Gets the correct preview URL to use for a Drupal entity.
   *
   * Currently this just returns the first matching preview URL.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to get the correct preview URL for.
   *
   * @return mixed
   *   Returns the matching preview URL or FALSE if one doesn't exist.
   */
  public function getPreviewUrlForEntity(ContentEntityInterface $entity) {
    $ref_field = $this->getGatsbyReferenceField($entity->getEntityTypeId(), $entity->bundle());

    if ($ref_field) {
      $endpoint_value = $entity->get($ref_field)->getValue();

      if (!empty($endpoint_value[0]['target_id'])) {
        $endpoint = $this->getEndpoint($endpoint_value[0]['target_id']);

        if ($endpoint) {
          return $this->getFirstPreviewUrl($endpoint);
        }
      }
    }
    else {
      $endpoints = $this->getEndpoints();

      foreach ($endpoints as $endpoint) {
        if ($this->checkBuildEntityTypeAndBundle($endpoint->getBuildEntityTypes(), $entity->getEntityTypeId(), $entity->bundle())) {
          return $this->getFirstPreviewUrl($endpoint);
        }
      }
    }

    return FALSE;
  }

  /**
   * Gets the first preview URL for a Gatsby Endpoint.
   */
  public function getFirstPreviewUrl(GatsbyEndpointInterface $endpoint) {
    $urls = $endpoint->getPreviewUrls();

    if (!empty($urls['preview_url'][0])) {
      return $urls['preview_url'][0];
    }

    return FALSE;
  }

  /**
   * Returns true if the endpoint is in the array of endpoint values.
   */
  private function checkEndpointValues($endpoint_values, $endpoint_id) {
    foreach ($endpoint_values as $endpoint_value) {
      if ($endpoint_value['target_id'] === $endpoint_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if the entity type and bundle are selected as build types.
   */
  private function checkBuildEntityTypeAndBundle($build_types, $entity_type, $entity_bundle) {
    foreach ($build_types as $build_type) {
      if (empty($build_type['entity_type']) || empty($build_type['entity_bundles'])) {
        continue;
      }

      if ($build_type['entity_type'] !== $entity_type) {
        continue;
      }

      foreach ($build_type['entity_bundles'] as $bundle_type) {
        if ($bundle_type === $entity_bundle) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
