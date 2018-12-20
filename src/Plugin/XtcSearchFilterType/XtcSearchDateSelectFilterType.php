<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Elastica\Aggregation\DateRange;

/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "dateSelect",
 *   label = @Translation("Date Select"),
 *   description = @Translation("Date Select filter."),
 * )
 */
class XtcSearchDateSelectFilterType extends XtcSearchSelectFilterType
{

  public function getRequest() {
    if($published = $this->getDefault()){
      return [
        'range' => [
          $this->getFieldName() => [
            'gte' => $published,
            'lte' => 'now',
            'format' => 'dd/MM/yyyy',
          ],
        ],
      ];
    }
  }

  public function getFilter(){
//    $element = $this->buildSelectFilter();
    $element = parent::getFilter();
    $element['#attributes'] = [
      'class' => [
        'custom-select',
      ]
    ];
    return $element;
  }

  public function addAggregation(){
    $range = New DateRange($this->getFieldName());
    $range->setField($this->getFieldName());
    $range->setFormat('dd-MM-yyyy');
    foreach ($this->options() as $name => $value) {
      $range->addRange($value['from'], $value['to'], $name);
    }
    $this->form->getQuery()
               ->addAggregation($range);
  }

  public function getOptions() {
    $results = $this->getAggregationBuckets()['buckets'];
    $options = [
      '' => t('None'),
    ];

    foreach ($results as $result) {
      $name = $this->options()[$result['key']]['from'];
      $opt[$name] = t($result['key']) . ' (' . $result['doc_count'] . ')';
    }

    // Replacer dans l'ordre des options
    foreach ($this->options() as $name => $value) {
      $options[$value['from']] = $opt[$value['from']];
    }
    return $options;
  }

  protected function options(){
    return [
      "Aujourd'hui" => ['from' => 'now', 'to' => 'now'],
      'Une semaine' => ['from' => 'now-7d/d', 'to' => 'now'],
      '1 mois' => ['from' => 'now-1M/d', 'to' => 'now'],
      '3 mois' => ['from' => 'now-3M/d', 'to' => 'now'],
      '6 mois' => ['from' => 'now-6M/d', 'to' => 'now'],
    ];
  }

}
