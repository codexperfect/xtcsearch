<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchFilterType;


use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Plugin implementation of the xtcsearch_filter_type.
 *
 */
abstract class XtcSearchIterativeFilterType extends XtcSearchFilterTypePluginBase
{

  public function getFilter(){
    $options = $this->getOptions();
    $element = [
      '#title' => $this->getTitle(),
      '#type' => 'fieldset',
    ];

    if(!empty($options)){
      foreach ($options as $key => $option){
        $this->itemName = $this->pluginId.'_'.$option['type'].'_'.$option['machineName'];
        $element[$this->itemName . $option['suffix']] = [
          '#type' => 'container',
          '#weight' => $key,
          '#name' => $this->getFieldName(),
        ];

        $parentName = $this->itemName;
        foreach ($option['children'] as $id => $child) {
          $child['eid'] = $id;
          $this->itemName = $this->pluginId.'_'.$child['type'].'_'.$child['machineName'];
          $element[$parentName][$child['type'].'_'.$child['machineName']] = $this->iterateLevels($child);
        }
      }
    }
    return $element;
  }

  public function getOptions(){
    if ($results = $this->getAggregationBuckets()) {
      foreach ($results['buckets'] as $key => $result) {
        $this->options[$key] = $this->buildCurrent($result, 0, $key);
        $current = $this->options[$key];

        if(!empty($current['children'])){
          foreach ($current['children'] as $id => $child){
            $sublevel = $this->getSubLevel($child, $current['level'] +1, $id);
            $this->options[$key]['children'][$id] = $sublevel;
          }
        }
      }
    }
    return $this->options;
  }

  protected function buildCurrent($current, $level = 0, $id = 0) {
    $item = parent::buildCurrent($current, $level, $id);
    $next = $this->getAggregations()[$level+1]['name'] ?? null;
    if ( !empty($item)
      && !empty($item[$next]['buckets'])
    ) {
      $item['children'] = $item[$next]['buckets'];
      foreach ($item['children'] as $bid => $bucket){
        $item['children'][$bid]['parent'] = $item['machineName'];
        $item['children'][$bid]['parent_id'] = $item['key'];
      }

    }
    return $item;
  }

  protected function getName($current, $level = 0, $id = 0){
    $type = $this->getAggregations()[$level]['name'];
    return  ('index' == $type) ? explode('_', $current['key'])[0] : $current['key'];
  }

  protected function iterateLevels($level){
    if(empty($level['visible'])) {
      $element = $this->buildHidenLevel($level);
    }
    $element[$this->itemName . $level['suffix']] = $this->buildLevel($level);

    if(!empty($level['children'])){
      $this->parentName = $this->itemName;
      foreach ($level['children'] as $child){
        if($child['level'] > 0) {
          $element[$this->itemName . $child['suffix']] = $this->buildLastLevel($level);
        }
      }
    }
    return $element;
  }

  protected function buildLevel($level){
    $element = [
      '#type' => 'xtc_checkboxes',
    ];
    $element['#attributes']['data-parent'] = 'parent_' . $level['key'];
    $element['#options'] = [$level['name'] => $level['label']];
    $element['#name'] = $this->itemName . $level['suffix'];
    $element['#weight'] = $this->weight($level['eid'], $level['level']);
    if($default = array_flip($this->getDefault()[$level['type']])){
      $element['#default_value'][] = $level['value'];
    }
    if(empty($level['children'])){
      $element['#attributes']['class'] = ['form-group'];
    }
    return $element;
  }
  /**
   * @param $parent
   * @param int $level
   * @param int $id
   *
   * @return array
   */
  protected function getSubLevel($parent, $level = 0, $id = 0) {
    $current = $this->buildCurrent($parent, $level, $id) ?? $parent;
    $next = $this->getAggregations()[$level+1]['name'] ?? null;
    if(!empty($current['children'])){
      foreach ($current['children'] as $id => $child){
        if(!empty($next)){
          $current['children'][$id] = $this->getSubLevel($child, $current['level'] + 1, $id);
        }
      }
    }
    return $current;
  }

  protected function buildLastLevel($level){
    $element = [
      '#type' => 'xtc_checkboxes',
    ];
    $element['#attributes']['data-child'] = 'parent_'.$level['key'];
    $element['#options'] = $this->getOptionsFromChildren($level['children']);
    $element['#name'] = $this->itemName . $this->getAggregations()[$level['level']+1]['suffix'];
    $element['#weight'] = $this->weight($level['eid'], $level['level']+1);
    $element['#attributes']['class'] = ['form-group'];

    foreach ($this->getDefault() as $values) {
      foreach ($values as $value) {
        if(key_exists($value, $element['#options'])){
        $element['#default_value'][] = $value;
        }
      }
    }
    return $element;
  }

  protected function buildHidenLevel($level){
    $element = [
      '#type' => 'container',
      '#weight' =>  $this->weight($level['eid'], $level['level']),
      '#name' => $this->getFieldName(),
    ];
    return $element;
  }

  protected function weight($value, $level){
    return pow(10, $level) + ($value +1);
  }

  protected function getOptionsFromChildren($children){
    $options = [];
    foreach ($children as $child){
      $options[$child['name']] = $child['label'];
    }
    return $options;
  }

  protected function buildNoChildren($current, $level = 0, $id = 0){
    $agg = $this->getAggregations();
    $nochildren = $agg[$current['level']]['nochildren'];
    if(!empty($nochildren[$current['value']])
      && !empty($agg[$level]['nochildren'][$current['machineName']])
    ){
      $child = $current;
      $child['level'] = $level+1;
      $child['name'] = $agg[$level]['nochildren'][$current['machineName']];
      $child['parent_id'] = $current['key'] ;
      $child['key'] = $current['machineName'] ;
      $child['prefix'] = $agg[$level+1]['prefix'] ;
      $child['suffix'] = $agg[$level+1]['suffix'] ;
      $child['value'] = $current['value'] ;
      $child['label'] = $child['name'] . ' (' . $current['doc_count'] . ')';
      $id = 0;
      $current['children'] = [
        $id => $this->getSubLevel($child, $current['level'] + 1, $id),
      ];
      $newName = $current['name'] . '_parent';
      $current['longkey'] = $current['name'] = $current['machineName'] =
      $current['value'] = $newName;
      return $current;
    }
  }

}
