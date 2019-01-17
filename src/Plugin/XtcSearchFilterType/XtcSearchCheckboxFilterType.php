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

  public function setDefault() {
    $queryName = $this->getQueryName();
    $requestParameter = \Drupal::request()->get($queryName);
    if(is_string($requestParameter)){
      $requestParameter = Json::decode($requestParameter) ?? $requestParameter;
    }
    if(!is_array($requestParameter)){
      $requestParameter = (!empty($requestParameter)) ? [$requestParameter] : [];
    }
    $route = \Drupal::routeMatch();
    $routeParameter = $route->getParameters()->get($queryName) ?? [];
    $routeOptions = $route->getRouteObject()->getOptions()['parameters'] ?? [];
    $routeOption = $routeOptions[$queryName] ?? [];

    $value = array_unique(array_merge($requestParameter, $routeParameter,
                                      $routeOption));

    $this->default = $value;
  }

}
