<?php

namespace Drupal\Tests\gatsby_endpoints\Kernel;

use Drupal\gatsby_endpoints\Controller\GatsbyEndpointController;
use Drupal\gatsby_endpoints\Entity\GatsbyEndpoint;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a class for testing endpoint.
 *
 * @group gatsby_endpoints
 *
 * @requires module jsonapi_extras
 */
class BundleEndpointsTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   *
   * @todo Remove in https://www.drupal.org/project/gatsby/issues/3198673
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gatsby_instantpreview',
    'node',
    'gatsby_endpoints',
    'gatsby',
    'jsonapi',
    'serialization',
    'jsonapi_extras',
    'field',
    'text',
    'options',
    'system',
    'user',
    'path_alias',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'filter', 'jsonapi_extras']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
    $this->createContentType(['type' => 'page']);
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('gatsby.settings')
      ->set('preview_entity_types', [
        'node',
      ])
      ->set('server_url', 'http://example.com')
      ->save();
    $this->setUpCurrentUser([], [
      'access content',
    ]);
  }

  /**
   * Tests bundle endpoints.
   */
  public function testBundleEndpoints() {
    $endpoint = GatsbyEndpoint::create([
      'id' => $this->randomMachineName(),
      'plugin' => 'jsonapi',
      'preview_urls' => ['http://example.com'],
      'build_urls' => ['http://example.com'],
      'settings' => [],
      'build_entity_types' => [
        [
          'entity_type' => 'node',
          'entity_bundles' => ['page' => 'page'],
          'include_entities' => ['node' => 'node'],
        ],
      ],
      'included_entity_types' => [],
    ]);
    $endpoint->save();
    $controller = GatsbyEndpointController::create(\Drupal::getContainer());
    $request = \Drupal::request();
    $response = $controller->sync($endpoint->id(), $request);
    $data = $response->getContent();
    $items = json_decode($data, TRUE);
    $this->assertTrue(!empty($items['links']['node--page']));
    $this->assertEquals(sprintf('%s/%s/node/page', $request->getSchemeAndHttpHost(), \Drupal::config('jsonapi_extras.settings')->get('path_prefix')), $items['links']['node--page']['href']);
  }

}
