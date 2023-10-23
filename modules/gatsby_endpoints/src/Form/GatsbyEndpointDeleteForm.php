<?php

namespace Drupal\gatsby_endpoints\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Builds the form to delete Gatsby endpoint entities.
 */
class GatsbyEndpointDeleteForm extends EntityConfirmFormBase {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('gatsby_endpoints.gatsby_endpoints_collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    // If there are no other Endpoints, remove the Gatsby Reference Field.
    // @todo.
    $this->messenger()->addMessage(
      $this->t('Deleted Gatsby Endpoint: @label.',
        [
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
