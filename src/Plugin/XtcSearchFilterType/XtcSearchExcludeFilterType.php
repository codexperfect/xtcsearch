<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Component\Serialization\Json;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "exclude",
 *   label = @Translation("Exclude"),
 *   description = @Translation("Exclude filter."),
 * )
 */
class XtcSearchExcludeFilterType extends XtcSearchFilterTypePluginBase
{

  public function getRequest($default = []){
    $mustnot = [];
    if(!empty($value = $this->getDefault())) {
      $values = array_filter(array_values($value));
    }
    if(!empty($values) ){
      foreach ($values as $key => $value) {
        $mustnot['bool']['should'][0][$key] = ['term' => [$this->getFieldName() => $value]];
      }
    }
    return $mustnot;
  }

  public function getFilter(){
  }

  public function setDefault($values = []) {
    $value = \Drupal::request()->get($this->getQueryName());
    if(is_string($value)){
      $value = Json::decode($value) ?? $value;
    }
    if(!is_array($value)){
      $value = (!empty($value)) ? [$value] : [];
    }
    $this->default = $value;
  }

}
