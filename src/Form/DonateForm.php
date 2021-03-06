<?php

namespace Drupal\giveauthnet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkInviteForm.
 */
class DonateForm extends FormBase {

  /**
   * @var array
   */
  protected $tempStore;

  /**
   * DonateForm constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // By default the session is only initialized for logged in users, so we
    // need to save something in it in order to initialize for anonymou users.
    // https://drupal.stackexchange.com/a/163156/4362
    $_SESSION['is_anonymous_user'] = 1;
    $this->tempStore = $temp_store_factory->get('giveauthnet');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'give_authorize_net_donation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('giveauthnet.settings');

    $form['donate_message'] = [
      '#type' => 'markup',
      '#markup' => $config->get('donate_message'),
    ];

    $form['authorized_donation'] = [
      '#type' => 'fieldset',
      '#title' => ''
    ];

    // @todo Make the default value configurable.
    $form['authorized_donation']['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#field_prefix' => '$',
      '#default_value' => "",
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Donate'),
      '#button_type' => 'primary',
    ];
    $form['#cache']['max-age'] = 0;
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');

    if (!is_numeric($amount) || $amount <= 0) {
      $form_state->setErrorByName('amount', 'The donation must be a number and bigger than 0');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStore->set('amount', $form_state->getValue('amount'));
    $form_state->setRedirect('giveauthnet.confirm');
  }

}
