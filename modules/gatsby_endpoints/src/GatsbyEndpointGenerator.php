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

    /**
     * Parks Australia updates:
     * tags: gatsby-custom, includes=, url-length
     * 
     * For whatever reason, the includes array contains duplicate entities.
     * We remove them here to reduce the length of the URL and save the internet
     * a few KB/volts/grams of cO2.
     */

    // If an entity includes more than one instance of another entity in
    // the relationship chain, there may be  a lot of duplicate items. 
    // Test if they exist before adding more to cull the list to unique
    // items only. 
    $url_params['include'] = array_unique($url_params['include']);

    if (!empty($url_params['filter'])) {
      $param_string .= $url_params['filter'];
    }

    /**
     * Parks Australia updates:
     * tags: gatsby-custom, includes=, url-length
     * 
     * The 'include' array contains all levels of the relationship chain, resulting 
     * in unnecessary includes e.g. parent, parent.child, all to get 
     * parent.child.grandchild, because requesting parent.child.grandchild also captures
     * any data from 'parent' and 'parent.child' in the response.
     */
    
    // Loop over the 'includes' array and scrub any items that are also
    // substrings of other items. This should leave us with a clean list of 
    // 'parent.child.grandchild' items that returns the exact same data
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
