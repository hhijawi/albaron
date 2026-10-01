<?php

/**
 * @file
 * Create responsive, WebP-optimised image styles for Al-Baron.
 */

use Drupal\image\Entity\ImageStyle;

$styles = [
  'product_card' => [
    ['id' => 'image_scale_and_crop', 'data' => ['width' => 800, 'height' => 600, 'anchor' => 'center-center']],
  ],
  'product_wide' => [
    ['id' => 'image_scale_and_crop', 'data' => ['width' => 1600, 'height' => 1100, 'anchor' => 'center-center']],
  ],
  'product_thumb' => [
    ['id' => 'image_scale_and_crop', 'data' => ['width' => 400, 'height' => 400, 'anchor' => 'center-center']],
  ],
  'gallery' => [
    ['id' => 'image_scale', 'data' => ['width' => 1000, 'upscale' => FALSE]],
  ],
];

foreach ($styles as $name => $effects) {
  $style = ImageStyle::load($name);
  if (!$style) {
    $style = ImageStyle::create(['name' => $name, 'label' => ucwords(str_replace('_', ' ', $name))]);
  }
  // Clear existing effects to keep idempotent.
  foreach ($style->getEffects() as $effect) {
    $style->deleteImageEffect($effect);
  }
  foreach ($effects as $effect) {
    $style->addImageEffect($effect);
  }
  // Convert to WebP last for smaller, faster images.
  $style->addImageEffect(['id' => 'image_convert', 'data' => ['extension' => 'webp']]);
  $style->save();
  print "Image style ready: $name\n";
}
print "Image styles complete.\n";
