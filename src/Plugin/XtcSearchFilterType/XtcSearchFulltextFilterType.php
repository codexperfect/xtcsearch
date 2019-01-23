<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Core\Url;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "fulltext",
 *   label = @Translation("Fulltext"),
 *   description = @Translation("Fulltext filter."),
 * )
 */
class XtcSearchFulltextFilterType extends XtcSearchFilterTypePluginBase
{

  /**
   * @return array
   */
  public function getFilter(){
    return [
      '#type' => 'search',
      '#title' => $this->getTitle(),
      '#placeholder' => $this->getPlaceholder(),
      '#default_value' => $this->getDefault(),
      '#attributes' => [
        'class' => [
          'form-control',
          'SearchBig',
          'fontcss',
        ],
        'id' => [
          'searchInput-'.$this->getFilterId(),
        ],
        'onchange' => 'window.location.href="'. $this->form->searchRoute() .'?fulltext=" + encodeURI(this.value);',
      ],
      '#weight' => '1',
      '#autocomplete_route_name' => $this->getAutocomplete()['service'],
      '#autocomplete_route_parameters' => $this->getAutocomplete()['parameters'],
    ];
  }

  public function getSuggest(){
    if($this->hasSuggest()){
      $suggestions = $this->form->getResultSet()->getSuggests();

      $suggestionsList = [];
      $titleList = [];
      foreach($suggestions as $suggestion){
        if(!empty($suggestion[0]['options'])){
          foreach ($suggestion[0]['options'] as $key => $value) {
            $value['text'] = strtolower(\Drupal::service('csoec_common.common_service')->replaceAccents($value['text']));
            if(!in_array($value['text'], $titleList)) {
              $titleList[] = $value['text'];
              $text = $this->cleanup($value['text']);
              $url = Url::fromRoute($this->form->getRouteName(), [$this->getPluginId() => urlencode($text)]);
              //$url = $this->form->searchRoute([$this->getPluginId() => urlencode($text)]);
              $suggestionsList[] = [
                '#type' => 'link',
                '#title' => $value['text'],
                '#url' => $url,
                '#prefix' => '<p class="suggestion-retour-meta float-left">',
                '#suffix' => '</p>',
              ];
            }
          }
        }
      }

      $suggest = [
        '#type' => 'inline_template',
        '#weight' => '1',
        '#template' => '<div class="col-12 py-20"> <div class="row m-0"> <div class="col-lg-12"> <p class="h3-dossier">Suggestion de recherche :</p> </div> </div> 
                      <div class="row m-0"> <div class="col-lg-12"> {{ suggestions }}</div> </div> </div>',
        '#context' => [
          'suggestions' => $suggestionsList,
        ],
      ];
    }
    return $suggest;
  }

  protected function cleanup($string){
    return $string;
  }

  public function getRequest(){
    $request = \Drupal::request();
    $must = [];
    if(!empty($request->get($this->getQueryName())) ) {
      $value = $this->getDefault();
    }
    if(!empty($value) ){
      $must['query_string']['query'] = $value;
      if(!empty($fields = $this->getFields()) ){
        $must['query_string']['fields'] = $fields;
      }
    }
    return $must;
  }

  public function hasSuggest() {
    return $this->getParams()['suggest'] ?? false;
  }

  public function hasCompletion() {
    return $this->getParams()['completion'] ?? false;
  }

  public function initSuggest(){
    if($this->hasSuggest()) {
      return [
        'prefix' => $this->getDefault(),
        'completion' => [
          'field' => 'complete.light_suggest',
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

  public function toQueryString($input) {
    $value = $input[$this->getQueryName()] ?? \Drupal::request()->get
      ($this->getQueryName());
    return urlencode($value);
  }

  public function setDefault() {
    $this->default = urldecode(\Drupal::request()->get($this->getQueryName()));
  }

  protected function getAutocomplete(){
    return $this->getParams()['autocomplete'] ?? [];
  }

  protected function getFields() : array {
    return $this->getParams()['fields'] ?? [];
  }

}
