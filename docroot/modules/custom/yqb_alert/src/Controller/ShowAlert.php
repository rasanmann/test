<?php

namespace Drupal\yqb_alert\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\Entity\Block;

class ShowAlert extends ControllerBase{


    public function Index(){

        $block = Block::load('yqbblockalert');
        $settings = $block->get('settings');

        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $language == 'fr' ? $content = $settings['french_alert_full'] : $content = $settings['english_alert_full'];

        if(!$settings['alert_is_enable']){
            return ['#markup' => ""];
                              
        } else {
            return [
            '#markup' => $content['value'],
        ];
    }

    

   
        
}

}
