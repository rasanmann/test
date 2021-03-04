<?php

namespace Drupal\webform_mailchimp\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class WebformMailchimpFetchGroupsCommand implements CommandInterface {

  protected $listId;

  protected $groupId;

  public function __construct($options_id) {
    $mailchimp_ids = $this->getMailchimpIdsFromOptionsId($options_id);
    if ($mailchimp_ids) {
      $this->listId = array_key_exists(0, $mailchimp_ids) ? $mailchimp_ids[0] : '';
      $this->groupId = array_key_exists(1, $mailchimp_ids) ? $mailchimp_ids[1] : '';
    }
    else {
      $this->listId = '';
      $this->groupId = '';
    }
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    $options = [];
    // Setting reset = TRUE skips our local cache and fetches from Mailchimp
    $lists = mailchimp_get_lists([$this->listId], TRUE);
    $lists = !empty($lists) ? $lists : [];

    foreach ($lists as $list) {
      $list_id = !empty($list->id) ? $list->id : '';

      if ($list_id === $this->listId) {
        break;
      }
    }

    if (!empty($list)) {
      $list_groups = !empty($list->intgroups) ? $list->intgroups : [];

      foreach ($list_groups as $group) {
        $group_id = !empty($group->id) ? $group->id : '';

        if ($group_id === $this->groupId) {
          $group_interests = !empty($group->interests) ? $group->interests : [];

          foreach ($group_interests as $interest) {
            $interest_id = !empty($interest->id) ? $interest->id : '';
            $interest_name = !empty($interest->name) ? $interest->name : '';

            if (!empty($interest_id) && !empty($interest_name)) {
              $options[$interest_id] = $interest_name;
            }
          }
          break;  // We found the correct group
        }
      }
    }

    return array(
      'command' => 'fetchGroupsOptions',
      'options' => $options,
    );
  }

  /**
   * Extracts the Mailchimp list ID and group ID from the option group's ID.
   *
   * @param $options_id a string ID for the Webform options group
   *
   * @return bool|array FALSE if $options_id is empty or is shorter than the
   *                    expected length for Webform Mailchimp group options,
   *                    otherwise returns the array of Mailchimp ids where
   *                    index 0 is the List ID and index 1 is the group ID.
   */
  protected function getMailchimpIdsFromOptionsId($options_id) {
    if (empty($options_id)) {
      return FALSE;
    }

    $length = strlen(WEBFORM_MAILCHIMP_ID_PREFIX);
    $ids = substr($options_id, $length);
    if ($ids) {
      return explode("_", $ids);
    }

    return FALSE;
  }

}
