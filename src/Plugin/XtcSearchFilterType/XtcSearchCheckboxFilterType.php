<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Component\Serialization\Json;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation("Checkbox filter."),
 * )
 */
class XtcSearchCheckboxFilterType extends XtcSearchFilterTypePluginBase
{

  public function getFilter(){
    if(!empty($this->getOptions())){
      return [
        '#type' => 'xtc_checkboxes',
        '#title' => $this->getTitle(),
        '#options' => $this->getOptions(),
        '#default_value' => $this->getDefault(),
      ];
    }
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
