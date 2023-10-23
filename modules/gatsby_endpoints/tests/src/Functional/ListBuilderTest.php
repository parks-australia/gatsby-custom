<?php

namespace Drupal\Tests\gatsby_endpoints\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a test for the list builder.
 *
 * @group gatsby_endpoints
 *
 * @requires module jsonapi_extras
 */
class ListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove this in https://www.drupal.org/project/gatsby/issues/3198673
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'gatsby_endpoints',
    'jsonapi_extras',
    'path_alias',
  ];

  /**
   * Tests the list builder.
   */
  public function testListBuilder() {
    // Basic smoke test.
    $this->drupalLogin($this->createUser(['manage gatsby endpoints']));
    $this->drupalGet(Url::fromRoute('gatsby_endpoints.gatsby_endpoints_collection'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
