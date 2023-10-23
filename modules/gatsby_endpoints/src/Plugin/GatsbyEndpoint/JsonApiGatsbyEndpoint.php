<?php

namespace Drupal\gatsby_endpoints\Plugin\GatsbyEndpoint;

use Drupal\gatsby_endpoints\Plugin\GatsbyEndpointBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Gatsby Endpoint type for use with JSON:API.
 *
 * @GatsbyEndpoint(
 *  id = "jsonapi",
 *  label = "JSON:API",
 *  description = "Use this type for JSON:API enabled endpoints."
 * )
 */
class JsonApiGatsbyEndpoint extends GatsbyEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function gatsbyEndpointForm($form, FormStateInterface $form_state) {
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gatsby Secret Key'),
      '#description' => $this->t('A Secret Key value that will be sent to Gatsby Preview and Build servers for an
        additional layer of security. <a href="#" id="gatsby--generate">Generate a Secret Key</a>'),
      '#default_value' => isset($this->configuration['secret_key']) ? $this->configuration['secret_key'] : '',
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function gatsbyEndpointSubmit($form, FormStateInterface $form_state) {
    $this->configuration['secret_key'] = $form_state->getValue('secret_key');
  }

}
