<?php

/**
 * @file
 * Ensure a body field exists on the Basic page content type.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

if (!FieldStorageConfig::loadByName('node', 'body')) {
  FieldStorageConfig::create([
    'field_name' => 'body',
    'entity_type' => 'node',
    'type' => 'text_with_summary',
    'cardinality' => 1,
  ])->save();
  print "body storage created\n";
}

if (!FieldConfig::loadByName('node', 'page', 'body')) {
  FieldConfig::create([
    'field_name' => 'body',
    'entity_type' => 'node',
    'bundle' => 'page',
    'label' => 'Body',
    'settings' => ['display_summary' => TRUE],
  ])->save();
  print "body instance created\n";
}

EntityFormDisplay::load('node.page.default')
  ?->setComponent('body', ['type' => 'text_textarea_with_summary', 'weight' => 1])
  ->save();

$vd = EntityViewDisplay::load('node.page.default');
if (!$vd) {
  $vd = EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'page',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$vd->setComponent('body', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 1])->save();

drupal_flush_all_caches();
print "Body field ready on page.\n";
