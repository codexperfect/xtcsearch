<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:41
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtc\XtendedContent\API\XtcFilter;
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

  protected function initFilters(){
    $this->filters = $this->definition['filters'];
  }

  /**
   * @param $name
   *
   * @return \Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase
   */
  protected function loadFilter($name) : XtcSearchFilterTypePluginBase{
    $filter = XtcFilter::get($name);
    return $filter->getFilterType();
  }


}