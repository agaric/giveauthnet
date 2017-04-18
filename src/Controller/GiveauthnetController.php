<?php

namespace Drupal\giveauthnet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Controller routines for give routes.
 */
class GiveauthnetController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a GiveauthnetController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Presents the give form.
   *
   * @param \Drupal\give\GiveauthnetFormInterface $give_form
   *   The give form to use.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   give form.
   */
  public function giveauthnetSitePage($give_form = NULL) {
    $config = $this->config('giveauthnet.settings');

    // Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($config->get('api_login_id'));
    $merchantAuthentication->setTransactionKey($config->get('transaction_key'));

    //create a transaction
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType("authCaptureTransaction");
    $transactionRequestType->setAmount("30.00");

    // Set Hosted Form options
    $setting1 = new AnetAPI\SettingType();
    $setting1->setSettingName("hostedPaymentButtonOptions");
    $setting1->setSettingValue("{\"text\": \"Donate\"}");
    $setting2 = new AnetAPI\SettingType();
    $setting2->setSettingName("hostedPaymentOrderOptions");
    $setting2->setSettingValue("{\"show\": false}");
    $setting3 = new AnetAPI\SettingType();
    $setting3->setSettingName("hostedPaymentReturnOptions");
    $thanks_url = Url::fromRoute('giveauthnet.thanks');
    $thanks_url->setAbsolute();
    $cancel_url = Url::fromRoute('giveauthnet.cancel');
    $cancel_url->setAbsolute();
    $settings = [
      'url' => $thanks_url->toString(),
      'cancelUrl' => $cancel_url->toString(),
      'showReceipt' => true
    ];
    $setting3->setSettingValue(json_encode($settings));
    // Build transaction request
    $request = new AnetAPI\GetHostedPaymentPageRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setTransactionRequest($transactionRequestType);
    $request->addToHostedPaymentSettings($setting1);
    $request->addToHostedPaymentSettings($setting2);
    $request->addToHostedPaymentSettings($setting3);

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
