<?php

namespace Drupal\gatsby_endpoints\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Gatsby endpoint entities.
 */
interface GatsbyEndpointInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getWeight();

  /**
   * {@inheritDoc}
   */
  public function getEntityTypes($key);

  /**
   * {@inheritdoc}
   */
  public function getBuildEntityTypes();

  /**
   * {@inheritDoc}
   */
  public function getBuildEntityType($entity_type);

  /**
   * {@inheritdoc}
   */
  public function setBuildEntityTypes($build_entity_types);

  /**
   * {@inheritdoc}
   */
  public function getIncludedEntityTypes($build_type);

  /**
   * {@inheritDoc}
   */
  public function getUrls($key);

  /**
   * {@inheritdoc}
   */
  public function getPreviewUrls();

  /**
   * {@inheritdoc}
   */
  public function setPreviewUrls($preview_urls);

  /**
   * {@inheritdoc}
   */
  public function getBuildUrls();

  /**
   * {@inheritdoc}
   */
  public function setBuildUrls($build_urls);

  /**
   * {@inheritdoc}
   */
  public function getBuildTrigger();

  /**
   * {@inheritdoc}
   */
  public function setBuildTrigger($built_trigger);

  /**
   * {@inheritdoc}
   */
  public function getPlugin();

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections();

  /**
   * {@inheritdoc}
   */
  public function getSettings();

}
