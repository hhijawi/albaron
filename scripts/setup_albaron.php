<?php

/**
 * @file
 * One-shot setup script for the Al-Baron site architecture.
 *
 * Run with: ddev drush php:script scripts/setup_albaron.php
 * Idempotent: safe to run multiple times.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

$messenger = static function (string $m): void {
  print "  - $m\n";
};

/**
 * Ensure a taxonomy vocabulary exists.
 */
$ensure_vocab = static function (string $vid, string $name, string $desc = '') use ($messenger): void {
  if (!Vocabulary::load($vid)) {
    Vocabulary::create(['vid' => $vid, 'name' => $name, 'description' => $desc])->save();
    $messenger("Vocabulary created: $vid");
  }
};

/**
 * Ensure a set of terms exist in a vocabulary. Returns [name => tid].
 */
$ensure_terms = static function (string $vid, array $names) use ($messenger): array {
  $map = [];
  foreach ($names as $name) {
    $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vid, 'name' => $name]);
    if ($existing) {
      $term = reset($existing);
    }
    else {
      $term = Term::create(['vid' => $vid, 'name' => $name]);
      $term->save();
      $messenger("Term created: $vid / $name");
    }
    $map[$name] = $term->id();
  }
  return $map;
};

/**
 * Ensure a field storage exists.
 */
$ensure_storage = static function (string $entity_type, string $field_name, string $type, int $cardinality = 1, array $settings = []) use ($messenger): void {
  if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $settings,
    ])->save();
    $messenger("Field storage created: $field_name ($type)");
  }
};

/**
 * Ensure a field instance exists on a bundle.
 */
$ensure_field = static function (string $entity_type, string $bundle, string $field_name, string $label, array $settings = [], bool $required = FALSE) use ($messenger): void {
  if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'required' => $required,
      'settings' => $settings,
    ])->save();
    $messenger("Field instance created: $bundle.$field_name");
  }
};

print "== Al-Baron architecture setup ==\n";

// ---------------------------------------------------------------------------
// 1. Taxonomies.
// ---------------------------------------------------------------------------
$ensure_vocab('stone_family', 'Stone Family', 'Material family: limestone, marble, travertine, etc.');
$ensure_vocab('color', 'Color', 'Dominant color / appearance group.');
$ensure_vocab('finish', 'Finish', 'Surface finish of the stone.');
$ensure_vocab('application', 'Application', 'Recommended use / application.');

$fam = $ensure_terms('stone_family', ['Limestone', 'Marble', 'Travertine', 'Sandstone', 'Breccia']);
$col = $ensure_terms('color', ['Beige', 'Cream', 'Golden', 'Grey', 'Rosso / Red', 'White']);
$fin = $ensure_terms('finish', ['Polished', 'Honed', 'Brushed', 'Bush-hammered', 'Split-face', 'Sandblasted', 'Tumbled']);
$app = $ensure_terms('application', ['Wall Cladding', 'Flooring', 'Facades', 'Countertops', 'Landscaping', 'Stairs & Steps']);

// ---------------------------------------------------------------------------
// 2. Product content type.
// ---------------------------------------------------------------------------
if (!NodeType::load('product')) {
  NodeType::create([
    'type' => 'product',
    'name' => 'Product',
    'description' => 'A stone or marble product in the export catalogue.',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  $messenger('Content type created: product');
}

// Field storages.
$ensure_storage('node', 'field_product_code', 'string');
$ensure_storage('node', 'field_summary', 'string_long');
$ensure_storage('node', 'field_stone_family', 'entity_reference', 1, ['target_type' => 'taxonomy_term']);
$ensure_storage('node', 'field_color', 'entity_reference', -1, ['target_type' => 'taxonomy_term']);
$ensure_storage('node', 'field_finish', 'entity_reference', -1, ['target_type' => 'taxonomy_term']);
$ensure_storage('node', 'field_application', 'entity_reference', -1, ['target_type' => 'taxonomy_term']);
$ensure_storage('node', 'field_dimensions', 'string');
$ensure_storage('node', 'field_thickness', 'string');
$ensure_storage('node', 'field_technical_data', 'text_long');
$ensure_storage('node', 'field_packaging', 'string_long');
$ensure_storage('node', 'field_images', 'entity_reference', -1, ['target_type' => 'media']);
$ensure_storage('node', 'field_featured', 'boolean');

// Field instances.
$ensure_field('node', 'product', 'field_product_code', 'Product code');
$ensure_field('node', 'product', 'field_summary', 'Short summary');
$ensure_field('node', 'product', 'field_stone_family', 'Stone family', [
  'handler' => 'default:taxonomy_term',
  'handler_settings' => ['target_bundles' => ['stone_family' => 'stone_family'], 'auto_create' => FALSE],
]);
$ensure_field('node', 'product', 'field_color', 'Color', [
  'handler' => 'default:taxonomy_term',
  'handler_settings' => ['target_bundles' => ['color' => 'color'], 'auto_create' => FALSE],
]);
$ensure_field('node', 'product', 'field_finish', 'Finishes', [
  'handler' => 'default:taxonomy_term',
  'handler_settings' => ['target_bundles' => ['finish' => 'finish'], 'auto_create' => FALSE],
]);
$ensure_field('node', 'product', 'field_application', 'Applications', [
  'handler' => 'default:taxonomy_term',
  'handler_settings' => ['target_bundles' => ['application' => 'application'], 'auto_create' => FALSE],
]);
$ensure_field('node', 'product', 'field_dimensions', 'Available dimensions');
$ensure_field('node', 'product', 'field_thickness', 'Thickness');
$ensure_field('node', 'product', 'field_technical_data', 'Technical data');
$ensure_field('node', 'product', 'field_packaging', 'Packaging & supply notes');
$ensure_field('node', 'product', 'field_images', 'Product images', [
  'handler' => 'default:media',
  'handler_settings' => ['target_bundles' => ['image' => 'image']],
]);
$ensure_field('node', 'product', 'field_featured', 'Featured on home');

// ---------------------------------------------------------------------------
// 3. Form display for product.
// ---------------------------------------------------------------------------
$form = EntityFormDisplay::load('node.product.default');
if (!$form) {
  $form = EntityFormDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'product',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$weight = 0;
$form->setComponent('field_product_code', ['type' => 'string_textfield', 'weight' => $weight++]);
$form->setComponent('field_summary', ['type' => 'string_textarea', 'weight' => $weight++, 'settings' => ['rows' => 2]]);
$form->setComponent('field_stone_family', ['type' => 'options_select', 'weight' => $weight++]);
$form->setComponent('field_color', ['type' => 'options_select', 'weight' => $weight++]);
$form->setComponent('field_finish', ['type' => 'options_select', 'weight' => $weight++]);
$form->setComponent('field_application', ['type' => 'options_select', 'weight' => $weight++]);
$form->setComponent('field_dimensions', ['type' => 'string_textfield', 'weight' => $weight++]);
$form->setComponent('field_thickness', ['type' => 'string_textfield', 'weight' => $weight++]);
$form->setComponent('field_technical_data', ['type' => 'text_textarea', 'weight' => $weight++]);
$form->setComponent('field_packaging', ['type' => 'string_textarea', 'weight' => $weight++, 'settings' => ['rows' => 2]]);
$form->setComponent('field_images', ['type' => 'media_library_widget', 'weight' => $weight++]);
$form->setComponent('field_featured', ['type' => 'boolean_checkbox', 'weight' => $weight++]);
$form->save();
$messenger('Product form display configured.');

// ---------------------------------------------------------------------------
// 4. View display for product (full + teaser).
// ---------------------------------------------------------------------------
$view = EntityViewDisplay::load('node.product.default');
if (!$view) {
  $view = EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'product',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$w = 0;
$view->setComponent('field_images', ['type' => 'media_thumbnail', 'label' => 'hidden', 'weight' => $w++, 'settings' => ['image_style' => 'large', 'image_link' => '']]);
$view->setComponent('field_summary', ['type' => 'basic_string', 'label' => 'hidden', 'weight' => $w++]);
$view->setComponent('field_product_code', ['type' => 'string', 'label' => 'inline', 'weight' => $w++]);
$view->setComponent('field_stone_family', ['type' => 'entity_reference_label', 'label' => 'inline', 'weight' => $w++, 'settings' => ['link' => FALSE]]);
$view->setComponent('field_color', ['type' => 'entity_reference_label', 'label' => 'inline', 'weight' => $w++, 'settings' => ['link' => FALSE]]);
$view->setComponent('field_finish', ['type' => 'entity_reference_label', 'label' => 'inline', 'weight' => $w++, 'settings' => ['link' => FALSE]]);
$view->setComponent('field_application', ['type' => 'entity_reference_label', 'label' => 'inline', 'weight' => $w++, 'settings' => ['link' => FALSE]]);
$view->setComponent('field_dimensions', ['type' => 'string', 'label' => 'inline', 'weight' => $w++]);
$view->setComponent('field_thickness', ['type' => 'string', 'label' => 'inline', 'weight' => $w++]);
$view->setComponent('field_technical_data', ['type' => 'text_default', 'label' => 'above', 'weight' => $w++]);
$view->setComponent('field_packaging', ['type' => 'basic_string', 'label' => 'above', 'weight' => $w++]);
$view->removeComponent('field_featured');
$view->save();
$messenger('Product view display configured.');

// ---------------------------------------------------------------------------
// 5. Enable content translation for product + fields + pages + taxonomies.
// ---------------------------------------------------------------------------
$ctm = \Drupal::service('content_translation.manager');
$ctm->setEnabled('node', 'product', TRUE);
$ctm->setEnabled('node', 'page', TRUE);
foreach (['stone_family', 'color', 'finish', 'application'] as $vid) {
  $ctm->setEnabled('taxonomy_term', $vid, TRUE);
}
$messenger('Content translation enabled for product, page and vocabularies.');

drupal_flush_all_caches();
print "== Setup complete ==\n";
