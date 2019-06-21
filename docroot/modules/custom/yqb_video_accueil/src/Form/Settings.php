<?php

namespace Drupal\yqb_video_accueil\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class Settings extends ConfigFormBase {
    const MAX_FILE_SIZE = 15;

    public function getFormId() {
        return 'yqb_video_accueil_settings';
    }

    public function getEditableConfigNames() {
        return [
            'yqb_video_accueil.settings',
        ];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('yqb_video_accueil.settings');
        //$file = File::load($form_state['video_accueil']);
        $form['video_accueil_fid'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Vidéo'),
            '#description' => t('The uploaded image that will be displayed on the homepage.'),
            '#upload_location' => 'public://videos',
            '#upload_validators' => array(
                'file_validate_extensions' => array('mp4 webm ogv'),
                // Pass the maximum file size in bytes
                'file_validate_size' => array(self::MAX_FILE_SIZE * 1024 * 1024),
            ),
        );
        $form['poster_video_accuel_fid'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Poster'),
            '#description' => $this->t('The default image displayed while the video is downloading.'),
            '#upload_location' => 'public://default_images',
            '#upload_validators' => array(
                'file_validate_size' => array(self::MAX_FILE_SIZE * 1024 * 1024),
            ),
        );

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

        // validate video
        // validate image
    //        if ($form_state->getValue('video')) {
    //            dump($form_state->getValue('video'));
    //            die(1);
    //        }
//        if ($form_state->getValue('disabled')) {
//            $disabledText = $form_state->getValue('disabled_text');
//            if (empty($disabledText)) {
//                $form_state->setError(
//                    $form['disabled_text'],
//                    $this->t('The "Disabled Text" field is required if the parking booker is disabled.')
//                );
//            }
//        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $this->config('yqb_video_accueil.settings')
            ->set('video_accueil_fid', $form_state->getValue('video_accueil_fid'))
            ->set('poster_video_accueil_fid', $form_state->getValue('poster_video_accueil_fid'))
            ->save();
    }
}
