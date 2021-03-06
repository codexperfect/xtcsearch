<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-09
 * Time: 15:50
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\Core\Form\FormStateInterface;
use Drupal\xtc\XtendedContent\API\Config;

trait PagerTrait
{

  /**
   * @var \Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase
   */
  protected $pager;


  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function pagerCallback(array $form, FormStateInterface $form_state) {
    $total= $this->searchBuilder->getResultSet()
                                ->getTotalHits();
    $this->searchBuilder->paginationSet('total', $total);
    $this->isCallback = true;
    $this->form = $form;
    $form_state->setCached(false);
    $form_state->disableCache();
    $this->getCallbackResults();
    return $this->pager->callBack($this->form, $form_state);
  }

  protected function getHeaderButton() {
    if(!empty($this->loadDisplay()['total'])){
      $this->form['container']['container_header']['total'] = [
        '#type' => 'xtctotal',
        '#markup' => '<div id="total"> ' . $this->searchBuilder->paginationGet('total')
      . t(' Résultat(s)') . '</div>',
        '#weight' => '2',
      ];
    }
  }

  protected function getPagination() {
    if(empty($this->definition['pager'])){
      $this->definition['pager'] = ['name' => 'nopager'];
    }
    if('nopager' == $this->definition['pager']){
      return;
    }
    foreach ($this->definition['pager'] as $name => $value) {
      $this->searchBuilder->paginationSet($name, $value);
    }
    if(!empty($this->searchBuilder->paginationGet('name'))){
      $this->pager = Config::getXtcPager($this->searchBuilder->paginationGet('name'));
    }

    $this->pager->setXtcSearchForm($this);
    foreach ($this->searchBuilder->getPagination() as $name => $value){
      $this->pager->set($name, $value);
    }
    foreach ($this->pager->getLibs() as $lib) {
      $this->form['#attached']['library'][] = $lib;
    }
    $this->form['container']['elements']['items']['ajax_'.$this->pager->getPluginId()] =
      $this->pager->getPager($this->searchBuilder->paginationGet('page'));
  }

}