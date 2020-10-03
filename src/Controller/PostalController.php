<?php

namespace Drupal\representative_match\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * This controller renders the postal code form.
 */
class PostalController extends ControllerBase {

  /**
   * Display the form.
   */
  public function postal() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hi')
    ];
  }
}
