<?php

namespace Drupal\gatsby_endpoints\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Select widget for Gatsby Endpoint reference fields.
 *
 * @FieldWidget(
 *   id = "gatsby_endpoints_select",
 *   label = @Translation("Select"),
 *   description = @Translation("A select field for Gatsby Endpoints."),
 *   field_types = {
 *     "gatsby_endpoint_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class GatsbyEndpointsSelectWidget extends OptionsSelectWidget {

}
