<?php

namespace Drupal\representative_match\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    // Using standard validation with AJAX seams to be problematic, because there is
    // no way to clear error messages, and they show up after page reload.
  }

  /**
   * Validate the form and return the array of errors.
   * Since we only have one field in the form, there is no need to also return a field.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  private function customValidate(array &$form, FormStateInterface $form_state) {
    $errors = [];
    if (!preg_match('/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ]) ?(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/',
        $form_state->getValue('postal_code'), $matches) === 1
      or $matches[0] !== $form_state->getValue('postal_code')) {
      $errors[] = $this->t('The format of the postal code is incorrect.
       The correct formal for a Canadian postal code is X0X 0X0.');
    }
    return $errors;
  }

  /**
   * Retrieve contacts and send the data to the rendering JS function.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function retrieveContacts(array $form, FormStateInterface $form_state) {

    // Form validation.
    if($errors = $this->customValidate($form, $form_state)) {
      $response = new AjaxResponse();

      /** @var TranslatableMarkup $error */
      foreach($errors as $error) {
        $response->addCommand(
          new MessageCommand($error->render(), null, ['type' => 'error'])
        );
      }
      return $response;
    }

    // Form processing.
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
