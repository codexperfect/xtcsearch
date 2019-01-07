<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 15:15
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\Core\Form\FormStateInterface;
use Drupal\xtcsearch\Form\XtcSearchFormBase;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;
use Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase;

/**
 * Trait PaginationTrait
 *
 * use PrefixSuffixTrait;
 *
 * @package Drupal\xtcsearch\Form\Traits
 */
trait PaginationTrait
{

  /**
   * @var array
   *
   * An associative array of additional URL options, with the
   * following elements:
   * - 'from'
   * - 'size'
   * - 'total'
   * - 'page'
   */
  protected $pagination = [
    'top_navigation' => FALSE,
    'bottom_navigation' => FALSE,
    'from' => 0,
    'size' => 5,
    'total' => 0,
    'page' => 1,
    'masonry' => TRUE,
  ];

  /**
   * @var \Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase
   */
  protected $pager;


  protected function initPagination($definition){
    if(!empty($definition['pager'])){
      foreach ($definition['pager'] as $name => $value) {
        $this->pagination[$name] = $value;
      }
    }
  }

  protected function getPagination() {
    $type = \Drupal::service('plugin.manager.xtcsearch_pager');
    if(!empty($this->definition['pager'])){
      foreach ($this->definition['pager'] as $name => $value) {
        $this->pagination[$name] = $value;
      }
    }
    if(!empty($this->pagination['name'])){
      $this->pager = $type->createInstance($this->pagination['name']);
    }

    if($this->pager instanceof XtcSearchPagerPluginBase &&
       $this instanceof XtcSearchFormInterface
    ){
      $this->pager->setXtcSearchForm($this);
      foreach ($this->pagination as $name => $value){
        $this->pager->set($name, $value);
      }
      foreach ($this->pager->getLibs() as $lib) {
        $this->form['#attached']['library'][] = $lib;
      }
      $this->form['container']['page_number'] = [
        '#type' => 'hidden',
        '#value' => $this->pagination['page'],
        '#attributes' => [
          'id' => ['page_number'],
        ],
      ];
      $this->form['container']['elements']['items']['ajax_'.$this->pager->getPluginId()] = $this->pager->getPager();
    }
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function pagerCallback(array $form, FormStateInterface $form_state) {
    if($this instanceof XtcSearchFormBase){
      $this->pagination['total'] = $this->getResultSet()->getTotalHits();
    }
    $this->isCallback = TRUE;
    $this->form = $form;
    $form_state->setCached(FALSE);
    $form_state->disableCache();
    $this->getCallbackResults();
    return $this->pager->callBack($this->form, $form_state);
  }

  protected function getHeaderButton() {
    if(!empty($this->loadDisplay()['total'])){
      $this->form['container']['container_header']['total'] = [
        '#type' => 'xtctotal',
        '#markup' => '<div id="total"> ' . $this->pagination['total'] . t(' Résultat(s)') . '</div>',
        '#weight' => '2',
      ];
    }
  }



}