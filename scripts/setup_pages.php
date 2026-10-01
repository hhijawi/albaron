<?php

/**
 * @file
 * Creates the Basic page content type and enables content translation.
 */

use Drupal\node\Entity\NodeType;

if (!NodeType::load('page')) {
  NodeType::create([
    'type' => 'page',
    'name' => 'Basic page',
    'description' => 'Static institutional page (About, Capabilities, Contact, Gallery).',
    'new_revision' => TRUE,
    'display_submitted' => FALSE,
  ])->save();
  node_add_body_field(NodeType::load('page'));
  print "Basic page content type created.\n";
}

$ctm = \Drupal::service('content_translation.manager');
$ctm->setEnabled('node', 'product', TRUE);
$ctm->setEnabled('node', 'page', TRUE);
foreach (['stone_family', 'color', 'finish', 'application'] as $vid) {
  $ctm->setEnabled('taxonomy_term', $vid, TRUE);
}
drupal_flush_all_caches();
print "Content translation enabled.\n";
