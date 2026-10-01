<?php

/**
 * @file
 * Import sample stone/marble products from the source image library.
 *
 * Idempotent: existing products (by title) are skipped.
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

$fs = \Drupal::service('file_system');
$source_dir = '/var/www/html/_source-assets/images';
$images = glob($source_dir . '/*.jpg');
sort($images);
if (!$images) {
  print "No source images found at $source_dir\n";
  return;
}

// Helper: term id by name in a vocabulary.
$tid = static function (string $vid, string $name): ?int {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => $vid, 'name' => $name]);
  return $terms ? (int) reset($terms)->id() : NULL;
};

// Helper: create a media image from a source path.
$make_media = static function (string $src, string $alt) use ($fs): ?int {
  if (!file_exists($src)) {
    return NULL;
  }
  $dir = 'public://products';
  $fs->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
  $dest = $dir . '/' . basename($src);
  $uri = $fs->copy($src, $dest, \Drupal\Core\File\FileExists::Replace);
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

// Product catalogue (sample). img = indexes into $images.
$catalogue = [
  ['Jerusalem Gold Limestone', 'AB-JG-01', 'Limestone', ['Golden', 'Beige'], ['Brushed', 'Honed'], ['Wall Cladding', 'Facades'], 'The iconic warm-gold limestone of the Holy Land — dense, weather-resistant and unmistakably Palestinian.', '600×300, 400×400, free length', '2–5 cm', [0, 1]],
  ['Bethlehem Cream', 'AB-BC-02', 'Limestone', ['Cream', 'Beige'], ['Polished', 'Honed'], ['Flooring', 'Wall Cladding'], 'A soft, even cream limestone prized for elegant interior floors and refined facades.', '600×600, 800×400', '2–3 cm', [2]],
  ['Hebron Beige', 'AB-HB-03', 'Limestone', ['Beige'], ['Bush-hammered', 'Split-face'], ['Facades', 'Landscaping'], 'A robust beige stone with rich texture, ideal for exterior cladding and hardscape.', '400×200, random ashlar', '3–6 cm', [3, 4]],
  ['Galilee Grey Marble', 'AB-GG-04', 'Marble', ['Grey'], ['Polished', 'Honed'], ['Flooring', 'Countertops'], 'A cool grey marble with subtle veining for contemporary interiors and surfaces.', '600×600, slabs to 2.6 m', '2–3 cm', [5]],
  ['Nablus Rose', 'AB-NR-05', 'Breccia', ['Rosso / Red', 'Beige'], ['Polished'], ['Countertops', 'Flooring'], 'A warm rose breccia with dramatic movement — a statement stone for feature surfaces.', 'Slabs, cut-to-size', '2–3 cm', [6]],
  ['Royal Botticino', 'AB-RB-06', 'Marble', ['Cream', 'Beige'], ['Polished', 'Honed'], ['Flooring', 'Stairs & Steps'], 'A classic light marble with gentle veining, versatile across floors and stairs.', '600×600, steps to spec', '2–4 cm', [7, 8]],
  ['Desert Travertine', 'AB-DT-07', 'Travertine', ['Beige', 'Cream'], ['Tumbled', 'Brushed'], ['Landscaping', 'Wall Cladding'], 'A naturally textured travertine that brings organic warmth to pools, patios and walls.', '610×406, pavers', '3 cm', [9]],
  ['Ramallah White', 'AB-RW-08', 'Limestone', ['White', 'Cream'], ['Honed', 'Sandblasted'], ['Facades', 'Wall Cladding'], 'A bright, clean limestone that lifts facades with a luminous, contemporary tone.', '600×300, panels', '2–4 cm', [10]],
  ['Olive Breccia', 'AB-OB-09', 'Breccia', ['Grey', 'Beige'], ['Polished', 'Bush-hammered'], ['Flooring', 'Facades'], 'An earthy breccia with layered character, equally at home indoors and out.', 'Slabs, cut-to-size', '2–3 cm', [11, 12]],
];

$created = 0;
foreach ($catalogue as $i => $def) {
  [$title, $code, $family, $colors, $finishes, $apps, $summary, $dims, $thick, $imgIdx] = $def;

  $existing = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['type' => 'product', 'title' => $title]);
  if ($existing) {
    print "Skip (exists): $title\n";
    continue;
  }

  // Build media from assigned images.
  $media_ids = [];
  foreach ($imgIdx as $idx) {
    if (isset($images[$idx])) {
      $mid = $make_media($images[$idx], $title);
      if ($mid) {
        $media_ids[] = $mid;
      }
    }
  }

  $fam_tid = $tid('stone_family', $family);
  $color_tids = array_filter(array_map(fn($c) => $tid('color', $c), $colors));
  $finish_tids = array_filter(array_map(fn($f) => $tid('finish', $f), $finishes));
  $app_tids = array_filter(array_map(fn($a) => $tid('application', $a), $apps));

  $node = Node::create([
    'type' => 'product',
    'title' => $title,
    'status' => 1,
    'langcode' => 'en',
    'field_product_code' => $code,
    'field_summary' => $summary,
    'field_stone_family' => $fam_tid ? ['target_id' => $fam_tid] : NULL,
    'field_color' => array_map(fn($t) => ['target_id' => $t], $color_tids),
    'field_finish' => array_map(fn($t) => ['target_id' => $t], $finish_tids),
    'field_application' => array_map(fn($t) => ['target_id' => $t], $app_tids),
    'field_dimensions' => $dims,
    'field_thickness' => $thick,
    'field_images' => array_map(fn($m) => ['target_id' => $m], $media_ids),
    'field_featured' => $i < 3 ? 1 : 0,
    'body' => [
      'value' => '<p>' . $summary . ' Al-Baron supplies ' . $title . ' in calibrated finishes and dimensions, packed to export standard for reliable delivery to international projects.</p>',
      'format' => 'basic_html',
    ],
  ]);
  $node->save();
  $created++;
  print "Created: $title (" . count($media_ids) . " images)\n";
}

print "Done. Created $created products.\n";
