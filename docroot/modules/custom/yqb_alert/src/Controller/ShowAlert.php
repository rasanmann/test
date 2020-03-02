<?php

namespace Drupal\yqb_alert\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\block\Entity\Block;
use Drupal\yqb_blocks\Plugin\Block\PreviewBlock;
use Drupal\Component\Annotation\Plugin;




class ShowAlert extends ControllerBase{


    public function Index(){

        $block = Block::load('yqbblockalert');
        $settings = $block->get('settings');
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $store = (Drupal::service('tempstore.private')->get('yqb_blocks_preview'));

        // We always erase the tempstore in YqbAlertblock. If any value exist in the tempstore it means were in preview
        if($store->get('french_alert_full')){
          $tempFrenchFull = $store->get('french_alert_full');
          $tempEnglishFull = $store->get('english_alert_full');
          $alertEnable = $store->get('alert_is_enable');
          $language == 'fr' ? $content = $tempFrenchFull : $content = $tempEnglishFull;
        }else {
          $alertEnable = strval($settings['alert_is_enable']);
          $language == 'fr' ? $content = strval($settings['french_alert_full']['value']) : $content = strval($settings['english_alert_full']['value']);
        }

        if(!$alertEnable){
          return [
            '#markup' => "",
          ];
        }else{
          return [
            '#markup' => $content,
          ];
        }

    }

}
