<?php

namespace Drupal\gatsby_endpoints;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\gatsby_endpoints\Entity\GatsbyEndpoint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a listing of Gatsby endpoint entities.
 */
class GatsbyEndpointListBuilder extends ConfigEntityListBuilder {

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new GatsbyEndpointListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Request $request) {
    parent::__construct($entity_type, $storage);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Endpoint Name');
    $header['plugin'] = $this->t('Type');
    $header['id'] = $this->t('Machine name');
    $header['url'] = $this->t('Endpoint URL');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!($entity instanceof GatsbyEndpoint)) {
      return parent::buildRow($entity);
    }

    $url = $this->request->getSchemeAndHttpHost() . '/gatsby/' . $entity->id();

    $row['label'] = $entity->label();
    $row['plugin'] = $entity->getPlugin()->getPluginDefinition()['label'];
    $row['id'] = $entity->id();
    $row['url'] = Link::fromTextAndUrl($url, Url::fromUri($url))->toString();
    return $row + parent::buildRow($entity);
  }

}
