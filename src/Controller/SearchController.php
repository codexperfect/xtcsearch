<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 20/08/2018
 * Time: 16:37
 */

namespace Drupal\xtcsearch\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\xtcsearch\Form\XtcSearchForm;

class SearchController extends ControllerBase
{

  /**
   * @param $id
   *
   * @return array
   */
  public function search() {
    $form = \Drupal::formBuilder()
      ->getForm(XtcSearchForm::class);
    return [
      '#theme' => 'csoec_agenda',
      '#response' => ['headline' => $this->getTitle()],
      '#form_events' => $form,
    ];
  }

  public function getTitle() {
    return 'Recherche';
  }

  protected function getType() {
    return 'document';
  }

}
