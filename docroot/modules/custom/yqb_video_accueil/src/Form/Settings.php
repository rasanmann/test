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

        // La vidéo
        $form['video_accueil_fid'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Vidéo'),
            '#description' => t('The uploaded image that will be displayed on the homepage.'),
            '#upload_location' => $config->get('video_upload_location'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('mp4 webm ogv'),
                'file_validate_size' => array(self::MAX_FILE_SIZE * 1024 * 1024),
            ),
        );

        // Le poster
        $form['poster_video_accueil_fid'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Poster'),
            '#description' => $this->t('The default image displayed while the video is downloading.'),
            '#upload_location' => $config->get('poster_upload_location'),
            '#upload_validators' => array(
                'file_validate_size' => array(self::MAX_FILE_SIZE * 1024 * 1024),
            ),
        );

        return $form;
    }

    public function sanitizeFilename(File $file) {
        // file_move gère les différents cas d'erreur et effectue les modifications dans la base de donnée
        file_move(
            $file,
            $this->config('yqb_video_accueil.settings')->get('video_upload_location') . '/' .
            preg_replace('/[^A-Za-z0-9\.]/i', '_', $file->getFilename())
        );
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        // la validation se trouve déjà dans les upload_validators
        // optionnellement on pourrait utiliser FFMPEG pour effectuer plus de validation sur le contenu
        // de la vidéo
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $config = $this->config('yqb_video_accueil.settings');
        // On ajoute la nouvelle vidéo/image à la config du module
        // On renomme le fichier pour qu'il soit unix-friendly
        // On supprime l'ancienne vidéo/image
        foreach (['video_accueil_fid', 'poster_video_accueil_fid'] as $input) {
            if (!empty($form_state->getValue($input))) {
                $oldFile = $config->get($input);

		$config->set($input, $form_state->getValue($input));

                $this->sanitizeFilename(
                    File::load(
                        $config->get($input)[0]
                    )
                );

                if (isset($oldFile[0])) {
                    file_delete($oldFile[0]);
                }
            }
        }

        $config->save();
    }
}
