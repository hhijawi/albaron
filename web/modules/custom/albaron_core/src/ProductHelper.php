<?php

namespace Drupal\albaron_core;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;

/**
 * Helper utilities for extracting product presentation data.
 */
class ProductHelper {

  /**
   * Build a lightweight card array for a product node.
   */
  public static function card(NodeInterface $node): array {
    return [
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'code' => self::scalar($node, 'field_product_code'),
      'summary' => self::scalar($node, 'field_summary'),
      'family' => self::firstTerm($node, 'field_stone_family'),
      'finishes' => self::terms($node, 'field_finish'),
      'colors' => self::terms($node, 'field_color'),
      'applications' => self::terms($node, 'field_application'),
      'dimensions' => self::scalar($node, 'field_dimensions'),
      'thickness' => self::scalar($node, 'field_thickness'),
      'image' => self::images($node, 'product_card')[0] ?? NULL,
    ];
  }

  /**
   * Get a scalar field value.
   */
  public static function scalar(NodeInterface $node, string $field): ?string {
    if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
      return $node->get($field)->value;
    }
    return NULL;
  }

  /**
   * Get the first referenced term label.
   */
  public static function firstTerm(NodeInterface $node, string $field): ?string {
    $terms = self::terms($node, $field);
    return $terms[0] ?? NULL;
  }

  /**
   * Get referenced term labels.
   */
  public static function terms(NodeInterface $node, string $field): array {
    $out = [];
    if ($node->hasField($field)) {
      $list = $node->get($field);
      if ($list instanceof EntityReferenceFieldItemListInterface) {
        $repository = \Drupal::service('entity.repository');
        foreach ($list->referencedEntities() as $term) {
          $out[] = $repository->getTranslationFromContext($term)->label();
        }
      }
    }
    return $out;
  }

  /**
   * Get image URLs (given style) from a product's media field.
   */
  public static function images(NodeInterface $node, string $style = 'large'): array {
    $urls = [];
    if (!$node->hasField('field_images')) {
      return $urls;
    }
    $list = $node->get('field_images');
    if (!$list instanceof EntityReferenceFieldItemListInterface) {
      return $urls;
    }
    $image_style = ImageStyle::load($style);
    foreach ($list->referencedEntities() as $media) {
      if (!$media instanceof \Drupal\Core\Entity\FieldableEntityInterface) {
        continue;
      }
      if (!$media->hasField('field_media_image') || $media->get('field_media_image')->isEmpty()) {
        continue;
      }
      $file = $media->get('field_media_image')->entity;
      if (!$file) {
        continue;
      }
      $uri = $file->getFileUri();
      $urls[] = [
        'src' => $image_style ? $image_style->buildUrl($uri) : \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
        'large' => ImageStyle::load('product_wide') ? ImageStyle::load('product_wide')->buildUrl($uri) : \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
        'alt' => $media->get('field_media_image')->alt ?: $node->label(),
      ];
    }
    return $urls;
  }

  /**
   * Get referenced nodes as [['title' => ..., 'url' => ...]].
   */
  public static function linkedNodes(NodeInterface $node, string $field): array {
    $out = [];
    if ($node->hasField($field)) {
      $list = $node->get($field);
      if ($list instanceof EntityReferenceFieldItemListInterface) {
        $repository = \Drupal::service('entity.repository');
        foreach ($list->referencedEntities() as $entity) {
          $entity = $repository->getTranslationFromContext($entity);
          $out[] = [
            'title' => $entity->label(),
            'url' => $entity->toUrl()->toString(),
          ];
        }
      }
    }
    return $out;
  }

  /**
   * Load taxonomy terms of a vocabulary as [tid => name] for filters.
   */
  public static function vocabularyOptions(string $vid): array {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vid]);
    $out = [];
    foreach ($terms as $term) {
      $translated = \Drupal::service('entity.repository')->getTranslationFromContext($term);
      $out[$term->id()] = $translated->label();
    }
    asort($out);
    return $out;
  }

  /**
   * Build a filter link preserving other active filters.
   */
  public static function filterUrl(string $key, $value, array $active): string {
    $query = $active;
    if ($value === NULL) {
      unset($query[$key]);
    }
    else {
      $query[$key] = $value;
    }
    return Url::fromRoute('albaron_core.products', [], ['query' => $query])->toString();
  }

  /**
   * Build a products URL from a multi-select active-filter map ([key => [tids]]).
   */
  public static function activeUrl(array $active): string {
    $query = [];
    foreach ($active as $key => $tids) {
      if (!empty($tids)) {
        $query[$key] = implode(',', $tids);
      }
    }
    return Url::fromRoute('albaron_core.products', [], $query ? ['query' => $query] : [])->toString();
  }

  /**
   * Toggle a term within a facet (add if absent, remove if present).
   */
  public static function toggleUrl(array $active, string $key, int $tid): string {
    $set = $active[$key] ?? [];
    if (in_array($tid, $set, TRUE)) {
      $set = array_values(array_diff($set, [$tid]));
    }
    else {
      $set[] = $tid;
    }
    if ($set) {
      $active[$key] = $set;
    }
    else {
      unset($active[$key]);
    }
    return self::activeUrl($active);
  }

}
