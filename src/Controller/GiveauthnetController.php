<?php

namespace Drupal\giveauthnet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\give\GiveFormInterface;
use Drupal\give\DonationInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
  public function giveauthnetSitePage(GiveauthnetFormInterface $give_form = NULL) {
    $config = $this->config('giveauthnet.settings');
    // Common setup for API credentials
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($config->get('giveauthnet_login_id'));
    $merchantAuthentication->setTransactionKey(\SampleCode\Constants::MERCHANT_TRANSACTION_KEY);
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
    $thanks_url = $GLOBALS['base_secure_url'] . 'receipt';
    $cancel_url = $GLOBALS['base_secure_url'] . 'cancel';
    $setting3->setSettingValue("{\"url\": \"$thanks_url\", \"cancelUrl\": \"$cancel_url\", \"showReceipt\": true}");
    // Build transaction request
    $request = new AnetAPI\GetHostedPaymentPageRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setTransactionRequest($transactionRequestType);
    $request->addToHostedPaymentSettings($setting1);
    $request->addToHostedPaymentSettings($setting2);
    $request->addToHostedPaymentSettings($setting3);

    //execute request
    $controller = new AnetController\GetHostedPaymentPageController($request);
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
    {
      drupal_set_message($response->getToken(), 'status');
    }
    else
    {
      drupal_set_message("ERROR :  Failed to get hosted payment page token", 'error');
      $errorMessages = $response->getMessages()->getMessage();
      drupal_set_message("RESPONSE : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText(), 'warning');
    }
    return $response;

  }


}
