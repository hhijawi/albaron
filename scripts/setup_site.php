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
// ---------------------------------------------------------------------------
$pages = [
  'about' => [
    'title' => 'About Al-Baron',
    'alias' => '/about',
    'body' => '<p class="lead">From the historic quarries around Bethlehem, Al-Baron transforms raw Palestinian limestone and marble into precision-finished materials trusted on projects across the world.</p>'
      . '<p>For over two decades we have combined traditional stone craft with modern processing to deliver consistent color, calibrated thickness and reliable export quality. Our vertically integrated operation — from extraction through cutting, finishing and export packing — gives buyers a single, accountable partner.</p>'
      . '<h3>Our Values</h3><p>Authenticity of Palestinian stone, precision in every finish, and dependable delivery. We publish only verified capabilities and specifications, so what you specify is exactly what arrives on site.</p>'
      . '<h3>Export Markets</h3><p>Al-Baron serves architects, developers and stone traders across the Gulf, Europe and beyond, supporting facades, flooring, cladding and bespoke architectural elements.</p>',
  ],
  'capabilities' => [
    'title' => 'Capabilities & Certifications',
    'alias' => '/capabilities',
    'body' => '<p class="lead">A vertically integrated stone operation built for demanding international specifications.</p>'
      . '<h3>Production Workflow</h3><p>Selection at the quarry, block sawing, calibration, surface finishing and quality inspection — each stage is controlled to hold color, dimension and finish tolerances across large orders.</p>'
      . '<h3>Machinery & Capacity</h3><p>Modern gang saws, bridge cutters and finishing lines enable a broad range of formats, from standard tiles to large slabs and cut-to-size architectural pieces.</p>'
      . '<h3>Quality Assurance</h3><p>Documented inspection of color consistency, dimensional accuracy and surface finish before packing. Custom finishes and sizes available on request.</p>'
      . '<h3>Packing & Export Readiness</h3><p>Secure crating and palletizing to export standard, with experienced logistics for reliable international delivery.</p>'
      . '<h3>Certifications</h3><p>Certificates and technical documentation are provided on request and published here once verified.</p>',
  ],
];

foreach ($pages as $key => $def) {
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
  ['title' => 'Products', 'uri' => 'internal:/products', 'weight' => -40],
  ['title' => 'Gallery', 'uri' => 'internal:/gallery', 'weight' => -30],
  ['title' => 'Capabilities', 'uri' => 'internal:/capabilities', 'weight' => -20],
  ['title' => 'About', 'uri' => 'internal:/about', 'weight' => -10],
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
