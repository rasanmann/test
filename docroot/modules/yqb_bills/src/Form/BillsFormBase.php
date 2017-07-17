<?php
/**
 * @file
 * Contains \Drupal\yqb_bills\Form\FlightPlannerForm.
 */

namespace Drupal\yqb_bills\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\moneris\Render\MonerisFrameRenderer;
use Drupal\moneris\Connector\MonerisConnector;

abstract class BillsFormBase extends FormBase {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;


  /**
   * Constructs a \Drupal\demo\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('multistep_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
      $_SESSION['multistep_form_holds_session'] = true;
      $this->sessionManager->start();
    }

    $form = [];

    $form['#attributes']['class'][] = 'form';
    $form['#attributes']['class'][] = 'page';
    $form['#attributes']['class'][] = 'content';

    return $form;
  }

  /**
   * Saves the data from the multistep form.
   */
  protected function saveData() {
    // Logic for saving data goes here...
    $this->deleteStore();
    drupal_set_message($this->t('The form has been saved.'));

  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multistep form.
   */
  protected function deleteStore() {
    $keys = ['name', 'email', 'age', 'location'];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }

  protected function sendAdminEmail() {
    // Send confirmation email
    $mailManager = \Drupal::service('plugin.manager.mail');

    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    $config = \Drupal::config('yqb_bills.settings');

    $to = $config->get('yqb_bills.recipients');

    $segments = [
        '<p>Bonjour,</p>',
        '<p>Une facture a été payée</p>',
        '<h2>Informations</h2>',
    ];

    $fields = [
        'first_name' => $this->t('Prénom'),
        'last_name' => $this->t('Nom'),
        'company_name' => $this->t('Nom de votre entreprise'),
        'invoice_number' => $this->t('Numéro de facture'),
        'client_id' => $this->t('Numéro de client'),
        'amount' => $this->t('Montant à débourser'),
    ];

    foreach($fields as $key => $value) {
      $segments[] = '<p>'. $value .  ' : ' . $this->store->get($key) . '</p>';
    }

    $data = str_replace(['"', '{', '}'], '', json_encode(json_decode($this->store->get('transaction_response'))->receipt, JSON_PRETTY_PRINT));

    $segments[] = '<h2>Reçu</h2>';
    $segments[] = '<pre>' . $data . '</pre>';

    $params = [
        'reference' => $this->store->get('reference_number'),
        'body' => implode('', $segments)
    ];

    $result = $mailManager->mail('yqb_bills', 'pay_bill', $to, $langcode, $params, NULL, true);
  }
}

?>