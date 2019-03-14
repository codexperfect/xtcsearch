<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "comingEvents",
 *   label = @Translation("Coming events Range"),
 *   description = @Translation("Coming events Range filter."),
 * )
 */
class XtcSearchComingEventsFilterType extends XtcSearchDateRangeFilterType
{

  public function getRequest($default = []){
    $value = $this->getDefault();
    $debDate = strtotime($value['day'].'-'.$value['month'].'-'.$value['year']);
    $finDate = strtotime('+1 year', $debDate);
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

}
