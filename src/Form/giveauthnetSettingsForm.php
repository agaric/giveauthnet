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

    $form['authorized_credentials'] = [
      '#type' => 'fieldset',
      '#title' => 'Authorized.net Credentials'
    ];

    $form['authorized_credentials']['api_login_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Login ID'),
      '#default_value' => $config->get('api_login_id'),
    );

    $form['authorized_credentials']['transaction_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Key'),
      '#default_value' => $config->get('transaction_key'),
    );

    $form['cancel_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('This message will be displayed in the cancelation page.'),
      '#descripton' => $this->t('If the user click in the "cancel" button in the Authirized.net page, will be redirected to a page with this message'),
      '#default_value' => $config->get('cancel_message'),
    );

    $form['thanks_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('This message will be displayed when the user finish their donation.'),
      '#descripton' => $this->t('After the donation the user will be redirected to a page with this message.'),
      '#default_value' => $config->get('thanks_message'),
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
      ->set('cancel_message', $form_state->getValue('cancel_message'))
      ->set('thanks_message', $form_state->getValue('thanks_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}