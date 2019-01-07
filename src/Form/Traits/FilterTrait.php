<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:41
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\xtcsearch\Form\XtcSearchFormBase;
use Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcSearchFilterDefault;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

trait FilterTrait
{
  /**
   * @var array
   */
  protected $filters = [];

  /**
   * @var bool
   */
  protected $displayFilters = false;

  protected function initFilters($definition){
    $this->filters = $definition['filters'];
  }

  protected function getFilters() {
    if($this instanceof XtcSearchFormBase){
      $weight = 0;
      foreach ($this->filters as $name => $container) {
        $weight++;
        $filter = $this->loadFilter($name);
        $filter->setForm($this);
        if('hidden' != $container){
          if(!empty($filter->getFilter())){
            $this->form['container']['container_'.$container][$filter->getQueryName()] = $filter->getFilter();
            $this->form['container']['container_'.$container][$filter->getQueryName()]['#weight'] = $weight;
            $this->displayFilters = true;
          }

          if($filter->hasSuggest()){
            $this->form['container']['container_'.$container][$filter->getQueryName().'_suggest'] = $filter->getSuggest();
            $this->form['container']['container_'.$container][$filter->getQueryName().'_suggest']['#weight'] = $weight;
          }

          foreach ($filter->getLibs() as $lib) {
            $this->form['#attached']['library'][] = $lib;
          }
          $this->form['#attached']['drupalSettings']['xtcsearch']['display'] = $this->loadDisplay();
          $this->form['#attached']['drupalSettings']['xtcsearch']['pager'] = $this->pagination;
        }
      }
    }
  }

  /**
   * @param $name
   *
   * @return \Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase
   */
  protected function loadFilter($name) : XtcSearchFilterTypePluginBase{
    $filter = $this->getFilter($name);
    return $filter->getFilterType();
  }

  /**
   * @param $name
   *
   * @return \Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcSearchFilterDefault
   */
  protected function getFilter($name) : XtcSearchFilterDefault{
    $service = \Drupal::service('plugin.manager.xtcsearch_filter');
    return  $service->createInstance($name);
  }

  protected function addAggregations() {
    if($this instanceof XtcSearchFormBase) {
      foreach($this->filters as $name => $container) {
        $filter = $this->loadFilter($name);
        $filter->setForm($this);
        $filter->addAggregation();
      }
    }
  }

  protected function getCriteria() {
    if($this instanceof XtcSearchFormBase) {
//      if(empty($this->filters[$this->definition['sort']['field']])){
//        $sort = [$this->definition['sort']['field'] => 'sort'];
//        $allFilters = array_merge($this->filters, $sort);
//      }
//      else{
//        $allFilters = $this->filters;
//      }
      foreach($this->filters as $name => $container) {
        $filter = $this->loadFilter($name);
        $filter->setForm($this);
        $this->musts[$filter->getQueryName()] = $filter->getRequest();
        $this->addCompletion($filter);
        $this->addSuggest($filter);
      }
    }
  }

  protected function addCompletion(XtcSearchFilterTypePluginBase $filter){
    if($filter->hasCompletion()) {
      $this->suggests[$filter->getQueryName()]['completion_' . $filter->getQueryName()] = $filter->initCompletion();
    }

  }

  protected function addSuggest(XtcSearchFilterTypePluginBase $filter){
    if($filter->hasSuggest()) {
      $this->suggests[$filter->getQueryName()]['suggest_' . $filter->getQueryName()] = $filter->initSuggest();
    }

  }

  protected function getFilterButton() {
    if((!empty($this->loadDisplay()['filter_top'])
        || !empty($this->loadDisplay()['filter_bottom']))
       && $this->displayFilters) {
      $filterElement = [
        '#type' => 'submit',
        '#value' => $this->t('Filtrer'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-dark',
            'filter-submit',
          ],
        ],
      ];
      $this->buildFilterTop($filterElement);
      $this->buildFilterBottom($filterElement);
    }
    else{
      unset($this->form['container']['container_sidebar']);
    }
  }

  protected function buildFilterTop($top){
    if(!empty($this->loadDisplay()['filter_top'])) {
      $top['#weight'] = -100;
      $top['#prefix'] = $this->getButtonPrefix('filter_top');
      $top['#suffix'] = $this->getButtonSuffix('filter_top');
      $this->form['container']['container_sidebar']['buttons']['filter_top'] =
        $top;
    }
  }

  protected function buildFilterBottom($bottom){
    if(!empty($this->loadDisplay()['filter_bottom'])) {
      $bottom['#weight'] = 100;
      $bottom['#prefix'] = $this->getButtonPrefix('filter_bottom');
      $bottom['#suffix'] = $this->getButtonSuffix('filter_bottom');
      $this->form['container']['container_sidebar']['filter_bottom'] =
        $bottom;
    }
  }

}