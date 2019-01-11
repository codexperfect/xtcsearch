<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


/**
 * Plugin implementation of the es_filter.
 *
 * @XtcSearchFilterType(
 *   id = "thisMonth",
 *   label = @Translation("This month Range"),
 *   description = @Translation("This month Range filter."),
 * )
 */
class XtcSearchThisMonthFilterType extends XtcSearchDateRangeFilterType
{

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

}
