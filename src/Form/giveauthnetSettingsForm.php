<?php

namespace Drupal\giveauthnet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class giveauthnetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'give_authorize_net';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'giveauthnet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('giveauthnet.settings');

    $form['api_login_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Login ID'),
      '#default_value' => $config->get('api_login_id'),
    );

    $form['transaction_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Key'),
      '#default_value' => $config->get('transaction_key'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $config = $this->config('giveauthnet.settings');
    $config->set('api_login_id', $form_state->getValue('api_login_id'))
      ->set('transaction_key', $form_state->getValue('transaction_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}