<?php

namespace Drupal\albaron_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;

/**
 * Contact page: company details + inquiry webform.
 */
class ContactController extends ControllerBase {

  /**
   * Builds the contact page.
   */
  public function view(): array {
    $form = NULL;
    $webform = Webform::load('inquiry');
    if ($webform) {
      $form = $this->entityTypeManager()
        ->getViewBuilder('webform')
        ->view($webform);
    }

    return [
      '#theme' => 'albaron_contact',
      '#form' => $form,
      '#cache' => [
        'contexts' => ['languages:language_interface'],
        'tags' => ['config:webform.webform.inquiry'],
      ],
    ];
  }

}
