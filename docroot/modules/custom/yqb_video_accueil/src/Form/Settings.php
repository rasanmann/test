<?php

namespace Drupal\yqb_video_accueil\Form;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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

        // La vidéo
        $form['video_accueil_fid'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Vidéo'),
            '#description' => t('The uploaded image that will be displayed on the homepage.'),
            '#upload_location' => 'public://videos',
            '#upload_validators' => array(
                'sanitizeFilename' => array(),
                'file_validate_extensions' => array('mp4 webm ogv'),
                'file_validate_size' => array(self::MAX_FILE_SIZE * 1024 * 1024),
            ),
        );

        // Le poster
        $form['poster_video_accueil_fid'] = array(
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

    public function sanitizeFilename(File $file) {
        file_move(
            $file,
            'public://videos/' . preg_replace('/[^A-Za-z0-9]/i', '_', $file->getFilename())
        );
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        // la validation se trouve déjà dans les upload_validators
        // optionnellement on pourrait utiliser FFMPEG pour effectuer plus de validation sur le contenu
        // de la vidéo
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

        foreach (['video_accueil_fid', 'poster_video_accueil_fid'] as $config) {
            if (!empty($form_state->getValue($config))) {
                $oldFile = $this->config('yqb_video_accueil.settings')->get($config);

                $this->config('yqb_video_accueil.settings')
                    ->set($config, $form_state->getValue($config));

                $this->sanitizeFilename(
                    File::load(
                        $this->config('yqb_video_accueil.settings')->get($config)[0]
                    )
                );

                if (isset($oldFile[0])) {
                    try {
                        file_delete($oldFile[0]);
                    } catch(FileException $e) {
                        // Il se peut que le fichier ait été supprimé manuellement
                        // L'index $oldFile[0] va exister, mais pas le fichier
                        // Si tel est le cas, file_delete va planter
                    }
                }
            }
        }

        $this->config('yqb_video_accueil.settings')->save();
    }
}
