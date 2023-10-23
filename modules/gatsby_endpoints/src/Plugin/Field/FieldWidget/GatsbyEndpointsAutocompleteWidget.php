<?php

namespace Drupal\gatsby_endpoints\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Autocomplete widget for Gatsby Endpoint reference fields.
 *
 * @FieldWidget(
 *   id = "gatsby_endpoints_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field for Gatsby Endpoints."),
 *   field_types = {
 *     "gatsby_endpoint_reference"
 *   }
 * )
 */
class GatsbyEndpointsAutocompleteWidget extends EntityReferenceAutocompleteWidget {

}
