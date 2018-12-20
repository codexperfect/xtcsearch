<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


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
    return [
      '#type' => 'select',
      '#title' => $this->getTitle(),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getDefault(),
      '#attributes' => [
        'class' => [ 'custom-select'],
      ],
      '#prefix' => '<div class="col-12 mb-4">',
      '#suffix' => '</div>',
    ];
  }

}
