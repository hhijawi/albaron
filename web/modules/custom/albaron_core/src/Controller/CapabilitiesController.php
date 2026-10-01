<?php

namespace Drupal\albaron_core\Controller;

use Drupal\albaron_core\ProductHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Capabilities & Certifications — a rich, image-led, animated page.
 */
class CapabilitiesController extends ControllerBase {

  /**
   * Builds the capabilities page.
   */
  public function view(): array {
    // Gather a pool of wide product images to illustrate the sections.
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
      '#theme' => 'albaron_capabilities',
      '#images' => [
        'hero' => $pick(0),
        'intro' => $pick(1),
      ],
      '#steps' => [
        ['num' => '01', 'title' => $this->t('Quarry Selection'), 'text' => $this->t('Blocks are hand-selected at the quarry for colour, density and character.')],
        ['num' => '02', 'title' => $this->t('Block Sawing'), 'text' => $this->t('Gang saws cut raw blocks into calibrated slabs with consistent thickness.')],
        ['num' => '03', 'title' => $this->t('Cutting & Calibration'), 'text' => $this->t('Bridge cutters size slabs and tiles to precise architectural specifications.')],
        ['num' => '04', 'title' => $this->t('Surface Finishing'), 'text' => $this->t('Polished, honed, brushed, bush-hammered and split-face finishes applied to spec.')],
        ['num' => '05', 'title' => $this->t('Quality Inspection'), 'text' => $this->t('Every batch is inspected for colour, dimension and finish before it leaves.')],
        ['num' => '06', 'title' => $this->t('Export Packing'), 'text' => $this->t('Secure crating and palletising to export standard for safe global delivery.')],
      ],
      '#features' => [
        [
          'eyebrow' => $this->t('Production'),
          'title' => $this->t('A controlled workflow, quarry to quay'),
          'text' => $this->t('Selection at the quarry, block sawing, calibration, surface finishing and quality inspection — each stage is controlled to hold colour, dimension and finish tolerances across large orders.'),
          'points' => [$this->t('Calibrated thickness'), $this->t('Consistent colour batching'), $this->t('Repeatable finishes')],
          'image' => $pick(2),
        ],
        [
          'eyebrow' => $this->t('Machinery & Capacity'),
          'title' => $this->t('Equipped for scale and precision'),
          'text' => $this->t('Modern gang saws, bridge cutters and finishing lines enable a broad range of formats, from standard tiles to large slabs and cut-to-size architectural pieces.'),
          'points' => [$this->t('Tiles, slabs & cut-to-size'), $this->t('Large-format capability'), $this->t('Custom dimensions on request')],
          'image' => $pick(4),
        ],
        [
          'eyebrow' => $this->t('Quality Assurance'),
          'title' => $this->t('Inspected before it ships'),
          'text' => $this->t('Documented inspection of colour consistency, dimensional accuracy and surface finish before packing. Custom finishes and sizes are available on request.'),
          'points' => [$this->t('Colour consistency checks'), $this->t('Dimensional accuracy'), $this->t('Finish verification')],
          'image' => $pick(6),
        ],
        [
          'eyebrow' => $this->t('Export Readiness'),
          'title' => $this->t('Packed to arrive perfectly'),
          'text' => $this->t('Secure crating and palletising to export standard, with experienced logistics for reliable international delivery across the Gulf, Europe and beyond.'),
          'points' => [$this->t('Export-grade crating'), $this->t('Experienced logistics'), $this->t('Global delivery')],
          'image' => $pick(8),
        ],
      ],
      '#stats' => [
        ['num' => '2.6', 'suffix' => 'm', 'label' => $this->t('Max slab length')],
        ['num' => '7', 'suffix' => '', 'label' => $this->t('Finish types')],
        ['num' => '15', 'suffix' => '', 'label' => $this->t('Export markets')],
        ['num' => '100', 'suffix' => '%', 'label' => $this->t('QA inspected')],
      ],
      '#certifications' => [
        ['title' => $this->t('Quarry-to-Quay Control'), 'text' => $this->t('Full ownership of the process from extraction to export.')],
        ['title' => $this->t('Documented Quality'), 'text' => $this->t('Inspection records for colour, dimension and finish.')],
        ['title' => $this->t('Technical Data Sheets'), 'text' => $this->t('Material specifications available on request.')],
        ['title' => $this->t('Export Compliance'), 'text' => $this->t('Packing and documentation prepared to export standards.')],
      ],
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:product']);
    $cache->applyTo($build);

    return $build;
  }

}
