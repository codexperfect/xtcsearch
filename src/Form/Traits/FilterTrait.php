<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:41
 */

namespace Drupal\xtcsearch\Form\Traits;


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


}