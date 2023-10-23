<?php

namespace Drupal\gatsby_endpoints\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Field formatter for Gatsby Endpoints fields that displays the label.
 *
 * @FieldFormatter(
 *   id = "gatsby_endpoints_label_view",
 *   label = @Translation("Gatsby Endpoints Label"),
 *   description = @Translation("Display the Gatsby Endpoint labels."),
 *   field_types = {
 *     "gatsby_endpoint_reference"
 *   }
 * )
 */
class GatsbyEndpointsFormatter extends EntityReferenceLabelFormatter {

}
