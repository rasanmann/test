<?php

namespace Drupal\yqb_payments\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Online Payment entity.
 *
 * @ingroup yqb_payments
 *
 * @ContentEntityType(
 *   id = "yqb_payment",
 *   label = @Translation("Online Payment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\yqb_payments\YqbPaymentEntityListBuilder",
 *     "views_data" = "Drupal\yqb_payments\Entity\YqbPaymentEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\yqb_payments\Form\YqbPaymentEntityForm",
 *       "add" = "Drupal\yqb_payments\Form\YqbPaymentEntityForm",
 *       "edit" = "Drupal\yqb_payments\Form\YqbPaymentEntityForm",
 *       "delete" = "Drupal\yqb_payments\Form\YqbPaymentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\yqb_payments\YqbPaymentEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\yqb_payments\YqbPaymentEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "yqb_payment",
 *   admin_permission = "administer online payment entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/airport-services/pay-bills/receipt/{yqb_payment}",
 *     "add-form" = "/admin/structure/yqb_payment/add",
 *     "edit-form" = "/admin/structure/yqb_payment/{yqb_payment}/edit",
 *     "delete-form" = "/admin/structure/yqb_payment/{yqb_payment}/delete",
 *     "collection" = "/admin/structure/yqb_payment",
 *   },
 *   field_ui_base_route = "yqb_payment.settings"
 * )
 */
class YqbPaymentEntity extends ContentEntityBase implements YqbPaymentEntityInterface
{

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime()
  {
    return $this->get('created')->value;
  }

  public function preSave(EntityStorageInterface $storage)
  {
    if ($this->isNew()) {
      $this->id = $this->uuid;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp)
  {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    $fields['transaction_no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Transaction number'))
      ->setDescription(t('The name of the Online Payment entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  public static function getConfigurableFields()
  {
    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setDescription(t('Payer first name.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setDescription(t('Payer last name.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Payer email.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['business_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Business name'))
      ->setDescription(t('Payer business name.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['bill_no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bill number'))
      ->setDescription(t('Payer bill no.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['customer_no'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer number'))
      ->setDescription(t('Payer customer number.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Amount to pay'))
      ->setDescription(t('Amount paid by the customer.'))
      ->setDefaultValue('')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['notifications'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('I would like to receive invoices and statements by email.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

  public function label()
  {
    return $this->get('transaction_no')->value;
  }
}
