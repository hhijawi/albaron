<?php

/**
 * @file
 * Site assembly: front page, blocks, menu, static pages, path aliases.
 */

use Drupal\block\Entity\Block;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\pathauto\Entity\PathautoPattern;

// ---------------------------------------------------------------------------
// 1. Front page -> /home.
// ---------------------------------------------------------------------------
\Drupal::configFactory()->getEditable('system.site')
  ->set('page.front', '/home')
  ->save();
print "Front page set to /home\n";

// ---------------------------------------------------------------------------
// 2. Blocks for the albaron theme.
// ---------------------------------------------------------------------------
$blocks = [
  'albaron_content' => ['plugin' => 'system_main_block', 'region' => 'content', 'weight' => 0],
  'albaron_messages' => ['plugin' => 'system_messages_block', 'region' => 'highlighted', 'weight' => -10],
  'albaron_local_tasks' => ['plugin' => 'local_tasks_block', 'region' => 'content_top', 'weight' => -10],
];
foreach ($blocks as $id => $def) {
  if (!Block::load($id)) {
    Block::create([
      'id' => $id,
      'theme' => 'albaron',
      'region' => $def['region'],
      'plugin' => $def['plugin'],
      'weight' => $def['weight'],
      'settings' => ['label' => $id, 'label_display' => '0'],
      'visibility' => [],
    ])->save();
    print "Block placed: $id\n";
  }
}

// ---------------------------------------------------------------------------
// 3. Static pages.
//
// About and Capabilities are now rendered by custom controllers
// (AboutController / CapabilitiesController at /company and /capabilities), not
// Basic page nodes — so no page nodes are created here. Leaving this map empty
// keeps the script idempotent without resurrecting deleted duplicate nodes.
// ---------------------------------------------------------------------------
$pages = [];
  $existing = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['type' => 'page', 'title' => $def['title']]);
  if ($existing) {
    print "Page exists: {$def['title']}\n";
    continue;
  }
  $node = Node::create([
    'type' => 'page',
    'title' => $def['title'],
    'langcode' => 'en',
    'status' => 1,
    'body' => ['value' => $def['body'], 'format' => 'basic_html'],
    'path' => ['alias' => $def['alias'], 'langcode' => 'en'],
  ]);
  $node->save();
  print "Page created: {$def['title']} -> {$def['alias']}\n";
}

// ---------------------------------------------------------------------------
// 4. Main menu links.
// ---------------------------------------------------------------------------
$links = [
  ['title' => 'Home', 'uri' => 'internal:/home', 'weight' => -50],
  ['title' => 'News', 'uri' => 'internal:/news', 'weight' => -45],
  ['title' => 'Products', 'uri' => 'internal:/products', 'weight' => -40],
  ['title' => 'Gallery', 'uri' => 'internal:/gallery', 'weight' => -30],
  ['title' => 'Projects', 'uri' => 'internal:/projects', 'weight' => -25],
  ['title' => 'Capabilities', 'uri' => 'internal:/capabilities', 'weight' => -20],
  ['title' => 'Company', 'uri' => 'internal:/company', 'weight' => -10],
  ['title' => 'Contact', 'uri' => 'internal:/contact', 'weight' => 0],
];
foreach ($links as $def) {
  $existing = \Drupal::entityTypeManager()->getStorage('menu_link_content')
    ->loadByProperties(['title' => $def['title'], 'menu_name' => 'main']);
  if ($existing) {
    continue;
  }
  MenuLinkContent::create([
    'title' => $def['title'],
    'link' => ['uri' => $def['uri']],
    'menu_name' => 'main',
    'weight' => $def['weight'],
    'expanded' => TRUE,
  ])->save();
  print "Menu link: {$def['title']}\n";
}

// ---------------------------------------------------------------------------
// 5. Pathauto patterns for tidy product URLs.
// ---------------------------------------------------------------------------
$uuid = \Drupal::service('uuid');
if (!PathautoPattern::load('product')) {
  $criteria_uuid = $uuid->generate();
  PathautoPattern::create([
    'id' => 'product',
    'label' => 'Product',
    'type' => 'canonical_entities:node',
    'pattern' => 'products/[node:title]',
    'selection_criteria' => [
      $criteria_uuid => [
        'id' => 'entity_bundle:node',
        'bundles' => ['product' => 'product'],
        'negate' => FALSE,
        'context_mapping' => ['node' => 'node'],
        'uuid' => $criteria_uuid,
      ],
    ],
    'weight' => 0,
  ])->save();
  print "Pathauto pattern: product\n";
}

// Generate aliases for existing products.
$generator = \Drupal::service('pathauto.generator');
$product_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'product')
  ->execute();
foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($product_ids) as $node) {
  $generator->updateEntityAlias($node, 'update');
}
print "Product aliases generated (" . count($product_ids) . ").\n";

drupal_flush_all_caches();
print "Site assembly complete.\n";
