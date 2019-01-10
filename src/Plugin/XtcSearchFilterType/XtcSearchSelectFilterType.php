<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Component\Serialization\Json;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "select",
 *   label = @Translation("Select"),
 *   description = @Translation("Select filter."),
 * )
 */
class XtcSearchSelectFilterType extends XtcSearchFilterTypePluginBase
{

  public function getFilter(){
    $empty = ['' => '---'];
    $options = (!empty($this->getOptions()))
      ? array_merge($empty, $this->getOptions())
      : $empty;
    return [
      '#type' => 'select',
      '#title' => $this->getTitle(),
      '#options' => $options,
      '#default_value' => $this->getDefault(),
      '#attributes' => [
        'class' => [ 'custom-select'],
      ],
      '#prefix' => '<div class="col-12 mb-4">',
      '#suffix' => '</div>',
    ];
  }

  public function getDefault() {
    $value = Json::decode(\Drupal::request()->get($this->getQueryName()));
    if(is_array($value)){
      $value = $value[0];
    }
    return (!empty($value)) ? $value : '';
  }

}
