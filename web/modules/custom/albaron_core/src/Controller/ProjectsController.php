<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Projects listing controller — completed project case studies.
 */
class ProjectsController extends ControllerBase {

  /**
   * Builds the projects listing page.
   */
  public function view(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $repository = \Drupal::service('entity.repository');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'project')
      ->condition('status', 1)
      ->sort('field_year', 'DESC')
      ->sort('created', 'DESC')
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      $node = $repository->getTranslationFromContext($node);
      $items[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'summary' => ProductHelper::scalar($node, 'field_summary'),
        'location' => ProductHelper::scalar($node, 'field_location'),
        'year' => ProductHelper::scalar($node, 'field_year'),
        'stones' => ProductHelper::terms($node, 'field_stone_used'),
        'image' => ProductHelper::images($node, 'product_card')[0] ?? NULL,
      ];
    }

    $build = [
      '#theme' => 'albaron_projects',
      '#items' => $items,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:project']);
    $cache->addCacheContexts(['languages:language_content']);
    $cache->applyTo($build);

    return $build;
  }

}
