<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("Autocomplete filter."),
 * )
 */
class XtcSearchAutocompleteFilterType extends XtcSearchFilterTypePluginBase
{

  /**
   * @return array
   */
  public function getFilter(){
    return [];
  }

  protected function cleanup($string){
    return $string;
  }

  public function getRequest(){
    $must = [];
    return $must;
  }

  public function hasCompletion() {
    return true;
  }

  public function initCompletion(){
    if($this->hasCompletion()) {
      return [
        'prefix' => $this->getDefault(),
        'completion' => [
          'field' => 'suggest.light_suggest',
          'size' => 10,
          'fuzzy' => [
            'fuzziness' => 1
          ],
          'skip_duplicates' => true
        ],
      ];
    }
    return [];
  }

  public function setDefault() {
    $this->default = urldecode(\Drupal::request()->get($this->getQueryName()));
  }

  protected function getFields() : array {
    return $this->getParams()['fields'] ?? [];
  }

}
