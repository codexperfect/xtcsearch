<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "dateRange",
 *   label = @Translation("Date Range"),
 *   description = @Translation("Date Range filter."),
 * )
 */
class XtcSearchDateRangeFilterType extends XtcSearchRangeFilterType
{

  const MONTH = [
    '01' => 'Janvier',
    '02' => 'Février',
    '03' => 'Mars',
    '04' => 'Avril',
    '05' => 'Mai',
    '06' => 'Juin',
    '07' => 'Juillet',
    '08' => 'Août',
    '09' => 'Septembre',
    '10' => 'Octobre',
    '11' => 'Novembre',
    '12' => 'Décembre',
  ];

  public function getRequest(){
    $value = $this->getDefault();
    $debDate = strtotime('01'.'-'.$value['month'].'-'.$value['year']);
    $finDate = strtotime('+1 month', $debDate);
    $format = 'd/m/Y';
    return [
      'range' => [
        'startDate' => [
          'gte' => date($format, $debDate),
          'lte' => date($format, $finDate),
          'format' => 'dd/MM/yyyy',
        ],
      ],
    ];
  }

  public function getFilter(){
    $default = $this->getDefault();
    $options = $this->getOptions();

    $container = [
      '#type' => 'fieldset',
      '#title' => 'Date',
      '#weight' => '1',
      '#prefix' => '<div class="mb-4">',
      '#suffix' => '</div>',
      '#field_prefix' => '<div class="form-row" id="date">',
      '#field_suffix' => '</div>',
    ];

    $container['month'] = [
      '#type' => 'select',
      '#options' => $options['month'],
      '#default_value' => $default['month'],
      '#weight' => '2',
      '#attributes' => [
        'class' =>
          [
            'custom-select',
          ],
      ],
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
    ];

    $container['year'] = [
      '#type' => 'select',
      '#options' => $options['year'],
      '#default_value' => $default['year'],
      '#weight' => '2',
      '#attributes' => [
        'class' =>
          [
            'custom-select',
          ],
      ],
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
    ];

    return $container;
  }

  public function toQueryString($input) {
    return [
      'month' =>  $input['month'] ?? date('m'),
      'year' => $input['year'] ?? date('Y'),
    ];
  }

  public function getOptions(){
    $thisYear = \Drupal::request()->get('year') ?? date('Y');
    $range = range($thisYear-3, $thisYear+2);
    return [
      'month' => self::MONTH,
      'year' => array_combine($range, $range),
    ];
  }

  public function getDefault() {
    return [
      'month' => \Drupal::request()->get('month') ?? date('m'),
      'year' => \Drupal::request()->get('year') ?? date('Y'),
    ];
  }

}
