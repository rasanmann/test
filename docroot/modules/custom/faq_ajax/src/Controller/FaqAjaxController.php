<?php
namespace Drupal\faq_ajax\Controller;
use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;

/**
 *
**/
class FaqAjaxController extends ControllerBase {
  /**
   * {@inheritdoc}
  */
  public function getFaqByCategory ($lang, $tid) {

    $markup = \Drupal::service('renderer')->render(views_embed_view('faq', 'questions_by_faq_category', $tid, $lang));
    return new Response(render($markup));

  }

}
