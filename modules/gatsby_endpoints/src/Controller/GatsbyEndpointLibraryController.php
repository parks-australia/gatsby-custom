<?php

namespace Drupal\gatsby_endpoints\Controller;

use Drupal\gatsby_endpoints\Plugin\GatsbyEndpointManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of Gatsby Endpoint plugins to be added to the layout.
 */
class GatsbyEndpointLibraryController extends ControllerBase {

  /**
   * The Gatsby endpoint manager.
   *
   * @var \Drupal\gatsby_endpoints\Plugin\GatsbyEndpointManager
   */
  protected $gatsbyEndpointManager;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * GatsbyEndpointLibraryController constructor.
   *
   * @param \Drupal\gatsby_endpoints\Plugin\GatsbyEndpointManager $gatsbyEndpointManager
   *   The Gatsby Endoint manager.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The  local action manager.
   */
  public function __construct(GatsbyEndpointManager $gatsbyEndpointManager, LocalActionManagerInterface $local_action_manager) {
    $this->gatsbyEndpointManager = $gatsbyEndpointManager;
    $this->localActionManager = $local_action_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.gatsby_endpoint'),
      $container->get('plugin.manager.menu.local_action')
    );
  }

  /**
   * Shows a list of Gatsby endoints that can be added.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listGatsbyEndpoints() {

    $headers = [
      ['data' => $this->t('Type')],
      ['data' => $this->t('Description')],
      ['data' => $this->t('Operations')],
    ];

    $definitions = $this->gatsbyEndpointManager->getDefinitions();

    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['label'],
        ],
      ];
      $row['description']['data'] = $plugin_definition['description'];

      $links['add'] = [
        'title' => $this->t('Add new endpoint'),
        'url' => Url::fromRoute('gatsby_endpoints.admin_add', ['plugin_id' => $plugin_id]),
      ];

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['gatsby_endpoints'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No types available. Please enable one of the submodules or add your own custom plugin.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the local actions for this listing.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions() {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

}
