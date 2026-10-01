<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Gallery controller — aggregates product imagery into a masonry grid.
 */
class GalleryController extends ControllerBase {

  /**
   * Builds the gallery page.
   */
  public function view(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->execute();

    $images = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      $title = $node->label();
      $family = ProductHelper::firstTerm($node, 'field_stone_family');
      $url = $node->toUrl()->toString();
      foreach (ProductHelper::images($node, 'gallery') as $img) {
        $img['caption'] = $title;
        $img['family'] = $family;
        $img['url'] = $url;
        $images[] = $img;
      }
    }

    $build = [
      '#theme' => 'albaron_gallery',
      '#images' => $images,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:product']);
    $cache->applyTo($build);

    return $build;
  }

}
