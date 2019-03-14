<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "contentId",
 *   label = @Translation("Content ID"),
 *   description = @Translation("Content ID filter."),
 * )
 */
class XtcSearchContentIdFilterType extends XtcSearchFilterTypePluginBase
{

  public function getFilter(){
    return [
      '#type' => 'textfield',
      '#title' => $this->getTitle(),
      '#default_value' => $this->getDefault(),
      '#prefix' => '<div class="col-12 mb-4">',
      '#suffix' => '</div>',
    ];
  }

  public function setDefault($values = []) {
    if(!empty($values[$this->getQueryName()])){
      $this->default = [$values[$this->getQueryName()]];
    }
    else{
      $this->default = [\Drupal::request()->get($this->getQueryName())] ?? [];
    }
  }

}
