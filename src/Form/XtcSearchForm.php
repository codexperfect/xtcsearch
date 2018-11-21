<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 21/11/2018
 * Time: 08:23
 */

namespace Drupal\xtcsearch\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class XtcSearchForm extends XtcSearchFormBase
{

  protected function getSearchId() {
    return 'xtcsearch';
  }

  /**
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function resetLink(){
    return Url::fromRoute('xtcsearch.search')->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('xtcsearch.search', ['s' => '*']);
    $form_state->setRedirectUrl($url);
  }

}