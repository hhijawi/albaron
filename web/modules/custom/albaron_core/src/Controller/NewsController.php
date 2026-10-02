<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * News listing controller — dated company news and announcements.
 */
class NewsController extends ControllerBase {

  /**
   * Builds the news listing page.
   */
  public function view(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $repository = \Drupal::service('entity.repository');
    $date_formatter = \Drupal::service('date.formatter');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1)
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
        'date' => $date_formatter->format($node->getCreatedTime(), 'custom', 'j M Y'),
        'image' => ProductHelper::images($node, 'product_card')[0] ?? NULL,
      ];
    }

    $build = [
      '#theme' => 'albaron_news',
      '#items' => $items,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:news']);
    $cache->addCacheContexts(['languages:language_content']);
    $cache->applyTo($build);

    return $build;
  }

}
