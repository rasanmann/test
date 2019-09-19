<?php

namespace Drupal\yqb_mailchimp;

use Drupal\node\Entity\Node;
use Drupal\mailchimp_campaign\Entity\MailchimpCampaign;
use Drupal\Core\Datetime\DrupalDateTime;

class Alert {

  protected $node;

  protected $config;

  public function __construct(Node $node) {
    $this->node = $node;
    $this->config = \Drupal::configFactory()->get('yqb_mailchimp.settings');
  }

  public function send() {
    if ($this->node->hasField('field_alert_sent') && $this->node->id() && !$this->node->isNew()) {
      $alertSent = $this->node->get('field_alert_sent')->value;
      if (!$alertSent && $this->node->isPublished()) {
        $this->sendMailchimpCampaign();
      }
    }
  }

  protected function sendMailchimpCampaign() {
    if ($this->isSendable()) {
      try {
        $templateContent = $this->getTemplateContent();
        $campaignId = $this->createMailchimpCampaign($templateContent);

        $campaign = MailchimpCampaign::create();
        $campaign->setMcCampaignId($campaignId);
        $campaign->setTemplate($templateContent);
        $campaign->save();
        $campaign = MailchimpCampaign::load($campaign->id());

        if ($this->scheduleMailchimpCampaign($campaign)) {
          $this->node->set('field_alert_sent', TRUE);
          $this->node->save();
        }
      } catch (\Exception $e) {
        \Drupal::logger('mailchimp_campaign')
               ->error('An error occurred while sending to this campaign: {message}', [
                 'message' => $e->getMessage(),
               ]);
      }
    }
  }

  protected function isSendable() {
    $configurationsSet = (bool) $this->config->get('list_id') && $this->config->get('template_id');

    $startDate = $this->node->field_start_date->date->getTimestamp();
    $endDate = $this->node->field_end_date->date->getTimestamp();
    $between = $startDate <= \Drupal::time()->getCurrentTime() && $endDate >= \Drupal::time()->getCurrentTime();

    return $configurationsSet && $between;
  }

  protected function getTemplateContent() {
    $element = [
      '#theme' => 'yqb_mailchimp_template',
      '#node' => $this->node,
      '#title' => $this->node->getTitle(),
      '#type' => $this->node->field_career_type->entity->getName(),
      '#department' => \Drupal::service('entity.repository')
          ->getTranslationFromContext(
              $this->node->field_department->entity, $this->node->language()->getId()
          )->getName(),
      '#location' => $this->node->field_location_name->value,
      '#summary' => $this->node->field_job_summary->value
    ];
    $html = \Drupal::service('renderer')->renderRoot($element);

    return [
      'body_content' => [
        'value' => $html,
        'format' => 'mailchimp_campaign',
      ],
      'job_title' => [
        'value' => $this->node->getTitle(),
        'format' => 'mailchimp_campaign'
      ],
      'job_link' => [
        'value' => '<a class="mcnButton" title="En savoir plus" href="'.$this->node->toUrl('canonical', ['absolute' => TRUE])->toString().'" target="_blank" style="font-weight:bold;letter-spacing:normal;line-height:100%;text-align:center;text-decoration:none;color:#FFFFFF;">En savoir plus</a>',
        'format' => 'mailchimp_campaign'
      ]
    ];
  }

  protected function createMailchimpCampaign($templateContent) {
    $recipients = (object) [
      'list_id' => $this->config->get('list_id'),
    ];

    $settings = (object) [
      'subject_line' => $this->node->getTitle(),
      'title' => $this->node->getTitle(),
      'from_name' => $this->config->get('from_name'),
      'reply_to' => $this->config->get('from_email'),
    ];

    return mailchimp_campaign_save_campaign(
      $templateContent,
      $recipients,
      $settings,
      $this->config->get('template_id')
    );
  }

  protected function scheduleMailchimpCampaign(MailchimpCampaign $campaign) {
    /* @var \Mailchimp\MailchimpCampaigns $mc_campaign */
    $mc_campaign = mailchimp_get_api_object('MailchimpCampaigns');

    // Schedule campaign.
    try {
      if (!$mc_campaign) {
        throw new \Exception('Cannot send campaign without Mailchimp API. Check API key has been entered.');
      }

      $mc_campaign->schedule($campaign->mc_data->id, $this->getScheduleTime());
      $result = $mc_campaign->getCampaign($campaign->mc_data->id);

      if (($result->status == MAILCHIMP_STATUS_SCHEDULE) || ($result->status == MAILCHIMP_STATUS_SENDING)) {
        // Log action, and notify the user.
        \Drupal::logger('mailchimp_campaign')->notice('Mailchimp campaign {name} has been scheduled.', [
          'name' => $campaign->label(),
        ]);

        $controller = \Drupal::entityTypeManager()->getStorage('mailchimp_campaign');
        $controller->resetCache([$campaign->getMcCampaignId()]);

        $cache = \Drupal::cache('mailchimp');

        $cache->invalidate('campaigns');
        $cache->invalidate('campaign_' . $campaign->mc_data->id);

        return TRUE;
      }
    } catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      \Drupal::logger('mailchimp_campaign')
             ->error('An error occurred while sending to this campaign: {message}', [
               'message' => $e->getMessage(),
             ]);
    }
    return FALSE;
  }

  protected function getScheduleTime() {
    $schedule = new DrupalDateTime('today 16:00:00');
    if ($schedule->getTimestamp() < \Drupal::time()->getCurrentTime()) {
      $schedule->modify('tomorrow 16:00:00');
    }

    return $schedule->format(\DateTime::ISO8601);
  }
}
