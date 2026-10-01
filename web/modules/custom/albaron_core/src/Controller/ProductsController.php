<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Products browse controller with faceted, AJAX-capable taxonomy filters.
 */
class ProductsController extends ControllerBase {

  /**
   * Filter definitions: query key => vocabulary + product field + label.
   */
  private const FILTERS = [
    'stone_family' => ['vid' => 'stone_family', 'field' => 'field_stone_family', 'label' => 'Material'],
    'color' => ['vid' => 'color', 'field' => 'field_color', 'label' => 'Color'],
    'finish' => ['vid' => 'finish', 'field' => 'field_finish', 'label' => 'Finish'],
    'application' => ['vid' => 'application', 'field' => 'field_application', 'label' => 'Application'],
  ];

  /**
   * Full products page.
   */
  public function view(Request $request): array {
    $data = $this->buildData($request);
    $build = [
      '#theme' => 'albaron_products',
      '#inner' => $this->innerBuild($data),
    ];
    $this->cacheability()->applyTo($build);
    return $build;
  }

  /**
   * AJAX fragment: only the filters + results markup.
   */
  public function ajax(Request $request): Response {
    $data = $this->buildData($request);
    $html = \Drupal::service('renderer')->renderRoot($this->innerBuild($data));

    $response = new CacheableResponse($html);
    $response->addCacheableDependency($this->cacheability());
    return $response;
  }

  /**
   * Cache metadata shared by both responses.
   */
  private function cacheability(): CacheableMetadata {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args', 'languages:language_content']);
    $cache->addCacheTags(['node_list:product', 'taxonomy_term_list']);
    return $cache;
  }

  /**
   * Build the inner render array (filters + results).
   */
  private function innerBuild(array $data): array {
    return [
      '#theme' => 'albaron_products_inner',
      '#products' => $data['products'],
      '#filters' => $data['filters'],
      '#active' => $data['active'],
      '#active_summary' => $data['active_summary'],
      '#total' => $data['total'],
    ];
  }

  /**
   * Query products, compute facet counts and the active-filter summary.
   */
  private function buildData(Request $request): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $repository = \Drupal::service('entity.repository');
    $lang = $this->languageManager()->getCurrentLanguage()->getId();

    // Read active filters from the request. Each facet accepts a comma-separated
    // list of term ids for multi-select (OR within a facet).
    $active = [];
    foreach (self::FILTERS as $key => $def) {
      $raw = $request->query->get($key);
      if ($raw === NULL || $raw === '') {
        continue;
      }
      $valid = array_keys(ProductHelper::vocabularyOptions($def['vid']));
      $tids = array_values(array_unique(array_filter(
        array_map('intval', explode(',', (string) $raw)),
        static fn($v) => in_array($v, $valid, TRUE)
      )));
      if ($tids) {
        $active[$key] = $tids;
      }
    }

    // Load all published products once and index their facet term ids.
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('status', 1)
      ->sort('title', 'ASC')
      ->execute();
    $nodes = $storage->loadMultiple($ids);

    $index = [];
    foreach ($nodes as $nid => $node) {
      if (!$node instanceof \Drupal\Core\Entity\FieldableEntityInterface) {
        continue;
      }
      foreach (self::FILTERS as $key => $def) {
        $tids = [];
        if ($node->hasField($def['field'])) {
          foreach ($node->get($def['field']) as $item) {
            if ($item->target_id) {
              $tids[] = (int) $item->target_id;
            }
          }
        }
        $index[$nid][$key] = $tids;
      }
    }

    // A node matches when, for every constrained facet, it has at least one of
    // the selected terms (OR within a facet, AND across facets).
    $matches = static function (array $nodeFacets, array $filters): bool {
      foreach ($filters as $key => $tids) {
        if (!array_intersect($tids, $nodeFacets[$key] ?? [])) {
          return FALSE;
        }
      }
      return TRUE;
    };

    // Result set: products matching all active filters.
    $products = [];
    foreach ($nodes as $nid => $node) {
      if ($matches($index[$nid], $active)) {
        $translated = $repository->getTranslationFromContext($node, $lang);
        $products[] = ProductHelper::card($translated);
      }
    }

    // Faceted groups with counts. A facet's own selection is excluded from its
    // own counts so each option shows how many results it would contribute.
    $filters = [];
    foreach (self::FILTERS as $key => $def) {
      $others = $active;
      unset($others[$key]);
      $options = [];
      foreach (ProductHelper::vocabularyOptions($def['vid']) as $tid => $name) {
        $count = 0;
        foreach ($nodes as $nid => $node) {
          if (in_array($tid, $index[$nid][$key], TRUE) && $matches($index[$nid], $others)) {
            $count++;
          }
        }
        $is_active = in_array($tid, $active[$key] ?? [], TRUE);
        $options[] = [
          'tid' => $tid,
          'name' => $name,
          'count' => $count,
          'active' => $is_active,
          'url' => ProductHelper::toggleUrl($active, $key, $tid),
        ];
      }
      $filters[] = [
        'key' => $key,
        'label' => $this->t($def['label']),
        'options' => $options,
      ];
    }

    // Active-filter summary pills — one per selected term, removable individually.
    $active_summary = [];
    foreach ($active as $key => $tids) {
      $names = ProductHelper::vocabularyOptions(self::FILTERS[$key]['vid']);
      foreach ($tids as $tid) {
        $active_summary[] = [
          'facet' => $key,
          'tid' => $tid,
          'name' => $names[$tid] ?? '',
          'clear' => ProductHelper::toggleUrl($active, $key, $tid),
        ];
      }
    }

    return [
      'products' => $products,
      'filters' => $filters,
      'active' => $active,
      'active_summary' => $active_summary,
      'total' => count($products),
    ];
  }

}
