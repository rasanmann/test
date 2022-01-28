<?php
namespace Drupal\faq_ajax\Controller;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
**/
class FaqAjaxController extends ControllerBase {

  /**
   *
  */
  public function getFaqByCategory ($lang, int $tid) {
    //Add tag name
    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();
    if(!$is_ajax){
      throw new NotFoundHttpException();
    }
    $lang  = Html::escape($lang);
    $markup = \Drupal::service('renderer')->render(views_embed_view('faq', 'questions_by_faq_category', $tid, $lang));
    return new Response(render($markup));

  }

  /**
   * {@inheritdoc}
  */
  public function questionClickCounter ($lang, int $nid) {
    $lang  = Html::escape($lang);
    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();
    if (!$is_ajax) {
      throw new NotFoundHttpException();
    }
    $question = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($question->hasTranslation($lang)) {
      $question = $question->getTranslation($lang);
    }
    $counter = $question->get('field_click_counter')->value ?? 0;
    $question->set('field_click_counter', $counter + 1);
    $question->save();
    return new Response('ok');
  }


}
