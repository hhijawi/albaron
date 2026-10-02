<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Home page controller.
 */
class HomeController extends ControllerBase {

  /**
   * Builds the home page render array.
   */
  public function view(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $lang = $this->languageManager()->getCurrentLanguage()->getId();
    $cache = new CacheableMetadata();
    $latest_news = NULL;
    $news_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->sort('nid', 'DESC')
      ->range(0, 1)
      ->execute();
    foreach ($storage->loadMultiple($news_ids) as $news) {
      $news = \Drupal::service('entity.repository')->getTranslationFromContext($news, $lang);
      $access = $news->access('view', NULL, TRUE);
      $cache->addCacheableDependency($news);
      $cache->addCacheableDependency($access);
      if (!$news->isPublished() || !$access->isAllowed()) {
        continue;
      }
      $url = $news->toUrl()->toString(TRUE);
      $cache->addCacheableDependency($url);
      $published = $news->getUntranslated()->getCreatedTime();
      $latest_news = [
        'title' => $news->label(),
        'url' => $url->getGeneratedUrl(),
        'date' => \Drupal::service('date.formatter')->format($published, 'custom', 'j M Y', NULL, $lang),
        'datetime' => gmdate('Y-m-d\TH:i:s\Z', $published),
        'image' => ProductHelper::images($news, 'product_card')[0] ?? NULL,
      ];
      foreach ($news->get('field_images')->referencedEntities() as $media) {
        $cache->addCacheableDependency($media);
        if ($media->hasField('field_media_image') && $media->get('field_media_image')->entity) {
          $cache->addCacheableDependency($media->get('field_media_image')->entity);
        }
      }
    }

    // Featured products (fall back to latest if none flagged).
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1)
      ->condition('field_featured', 1)
      ->sort('created', 'DESC')
      ->range(0, 6)
      ->execute();
    if (!$ids) {
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'product')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 6)
        ->execute();
    }
    $featured = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      $node = \Drupal::service('entity.repository')->getTranslationFromContext($node, $lang);
      $featured[] = ProductHelper::card($node);
    }

    // Stone families for the "explore by material" section.
    $families = [];
    foreach (ProductHelper::vocabularyOptions('stone_family') as $tid => $name) {
      $families[] = [
        'name' => $name,
        'url' => ProductHelper::filterUrl('stone_family', $tid, []),
      ];
    }

    // A few gallery images from recent products.
    $gallery = [];
    foreach ($storage->loadMultiple(array_slice($ids, 0, 6)) as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      foreach (ProductHelper::images($node, 'gallery') as $img) {
        $gallery[] = $img;
      }
    }
    $gallery = array_slice($gallery, 0, 8);

    $build = [
      '#theme' => 'albaron_home',
      '#featured' => $featured,
      '#families' => $families,
      '#gallery' => $gallery,
      '#latest_news' => $latest_news,
      '#stats' => [
        ['num' => '25+', 'label' => $this->t('Years of craft')],
        ['num' => '40+', 'label' => $this->t('Stone varieties')],
        ['num' => '15', 'label' => $this->t('Export markets')],
        ['num' => '100%', 'label' => $this->t('Natural Palestinian stone')],
      ],
    ];

    $cache->addCacheContexts(['languages:language_content', 'user.node_grants:view', 'user.permissions', 'timezone']);
    $cache->addCacheTags(['node_list:product', 'node_list:news', 'config:image.style.product_card']);
    $cache->applyTo($build);

    return $build;
  }

}
