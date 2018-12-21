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

  public function getFilter(){
    $filter = [
      '#type' => 'search',
      '#title' => $this->getTitle(),
      '#placeholder' => $this->getPlaceholder(),
      '#default_value' => $this->getDefault(),
      '#attributes' => [
        'class' => [
          'form-control',
          'col-11',
          'SearchBig',
          'fontcss',
        ],
        'id' => [
          'searchInput-main',
        ],
        'onchange' => 'window.location.href="'. $this->form->searchRoute() .'?fulltext=" + encodeURI(this.value);',
      ],
      '#weight' => '1',
      '#autocomplete_route_name' => $this->getAutocomplete()['service'],
      '#autocomplete_route_parameters' => $this->getAutocomplete()['parameters'],
//      '#prefix' => '<div class="col-md-10 text-right">',
//      '#suffix' => '</div>',
    ];
    return $filter;

  }

  public function getSuggest(){
    if($this->hasSuggest()){
      $suggestions = $this->form->getResultSet()->getSuggests();

      foreach($suggestions as $suggestion){
        if(!empty($suggestion[0]['options'])){
          foreach ($suggestion[0]['options'] as $key => $value) {
            $text = '"' . strtolower($value['text']) . '"';
            $url = Url::fromRoute($this->form->getRouteName(), [$this->getPluginId() =>
              urlencode($text)]);
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

  public function getRequest(){
    $request = \Drupal::request();
    $must = [];
    if(!empty($request->get($this->getFilterId())) ) {
      $value = $this->getDefault();
    }
    if(!empty($value) ){
      $must['query_string']['query'] = $value;
    }
    return $must;
  }

  public function hasSuggest() {
    return true;
  }

  public function initSuggest(){
    if($this->hasSuggest()) {
      $value = $this->getDefault();
      $fieldName = 'suggest_'.$this->pluginId;
      return [
        'text' => $value,
        $fieldName => [
          'prefix' => $value,
          'completion' => [
            'field' => 'suggest',
            'size' => 10,
            'fuzzy' => [
              'fuzziness' => 3
            ],
            'skip_duplicates' => true
          ],
        ],
        'completion' => [
          'regex' => '.*' . $value . '.*',
          'completion' => [
            'field' => 'complete',
          ],
        ],
      ];
    }
    return [];
  }

  public function toQueryString($input) {
    $value = $input[$this->getFilterId()];
    return urlencode($value);
  }

  public function getDefault() {
    return urldecode(\Drupal::request()->get($this->getFilterId()));
  }

  protected function getAutocomplete(){
    return $this->getParams()['autocomplete'];
  }

}
