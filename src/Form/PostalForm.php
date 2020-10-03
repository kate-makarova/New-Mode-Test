<?php

namespace Drupal\representative_match\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\representative_match\Service\RepresentativeApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This form provides the contacts of a local representative based on a postal code.
 * Relies on a service implementing RepresentativeApiInterface to retrieve the data.
 */
class PostalForm extends FormBase {

  /**
   * A service responsible for processing the request to the external API to retrieve the data.
   *
   * @var RepresentativeApiInterface
   */
  private $representative_api;

  /**
   * Overridden PostalForm constructor.
   *
   * @param RepresentativeApiInterface $representative_api A service responsible for processing the request
   * to the external API to retrieve the data.
   */
  public function __construct(RepresentativeApiInterface $representative_api)
  {
    $this->representative_api = $representative_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('representative_match.open_north_represent_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'representative_match_postal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['contacts'] = [
      '#type' => 'markup',
      '#markup' => '<div id="representative-match-postal-form-contacts"></div>',
    ];

    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your postal code'),
    ];

    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::retrieveContacts',
      ],
    ];

    $form['#theme'] = 'representative_match_postal_form';
    $form['#attached']['library'][] = "representative_match/postal-form";

    return $form;
  }

  /**
   * This validator assumes that the site is designed to handle only Canadian postal codes.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (preg_replace('/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ]) ?(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/',
        $form_state->getValue('postal_code'))) {
      $form_state->setErrorByName('postal_code', $this->t('The format of the postal code is incorrect.
       The correct formal for a Canadian postal code is X0X 0X0.'));
    }
  }

  public function retrieveContacts(array $form, FormStateInterface $form_state) {
    $candidates = $this->representative_api->getCandidateList($form_state->getValue('postal_code'));

    $response = new AjaxResponse();
    $response->addCommand(
      new InvokeCommand(
        '#representative-match-postal-form-contacts',
        'formTable',
        [$candidates])
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // This method is never called, but it has to be implemented.
  }
}
