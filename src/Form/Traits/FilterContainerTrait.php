<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-09
 * Time: 14:23
 */

namespace Drupal\xtcsearch\Form\Traits;


trait FilterContainerTrait
{

  use FilterTrait;

  protected function getFilters() {
    $weight = 0;
    foreach($this->filters as $name => $container) {
      $weight++;
      $filter = $this->loadFilter($name);
      $filter->setForm($this);
      if('hidden' != $container) {
        if(!empty($filter->getFilter())) {
          $this->form['container']['container_'
                                   . $container][$filter->getQueryName()] =
            $filter->getFilter();
          $this->form['container']['container_'
                                   . $container][$filter->getQueryName()]['#weight'] =
            $weight;
          $this->displayFilters = true;
        }

        if($filter->hasSuggest()) {
          $this->form['container']['container_'
                                   . $container][$filter->getQueryName()
                                                 . '_suggest'] =
            $filter->getSuggest();
          $this->form['container']['container_'
                                   . $container][$filter->getQueryName()
                                                 . '_suggest']['#weight'] =
            $weight;
        }

        foreach($filter->getLibs() as $lib) {
          $this->form['#attached']['library'][] = $lib;
        }
      }
    }
    $this->form['#attached']['drupalSettings']['xtcsearch']['display'] =
      $this->loadDisplay();
    $this->form['#attached']['drupalSettings']['xtcsearch']['pager'] =
      $this->searchBuilder->getPagination();

    $this->getFilterButton();
  }

  protected function getFilterButton() {
    if((!empty($this->loadDisplay()['filter_top']) ||
        !empty($this->loadDisplay()['filter_bottom'])
       )
       && $this->displayFilters
    ) {
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