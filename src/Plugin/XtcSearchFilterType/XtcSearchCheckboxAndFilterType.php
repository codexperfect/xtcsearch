<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Component\Serialization\Json;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "checkbox_and",
 *   label = @Translation("Checkbox AND"),
 *   description = @Translation("Checkbox AND filter."),
 * )
 */
class XtcSearchCheckboxAndFilterType extends XtcSearchCheckboxFilterType
{

  public function getRequest(){
    $must = [];
    $values = [];
    if(!empty($value = $this->getDefault())) {
      $values = array_filter(array_values($value));
    }
    if(!empty($values) ){
      foreach ($values as $key => $value) {
        $must['bool']['must'][0][$key] = ['term' => [$this->getFieldName()
          => $value]] ?? [];
      }
    }
    return $must;
  }

}
