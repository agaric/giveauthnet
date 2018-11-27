<?php

namespace Drupal\giveauthnet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Controller routines for give routes.
 */
class GiveauthnetController extends ControllerBase {

  /**
   * @var array
   */
  protected $tempStore;

  /**
   * Constructs a GiveauthnetController object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
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
   * Presents the give form.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form as render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   give form.
   */
  public function confirm() {
    $config = $this->config('giveauthnet.settings');

    $amount = $this->tempStore->get('amount');

    // If the amount is negative, not a number or equal to zero lets redirect
    // the user to the donation page.
    if (!is_numeric($amount) || $amount <= 0) {
      drupal_set_message('Something went wrong, please fill again the amount', 'warning');
      return $this->redirect('giveauthnet.donate');
    }

    // Delete the amount value from the session.
    $this->tempStore->delete('amount');

    // Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($config->get('api_login_id'));
    $merchantAuthentication->setTransactionKey($config->get('transaction_key'));

    //create a transaction
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType("authCaptureTransaction");
    $transactionRequestType->setAmount($amount);

    // Set Hosted Form options
    $setting1 = new AnetAPI\SettingType();
    $setting1->setSettingName("hostedPaymentButtonOptions");
    $setting1->setSettingValue("{\"text\": \"Donate\"}");
    $setting2 = new AnetAPI\SettingType();
    $setting2->setSettingName("hostedPaymentOrderOptions");
    $setting2->setSettingValue("{\"show\": false}");
    $setting3 = new AnetAPI\SettingType();
    $setting3->setSettingName("hostedPaymentReturnOptions");
    $setting4 = new AnetApI\SettingType();
    $setting4->setSettingName("hostedPaymentCustomerOptions");
    $setting4->setSettingValue(json_encode(["showEmail" => true, "requiredEmail" => true]));
    $thanks_url = Url::fromRoute('giveauthnet.thanks');
    $thanks_url->setAbsolute();
    $cancel_url = Url::fromRoute('giveauthnet.cancel');
    $cancel_url->setAbsolute();
    $settings = [
      'url' => $thanks_url->toString(),
      'cancelUrl' => $cancel_url->toString(),
      'showReceipt' => true,
    ];
    $setting3->setSettingValue(json_encode($settings));
    // Build transaction request
    $request = new AnetAPI\GetHostedPaymentPageRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setTransactionRequest($transactionRequestType);
    $request->addToHostedPaymentSettings($setting1);
    $request->addToHostedPaymentSettings($setting2);
    $request->addToHostedPaymentSettings($setting3);
    $request->addToHostedPaymentSettings($setting4);

    //execute request
    $controller = new AnetController\GetHostedPaymentPageController($request);
    $sandbox = $config->get('test_enviroment', 0);
    if ($sandbox) {
      $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
    }
    else {
      $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    }


    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") ) {
      $token = $response->getToken();
    }
    else {
      $token = '';
      drupal_set_message("ERROR :  Failed to get hosted payment page token", 'error');
      $errorMessages = $response->getMessages()->getMessage();
      drupal_set_message("RESPONSE : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText(), 'warning');
    }

    return [
      '#theme' => 'authnet_link_page',
      '#authnet_token' => $token,
      '#amount' => $amount,
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Cancel page.
   */
  public function cancel() {

    return [
      '#theme' => 'authnet_cancel_page'
    ];
  }


  /**
   * Thanks page.
   */
  public function thanks() {
    return [
      '#theme' => 'authnet_thanks_page'
    ];
  }

}
