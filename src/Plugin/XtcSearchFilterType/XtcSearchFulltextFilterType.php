<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\Component\Serialization\Json;
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
    if($this->hasSuggest()){
      $suggestions = $this->form->getResultSet()->getSuggests();

//      foreach ($suggestions['completion'][0]['options'] as $key => $value) {
//        $options = ['absolute' => TRUE];
//        $url = Url::fromRoute('csoec_search.csoec_main_search_form', ['s' => '"' . urlencode($value['text']) . '"'], $options);
//        if (strtolower($value['text']) != strtolower($this->query_string)) {
//          $suggestionsList[] = [
//            '#type' => 'link',
//            '#title' => $value['text'],
//            '#url' => $url,
//            '#prefix' => '<p class="suggestion-retour-meta float-left">',
//            '#suffix' => '</p>',
//          ];
//        }
//      }
//      if (count($suggestionsList)) {
//        $form['container']['suggestions'] = [
//          '#type' => 'inline_template',
//          '#weight' => '1',
//          '#template' => '<div class="col-12 py-20"> <div class="row m-0"> <div class="col-lg-12"> <p class="h3-dossier"> {% trans %} Suggestion de recherche {% endtrans %} :</p> </div> </div>
//                        <div class="row m-0"> <div class="col-lg-12"> {{suggesstions}}</div> </div> </div>',
//          '#context' => [
//            'suggesstions' => $suggestionsList,
//          ],
//        ];
//      }



      $suggestionsList = [];
      $filter['suggest'] = [
        '#type' => 'inline_template',
        '#weight' => '1',
        '#template' => '<div class="col-12 py-20"> <div class="row m-0"> <div class="col-lg-12"> <p class="h3-dossier"> {% trans %} Suggestion de recherche {% endtrans %} :</p> </div> </div> 
                      <div class="row m-0"> <div class="col-lg-12"> {{suggesstions}}</div> </div> </div>',
        '#context' => [
          'suggesstions' => $suggestionsList,
        ],
      ];
    }
    return $filter;

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
//    return true;
    return false;
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

//    $this->query->setParam('suggest', $suggest);



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
