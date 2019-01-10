<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 15:15
 */

namespace Drupal\xtcsearch\Form\Traits;


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

  protected function initPagination(){
    if(!empty($this->definition['pager'])){
      foreach ($this->definition['pager'] as $name => $value) {
//        $this->pagination[$name] = $value;
        $this->paginationSet($name, $value);
      }
    }
  }

  public function paginationSet($field, $value){
    $this->pagination[$field] = $value;
  }

  public function paginationGet($field){
    return $this->pagination[$field] ?? null;
  }

  public function getPagination(){
    return $this->pagination;
  }

}