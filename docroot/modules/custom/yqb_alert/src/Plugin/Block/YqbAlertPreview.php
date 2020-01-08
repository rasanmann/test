<?php

namespace Drupal\yqb_alert\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

abstract class YqbAlertPreview extends BlockBase {

  /** @var \Drupal\Core\TempStore\PrivateTempStore */
  public $tempStore;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = Drupal::service('tempstore.private')->get('yqb_blocks_preview');
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $preview = $form_state->get('preview_block');
    if (empty($preview)) {
      $preview = FALSE;
    }
    $form_state->set('preview_block', $preview);

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 200,
    ];
    $form['actions']['preview'] = [
      '#type' => 'submit',
      '#name' => 'preview',
      '#value' => $this->t('Preview short version'),
      '#weight' => 100,
      '#submit' => [[$this, 'previewBlock']],
      '#ajax' => [
        'callback' => [$this, 'updateShortPreview'],
        'wrapper' => 'preview-zone',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['actions']['previewother'] = [
      '#type' => 'submit',
      '#name' => 'previewother',
      '#value' => $this->t('Preview full version'),
      '#weight' => 100,
      '#submit' => [[$this, 'previewBlock']],
      '#ajax' => [
        'callback' => [$this, 'updateFullpreview'],
        'wrapper' => 'preview-zone',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['short_preview_zone'] = [
      '#type' => 'container',
      '#weight' => 300,
      '#id' => 'preview-zone',
      '#attributes' => [
        'class' => $preview ? ['is-preview'] : [],
      ],
      'content' => $preview ? $this->getPreviewRenderArrayAlert('short') : ['#markup' => ''],
    ];
    $form['full_preview_zone'] = [
      '#type' => 'container',
      '#weight' => 300,
      '#id' => 'preview-zone',
      '#attributes' => [
        'class' => $preview ? ['is-preview'] : [],
      ],
      'content' => $preview ? $this->getPreviewRenderArrayAlert('full') : ['#markup' => ''],
    ];


    return $form;
  }

  protected function getPreviewRenderArray() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $this->getPreviewUrl(),
        'height' => 800,
        'width' => "100%",
      ],
    ];
  }

  protected function getPreviewRenderArrayAlert($shortOrFull)
  {
    //get language of site
//    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if($shortOrFull == 'short'){
      $url = Url::fromRoute('<front>')->toString();
    }else {
      $url = Url::fromRoute('yqb_alert.fr.index')->toString();
    }

    //change url base on language

    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $url,
        'height' => 800,
        'width' => "100%",
      ],
    ];
  }

  abstract protected function getPreviewUrl();

  abstract protected function previewSubmit($form, FormStateInterface $form_state);

  public function updateShortPreview($form, FormStateInterface $form_state) {
    return $form['settings']['short_preview_zone'];
  }

  public function updateFullPreview($form, FormStateInterface $form_state, $whichPreview) {
    return $form['settings']['full_preview_zone'];
  }

  public function previewBlock($form, FormStateInterface $form_state) {
    Drupal\Core\Cache\Cache::invalidateTags(['preview:block.' . $this->getDerivativeId()]);
    $form_state->set('preview_block', TRUE);
    $this->previewSubmit($form, $form_state);
    $form_state->setRebuild();
  }

  public function getCacheTags() {
    $tags = parent::getCacheTags();

    $tags[] = 'preview:block.' . $this->getDerivativeId();

    return $tags;
  }
}
