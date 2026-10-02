<?php

/**
 * @file
 * Create the Al-Baron export inquiry webform.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\webform\Entity\Webform;

if (Webform::load('inquiry')) {
  print "Webform 'inquiry' already exists.\n";
  return;
}

$elements = [
  'row_name' => [
    '#type' => 'webform_flexbox',
    'name' => [
      '#type' => 'textfield',
      '#title' => 'Full name',
      '#required' => TRUE,
    ],
    'company' => [
      '#type' => 'textfield',
      '#title' => 'Company',
    ],
  ],
  'row_contact' => [
    '#type' => 'webform_flexbox',
    'email' => [
      '#type' => 'email',
      '#title' => 'Email',
      '#required' => TRUE,
    ],
    'phone' => [
      '#type' => 'tel',
      '#title' => 'Phone / WhatsApp',
    ],
  ],
  'country' => [
    '#type' => 'textfield',
    '#title' => 'Country',
    '#required' => TRUE,
  ],
  'row_product' => [
    '#type' => 'webform_flexbox',
    'product' => [
      '#type' => 'textfield',
      '#title' => 'Product of interest',
    ],
    'finish' => [
      '#type' => 'textfield',
      '#title' => 'Preferred finish',
    ],
  ],
  'quantity' => [
    '#type' => 'textfield',
    '#title' => 'Approximate quantity / project size',
  ],
  'message' => [
    '#type' => 'textarea',
    '#title' => 'Your message',
    '#required' => TRUE,
  ],
  'consent' => [
    '#type' => 'checkbox',
    '#title' => 'I consent to Al-Baron contacting me about this inquiry. My details will be used only for this purpose.',
    '#required' => TRUE,
  ],
  'actions' => [
    '#type' => 'webform_actions',
    '#submit__label' => 'Send Inquiry',
  ],
];

$webform = Webform::create([
  'id' => 'inquiry',
  'title' => 'Export Inquiry',
  'description' => 'Al-Baron export sales inquiry form.',
  'category' => 'Al-Baron',
  'elements' => Yaml::encode($elements),
  'settings' => Webform::getDefaultSettings() + [
    'confirmation_type' => 'message',
    'confirmation_message' => 'Thank you — your inquiry has been received. Our export team will contact you shortly.',
  ],
]);
$webform->save();

// Add an email notification handler to the company inbox.
$handler_manager = \Drupal::service('plugin.manager.webform.handler');
$handler = $handler_manager->createInstance('email');
$handler->setConfiguration([
  'id' => 'email',
  'handler_id' => 'company_notification',
  'label' => 'Company notification',
  'status' => TRUE,
  'weight' => 0,
  'settings' => [
    'to_mail' => 'albaronstone@gmail.com',
    'from_mail' => '[site:mail]',
    'subject' => 'New export inquiry from [webform_submission:values:name]',
    'body' => "[webform_submission:values]",
  ],
]);
$webform->addWebformHandler($handler);
$webform->save();

print "Webform 'inquiry' created with email handler.\n";
