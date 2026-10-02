<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * About Al-Baron — a rich, image-led, animated story page.
 */
class AboutController extends ControllerBase {

  /**
   * Builds the about page.
   */
  public function view(): array {
    // Pool of wide product images to illustrate the story sections.
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 12)
      ->execute();

    $pool = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      foreach (ProductHelper::images($node, 'product_wide') as $img) {
        $pool[] = $img['large'];
      }
    }
    $pick = static fn(int $i): ?string => $pool[$i % max(count($pool), 1)] ?? NULL;

    $build = [
      '#theme' => 'albaron_about',
      '#images' => [
        'hero' => $pick(0),
        'story' => $pick(2),
        'heritage' => $pick(5),
      ],
      '#stats' => [
        ['num' => '25', 'suffix' => '+', 'label' => $this->t('Years of craft')],
        ['num' => '40', 'suffix' => '+', 'label' => $this->t('Stone varieties')],
        ['num' => '15', 'suffix' => '', 'label' => $this->t('Export markets')],
        ['num' => '100', 'suffix' => '%', 'label' => $this->t('Palestinian stone')],
      ],
      '#values' => [
        [
          'title' => $this->t('Authenticity'),
          'text' => $this->t('Genuine Palestinian limestone and marble, quarried from the hills around Bethlehem — nothing imitated, nothing substituted.'),
          'icon' => 'gem',
        ],
        [
          'title' => $this->t('Precision'),
          'text' => $this->t('Calibrated thickness, consistent colour and repeatable finishes, held to tolerance on every order, large or small.'),
          'icon' => 'target',
        ],
        [
          'title' => $this->t('Reliability'),
          'text' => $this->t('What you specify is what arrives — packed to export standard and delivered on schedule to projects worldwide.'),
          'icon' => 'truck',
        ],
      ],
      '#milestones' => [
        ['year' => $this->t('Heritage'), 'text' => $this->t('Generations of stone craft rooted in the quarries of Bethlehem.')],
        ['year' => $this->t('Modernised'), 'text' => $this->t('Traditional craft combined with modern sawing, calibration and finishing.')],
        ['year' => $this->t('Export-ready'), 'text' => $this->t('Processes and packing built for demanding international specifications.')],
        ['year' => $this->t('Global'), 'text' => $this->t('Palestinian stone delivered to architects and developers across the world.')],
      ],
      '#markets' => [
        $this->t('Gulf (GCC)'),
        $this->t('Europe'),
        $this->t('Levant'),
        $this->t('North Africa'),
        $this->t('North America'),
        $this->t('Asia'),
      ],
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:product']);
    $cache->applyTo($build);

    return $build;
  }

}
