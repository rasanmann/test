<?php

namespace Drupal\yqb_blocks\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

abstract class PreviewBlock extends BlockBase {

  /** @var \Drupal\Core\TempStore\PrivateTempStore */
  protected $tempStore;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = Drupal::service('tempstore.private')->get('yqb_blocks_preview.' . $this->getBaseId());
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
      '#value' => $this->t('Preview'),
      '#weight' => 100,
      '#submit' => [[$this, 'previewBlock']],
      '#ajax' => [
        'callback' => [$this, 'updatePreview'],
        'wrapper' => 'preview-zone',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['preview_zone'] = [
      '#type' => 'container',
      '#weight' => 300,
      '#id' => 'preview-zone',
      '#attributes' => [
        'class' => $preview ? ['is-preview'] : [],
      ],
      'content' => $preview ? $this->getPreviewRenderArray() : ['#markup' => ''],
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

  abstract protected function getPreviewUrl();

  abstract protected function previewSubmit($form, FormStateInterface $form_state);

  public function updatePreview($form, FormStateInterface $form_state) {
    return $form['settings']['preview_zone'];
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
