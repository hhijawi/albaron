<?php

/**
 * @file
 * Sets up the News (news) and Projects (project) content types, their fields,
 * form/view displays, pathauto aliases, translation and sample content.
 *
 * Run with: ddev drush php:script scripts/setup_news_projects.php
 * Idempotent: safe to run multiple times.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\pathauto\Entity\PathautoPattern;

$messenger = static function (string $m): void {
  print "  - $m\n";
};

$ensure_storage = static function (string $field_name, string $type, int $cardinality = 1, array $settings = []) use ($messenger): void {
  if (!FieldStorageConfig::loadByName('node', $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $settings,
    ])->save();
    $messenger("Field storage created: $field_name ($type)");
  }
};

$ensure_field = static function (string $bundle, string $field_name, string $label, array $settings = [], bool $required = FALSE) use ($messenger): void {
  if (!FieldConfig::loadByName('node', $bundle, $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
      'required' => $required,
      'settings' => $settings,
    ])->save();
    $messenger("Field instance created: $bundle.$field_name");
  }
};

print "== Al-Baron News & Projects setup ==\n";

// ---------------------------------------------------------------------------
// 1. Content types.
// ---------------------------------------------------------------------------
if (!NodeType::load('news')) {
  NodeType::create([
    'type' => 'news',
    'name' => 'News',
    'description' => 'Company news, announcements and trade-show updates.',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  node_add_body_field(NodeType::load('news'));
  $messenger('Content type created: news');
}
if (!NodeType::load('project')) {
  NodeType::create([
    'type' => 'project',
    'name' => 'Project',
    'description' => 'Completed project / case study showcasing Al-Baron stone.',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  node_add_body_field(NodeType::load('project'));
  $messenger('Content type created: project');
}

// ---------------------------------------------------------------------------
// 2. Field storages. field_summary and field_images already exist (reused).
// ---------------------------------------------------------------------------
$ensure_storage('field_location', 'string');
$ensure_storage('field_year', 'string');
$ensure_storage('field_stone_used', 'entity_reference', -1, ['target_type' => 'node']);

// ---------------------------------------------------------------------------
// 3. Field instances.
// ---------------------------------------------------------------------------
// News: short summary + featured image (reused shared fields).
$ensure_field('news', 'field_summary', 'Short summary');
$ensure_field('news', 'field_images', 'Image', [
  'handler' => 'default:media',
  'handler_settings' => ['target_bundles' => ['image' => 'image']],
]);

// Project: summary, location, year, stone used, gallery images.
$ensure_field('project', 'field_summary', 'Short summary');
$ensure_field('project', 'field_location', 'Location');
$ensure_field('project', 'field_year', 'Year completed');
$ensure_field('project', 'field_stone_used', 'Stone used', [
  'handler' => 'default:node',
  'handler_settings' => ['target_bundles' => ['product' => 'product'], 'auto_create' => FALSE],
]);
$ensure_field('project', 'field_images', 'Project images', [
  'handler' => 'default:media',
  'handler_settings' => ['target_bundles' => ['image' => 'image']],
]);

// ---------------------------------------------------------------------------
// 4. Form displays.
// ---------------------------------------------------------------------------
$news_form = EntityFormDisplay::load('node.news.default')
  ?: EntityFormDisplay::create(['targetEntityType' => 'node', 'bundle' => 'news', 'mode' => 'default', 'status' => TRUE]);
$w = 0;
$news_form->setComponent('field_summary', ['type' => 'string_textarea', 'weight' => $w++, 'settings' => ['rows' => 2]]);
$news_form->setComponent('field_images', ['type' => 'media_library_widget', 'weight' => $w++]);
$news_form->setComponent('body', ['type' => 'text_textarea_with_summary', 'weight' => $w++]);
$news_form->save();
$messenger('News form display configured.');

$proj_form = EntityFormDisplay::load('node.project.default')
  ?: EntityFormDisplay::create(['targetEntityType' => 'node', 'bundle' => 'project', 'mode' => 'default', 'status' => TRUE]);
$w = 0;
$proj_form->setComponent('field_summary', ['type' => 'string_textarea', 'weight' => $w++, 'settings' => ['rows' => 2]]);
$proj_form->setComponent('field_location', ['type' => 'string_textfield', 'weight' => $w++]);
$proj_form->setComponent('field_year', ['type' => 'string_textfield', 'weight' => $w++]);
$proj_form->setComponent('field_stone_used', ['type' => 'entity_reference_autocomplete', 'weight' => $w++]);
$proj_form->setComponent('field_images', ['type' => 'media_library_widget', 'weight' => $w++]);
$proj_form->setComponent('body', ['type' => 'text_textarea_with_summary', 'weight' => $w++]);
$proj_form->save();
$messenger('Project form display configured.');

// ---------------------------------------------------------------------------
// 5. View displays (custom templates render the detail; keep displays tidy).
// ---------------------------------------------------------------------------
$news_view = EntityViewDisplay::load('node.news.default')
  ?: EntityViewDisplay::create(['targetEntityType' => 'node', 'bundle' => 'news', 'mode' => 'default', 'status' => TRUE]);
$news_view->setComponent('body', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 0]);
$news_view->removeComponent('field_summary');
$news_view->removeComponent('field_images');
$news_view->save();

$proj_view = EntityViewDisplay::load('node.project.default')
  ?: EntityViewDisplay::create(['targetEntityType' => 'node', 'bundle' => 'project', 'mode' => 'default', 'status' => TRUE]);
$proj_view->setComponent('body', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 0]);
foreach (['field_summary', 'field_location', 'field_year', 'field_stone_used', 'field_images'] as $c) {
  $proj_view->removeComponent($c);
}
$proj_view->save();
$messenger('View displays configured.');

// ---------------------------------------------------------------------------
// 6. Content translation.
// ---------------------------------------------------------------------------
$ctm = \Drupal::service('content_translation.manager');
$ctm->setEnabled('node', 'news', TRUE);
$ctm->setEnabled('node', 'project', TRUE);
$messenger('Content translation enabled for news and project.');

// ---------------------------------------------------------------------------
// 7. Pathauto patterns.
// ---------------------------------------------------------------------------
$uuid = \Drupal::service('uuid');
foreach (['news' => 'news/[node:title]', 'project' => 'projects/[node:title]'] as $bundle => $pattern) {
  if (!PathautoPattern::load($bundle)) {
    $cuid = $uuid->generate();
    PathautoPattern::create([
      'id' => $bundle,
      'label' => ucfirst($bundle),
      'type' => 'canonical_entities:node',
      'pattern' => $pattern,
      'selection_criteria' => [
        $cuid => [
          'id' => 'entity_bundle:node',
          'bundles' => [$bundle => $bundle],
          'negate' => FALSE,
          'context_mapping' => ['node' => 'node'],
          'uuid' => $cuid,
        ],
      ],
      'weight' => 0,
    ])->save();
    $messenger("Pathauto pattern: $bundle");
  }
}

// ---------------------------------------------------------------------------
// 8. Sample content.
// ---------------------------------------------------------------------------
$fs = \Drupal::service('file_system');
$images = glob('/var/www/html/_source-assets/images/*.jpg');
sort($images);

$make_media = static function (?string $src, string $alt) use ($fs): ?int {
  if (!$src || !file_exists($src)) {
    return NULL;
  }
  $dir = 'public://editorial';
  $fs->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
  $dest = $dir . '/' . basename($src);
  $uri = $fs->copy($src, $dest, FileExists::Replace);
  $file = File::create(['uri' => $uri, 'status' => 1]);
  $file->save();
  $media = Media::create([
    'bundle' => 'image',
    'name' => $alt,
    'field_media_image' => ['target_id' => $file->id(), 'alt' => $alt],
    'status' => 1,
  ]);
  $media->save();
  return (int) $media->id();
};

// --- News posts: [title, summary, body, image index, days-ago]. ---
$news = [
  [
    'Al-Baron Exhibits at Marmomac 2026 in Verona',
    'Meet our team at the world’s leading natural stone fair and discover our latest Palestinian limestone and marble finishes.',
    '<p>Al-Baron is proud to return to <strong>Marmomac</strong> in Verona, Italy — the global reference event for natural stone. Visitors will see our full export range of Jerusalem Gold limestone, Bethlehem Cream and Galilee Grey marble, alongside new calibrated finishes developed for international facade and flooring projects.</p><p>Our team will be on hand to discuss specifications, custom sizing and export logistics for buyers across Europe, the Gulf and North America.</p>',
    0,
    6,
  ],
  [
    'New Brushed & Sandblasted Finishes Added to the Export Range',
    'We have expanded our surface-finishing capacity with new textured finishes for exterior cladding and landscaping.',
    '<p>Responding to growing demand from architects, Al-Baron has commissioned additional finishing lines at our Bethlehem facility. The expansion adds consistent <strong>brushed</strong> and <strong>sandblasted</strong> textures across our limestone range, ideal for high-traffic flooring, facades and hardscape.</p><p>All new finishes are held to our documented colour and dimensional tolerances and are available for sampling on request.</p>',
    4,
    20,
  ],
  [
    'EU4Trade Partnership Powers Al-Baron’s Global Expansion',
    'With support from PalTrade and the EU, Al-Baron is strengthening its digital presence and export readiness.',
    '<p>Through the <strong>EU4Trade</strong> programme and in partnership with PalTrade, Al-Baron has invested in a new bilingual digital platform, catalogue and quality documentation to better serve international buyers.</p><p>The initiative reinforces our commitment to reliable, export-standard supply of premium Palestinian natural stone to projects worldwide.</p>',
    8,
    45,
  ],
];

$created_news = 0;
foreach ($news as $def) {
  [$title, $summary, $body, $imgIdx, $daysAgo] = $def;
  $existing = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['type' => 'news', 'title' => $title]);
  if ($existing) {
    print "Skip news (exists): $title\n";
    continue;
  }
  $mid = $make_media($images[$imgIdx] ?? NULL, $title);
  Node::create([
    'type' => 'news',
    'title' => $title,
    'status' => 1,
    'langcode' => 'en',
    'created' => \Drupal::time()->getRequestTime() - ($daysAgo * 86400),
    'field_summary' => $summary,
    'field_images' => $mid ? [['target_id' => $mid]] : [],
    'body' => ['value' => $body, 'format' => 'basic_html'],
  ])->save();
  $created_news++;
  print "Created news: $title\n";
}

require __DIR__ . '/setup_palestinian_projects.php';

// Generate aliases for the new content.
$generator = \Drupal::service('pathauto.generator');
$new_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', ['news', 'project'], 'IN')
  ->execute();
foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($new_ids) as $node) {
  $generator->updateEntityAlias($node, 'update');
}

drupal_flush_all_caches();
print "== News & Projects setup complete (news: $created_news, projects: $created_proj) ==\n";
