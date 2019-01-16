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

  public function getRequest(){
    $request = \Drupal::request();
    $mustnot = [];
    if(!empty($request->get($this->getQueryName())) ) {
      if (!is_array($request->get($this->getQueryName()))) {
        $filterRequest = $this->getDefault();
        $values = array_values($filterRequest);
      }
      else {
        $values = array_values($request->get($this->getQueryName()));
      }
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

  public function getDefault() {
    $value = \Drupal::request()->get($this->getQueryName());
    if(is_string($value)){
      $value = Json::decode($value) ?? $value;
    }
    if(!is_array($value)){
      $value = (!empty($value)) ? [$value] : [];
    }
    return $value;
  }

}
