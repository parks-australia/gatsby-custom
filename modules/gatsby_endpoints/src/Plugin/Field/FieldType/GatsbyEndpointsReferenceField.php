<?php

namespace Drupal\gatsby_endpoints\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * The Gatsby endpoint reference field.
 *
 * @FieldType(
 *   id = "gatsby_endpoint_reference",
 *   label = @Translation("Gatsby Endpoint Reference"),
 *   description = @Translation("An entity field containing an entity reference to a Gatsby Endpoint."),
 *   category = "reference",
 *   default_widget = "gatsby_endpoints_select",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class GatsbyEndpointsReferenceField extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of item to reference'),
      '#options' => ['gatsby_endpoint' => $this->t('Gatsby Endpoint')],
      '#default_value' => 'gatsby_endpoint',
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#size' => 1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}
