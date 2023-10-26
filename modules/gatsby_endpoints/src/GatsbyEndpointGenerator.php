<?php

namespace Drupal\gatsby_endpoints;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\gatsby_endpoints\Entity\GatsbyEndpointInterface;

/**
 * Class GatsbyEndpointGenerator.
 *
 * Generates JSON:API links for a Gatsby Endpoint.
 */
class GatsbyEndpointGenerator {

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
   * Generates JSON:API links for a specific Gatsby Endpoint.
   */
  public function getEndpointLinks(GatsbyEndpointInterface $endpoint) {
    $build_types = $endpoint->getBuildEntityTypes();
    $links = [];

    foreach ($build_types as $build_type) {
      if (empty($build_type) || empty($build_type['entity_type'])) {
        continue;
      }

      $include_types = $endpoint->getIncludedEntityTypes($build_type);

      // Check if this has bundles.
      foreach ($build_type['entity_bundles'] as $bundle_id => $bundle_label) {
        if ($bundle_label === $bundle_id) {
          $params = $this->getUrlParameters($build_type['entity_type'], $bundle_id, $endpoint, $include_types);
          $entity_key = $build_type['entity_type'] . '--' . $bundle_id;
          $links[$entity_key] = $build_type['entity_type'] . '/' . $bundle_id . $params;
        }
      }

    }

    return $links;
  }

  /**
   * Gets the correct JSON:API url parameters string.
   */
  private function getUrlParameters($entity_type, $bundle, GatsbyEndpointInterface $endpoint, $include_types) {
    $url_params = $this->loadUrlFiltersAndIncludes($entity_type,
      $bundle, $endpoint, $include_types);

    $param_string = '';

    // If an entity includes more than one instance of another entity in
    //  the relationship chain, there may be  a lot of duplicate items. 
    // Test if they exist before adding more to cull the list to unique
    // items only. 
    $url_params['include'] = array_unique($url_params['include']);

    if (!empty($url_params['filter'])) {
      $param_string .= $url_params['filter'];
    }

    /**
     * Disable appending the related entity fields to the JSON API request, as 
     * this feature is only used in true Incremental Builds on Gatsby Cloud - r.i.p :( 
     */
    
    // As no other remote build service supports IBs, this feature is not needed.
    // In the case of the Place content type in Drupal, the `includes=` string is over 
    // 14,000 characters long due to complex use of Paragraphs, and cannot be read 
    // by Gatsby anyway. 

    // TODO: This query also contains all levels of the relationship chain, resulting 
    // in unnecessary includes e.g. field, field.child, all to get field.child.child
    // because requesting 'field.child.child' also captures 'field.child' and 'field' in
    // the response data.

    // Loop over the 'includes' array. For each item, check the rest of the array items 
    // and test if the current item appears as a substring of any other values in the array.
    // If it does, remove it from the array.
    foreach ($url_params['include'] as $key => $value) {
      foreach ($url_params['include'] as $key2 => $value2) {
        if ($key !== $key2 && strpos($value2, $value) !== FALSE) {
          unset($url_params['include'][$key]);
        }
      }
    }

    if (!empty($url_params['include'])) {
      $param_string .= '&include=' . implode(',', $url_params['include']);
    }

    // Add the starting "?" if parameters are needed.
    return $param_string ? '?' . $param_string : $param_string;
  }

  /**
   * Gets the includes and filter parameters for a JSON:API url.
   */
  private function loadUrlFiltersAndIncludes($entity_type, $bundle, GatsbyEndpointInterface $endpoint, $include_types, $current_field = FALSE) {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $core_reference_fields = ['comments', 'file', 'image'];
    $params = [];

    // Images are a special kind of file, so make sure include_types
    // include images if they have files added. Without this core image fields
    // will not work.
    if (in_array('file', $include_types)) {
      $include_types[] = 'image';
    }

    foreach ($definitions as $field) {
      $field_name = $field->getName();

      // Only check manually created fields.
      if (empty($field_name) || !$field instanceof FieldConfig) {
        continue;
      }

      $field_type = $field->getType();
      if ($field_type == 'gatsby_endpoint_reference') {
        $params['filter'] = 'filter[' . $field_name . '.meta.drupal_internal__target_id]=' . $endpoint->id();
      }
      elseif (in_array($field_type, $core_reference_fields)) {

        // Check if this field references an included entity type.
        if (in_array($field_type, $include_types)) {
          if ($current_field) {
            $field_name = $current_field . '.' . $field_name;
          }
          $params['include'][] = $field_name;
        }
      }
      elseif (in_array($field_type, [
        'entity_reference',
        'entity_reference_revisions',
      ])) {
        // Check if this field references an included entity type.
        $handler = $field->getSetting('handler');
        $reference_type = explode(':', $handler);
        if (!empty($reference_type[1]) && in_array($reference_type[1], $include_types)) {
          // Continue building out the JSON:API path to this related field.
          if ($current_field) {
            $field_name = $current_field . '.' . $field_name;
          }
          $params['include'][] = $field_name;

          // Now we need to recursively traverse this reference field to ensure
          // to include all the necessary related entity fields.
          $handler_settings = $field->getSetting('handler_settings');
          if (!empty($handler_settings['target_bundles'])) {
            foreach ($handler_settings['target_bundles'] as $target_bundle) {
              $reference_params = $this->loadUrlFiltersAndIncludes($reference_type[1],
                $target_bundle,
                $endpoint,
                $include_types,
                $field_name);

              if (!empty($reference_params['include'])) {
                $params['include'] = array_merge($params['include'], $reference_params['include']);
              }
            }
          }
        }
      }
    }

    return $params;
  }

}
