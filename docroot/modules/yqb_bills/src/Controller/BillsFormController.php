<?php
/**
 * @file
 * Contains \Drupal\yqb_flight_planner\Controller\FlightPlannerController.
 */

namespace Drupal\yqb_bills\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the ParkingBooker module.
 */
class BillsFormController extends ControllerBase
{

    /**
     * @return array
     *   A simple renderable array.
     */
    public function index() {

//        $information = [
//            '#type' => 'html_tag',
//            '#tag' => 'p',
//            '#value' => $this->t("Aéroport de Québec inc. (AQi), gestionnaire de l'aéroport international Jean-Lesage de Québec, met à votre disposition un service gratuit de paiement en ligne qui vous permet d'acquitter vos factures en tout temps et en toute sécurité (maximum de 5000$) par Visa, MasterCard ou American Express. Pour utiliser le service, il vous suffit de compléter les champs suivants&nbsp;:"),
//        ];

        $information = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("TESTNous éprouvons actuellement des difficultés techniques avec le paiement de facture sur notre site <a href='/'>aeroportdequebec.com</a>. Nous travaillons à rétablir le service le plus rapidement possible. Il est possible d’effectuer des paiements en contactant par téléphone les Comptes recevables de l’Aéroport international Jean-Lesage de Québec au 1 418 640-2700 poste 2763. Merci de votre compréhension."),
        ];

        $psStoreId = [
            '#type' => 'hidden',
            '#name' => 'ps_store_id',
            '#value' => 'HEJGN34601'
        ];

        $hppKey = [
            '#type' => 'hidden',
            '#name' => 'hpp_key',
            '#value' => 'hpOUJB91ZLFF'
        ];

        $lang = [
            '#type' => 'hidden',
            '#name' => 'lang',
            '#value' => \Drupal::languageManager()->getCurrentLanguage()->getId() . '-ca'
        ];

        $fields = [
            'bill_last_name' => $this->t("Nom"),
            'bill_first_name' => $this->t("Prénom"),
            'email' => $this->t("Courriel"),
            'bill_company_name' => $this->t("Nom de votre entreprise"),
            'order_id' => $this->t("Numéro de facture"),
            'cust_id' => $this->t("Numéro de client"),
            'charge_total' => $this->t("Montant à débourser (Entrer les décimales Ex.: 20.00)"),
        ];

        $form = [
            '#type' => 'form',
            '#attributes' => [
                'class' => ['col-sm-6'],
                'action' => 'https://www3.moneris.com/HPPDP/index.php',
                'method' => 'post',
                'target' => 'results',
            ],
            'information' => $information,
            'ps_store_id' => $psStoreId,
            'hpp_key' => $hppKey,
            'lang' => $lang,
        ];

//        foreach ($fields as $name => $label) {
//            $form[$name] = [
//                '#type' => 'textfield',
//                '#title' => $label,
//                '#name' => $name,
//                '#required' => true,
//            ];
//        }
//
//        $form['note'] = [
//          '#type' => 'checkbox',
//          '#name' => 'note',
//          '#title' => $this->t('Je désire recevoir factures et états de compte par courriel.'),
//          '#default_value' => $this->t("Factures et états de compte par courriel."),
//          '#return_value' => $this->t("Factures et états de compte par courriel."),
//        ];

//        $form['actions'] = [
//            '#type' => 'button',
//            '#value' => $this->t("Soumettre"),
//        ];

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['row']],
            'form' => $form
        ];
    }

}

?>