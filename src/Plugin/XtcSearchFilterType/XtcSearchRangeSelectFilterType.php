<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Elastica\Aggregation\Range;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "rangeSelect",
 *   label = @Translation("Range Select"),
 *   description = @Translation("Range Select filter."),
 * )
 */
class XtcSearchRangeSelectFilterType extends XtcSearchSelectFilterType
{

  public function getRequest() {
    if($value = $this->getDefault()){
      $options = $this->options()[$value];
      return [
        'range' => [
          $this->getFieldName() => [
            'gte' => $options['from'],
            'lte' => $options['to'],
          ],
        ],
      ];
    }
  }

  public function getFilter(){
    $element = parent::getFilter();
    $element['#attributes'] = [
      'class' => [
        'custom-select',
      ]
    ];
    return $element;
  }

  public function addAggregation(){
    $range = New Range($this->getFieldName());
    $range->setField($this->getFieldName());
    foreach ($this->options() as $name => $value) {
      $range->addRange($value['from'], $value['to'], $name);
    }
    $this->searchBuilder->getQuery()
               ->addAggregation($range);
  }

  public function getOptions() {
    $options = [];
    if (!empty($this->getAggregationBuckets()['buckets']) &&
        $results = $this->getAggregationBuckets()['buckets']
    ) {
      $options = [
        '' => t('None'),
      ];

      foreach($results as $result) {
        if(!empty($result['doc_count'])){
          $name = $this->options()[$result['key']]['from'];
          $opt[$name] = t($result['key']) . ' (' . $result['doc_count'] . ')';
        }
      }
      // Replacer dans l'ordre des options
      foreach($this->options() as $name => $value) {
        if(!empty($opt[$value['from']])){
          $options[$value['from']] = $opt[$value['from']];
        }
      }

    }
    return $options;
  }

  protected function options(){
    return [
      "Moins de 250" => ['from' => 0, 'to' => 249],
      'De 250 à 2 500' => ['from' => 250, 'to' => 2499],
      'De 2 500 à 10 000' => ['from' => 2500, 'to' => 9999],
      'De 10 000 à 100 000' => ['from' => 10000, 'to' => 99999],
      'Plus de 100 000' => ['from' => 100000, 'to' => 9999999],
    ];
  }

}
