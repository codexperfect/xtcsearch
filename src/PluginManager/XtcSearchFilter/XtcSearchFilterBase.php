<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;
use Elastica\Aggregation\Terms;

/**
 * Base class for XTC Search Filter plugins.
 */
abstract class XtcSearchFilterBase extends PluginBase implements XtcSearchFilterInterface
{

  /**
   * @var XtcSearchFormInterface
   */
  protected $form;

  /**
   * @var array;
   */
  protected $libs = [];

  /**
   * @var array
   */
  protected $options;

  protected $itemName;


  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loadLibraries();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function isIterative() {
    return FALSE;
  }

  public function getFieldName() {
    return $this->getPluginId();
  }

  public function getRequest(){
    $request = \Drupal::request();
    $must = [];
    if(!empty($request->get($this->getPluginId())) ) {
      if (!is_array($request->get($this->getPluginId()))) {
        $filterRequest = Json::decode($request->get($this->getPluginId()));
        $values = array_values($filterRequest);
      }
      else {
        $values = array_values($request->get($this->getPluginId()));
      }
    }
    if(!empty($values) ){
      foreach ($values as $key => $editor) {
        $must['bool']['should'][0][$key] = ['term' => [$this->getFieldName() => $editor]];
      }
    }
    return $must;
  }

  public function getFilter(){
    return $this->buildCheckboxFilter();
  }

  public function buildCheckboxFilter(){
    if($this->isIterative()){
      return $this->buildIterativeCheckboxFilter();
    }
    else{
      return $this->buildPlainCheckboxFilter();
    }
  }

  protected function buildIterativeCheckboxFilter(){
    $options = $this->getOptions();
    $element = [
      '#title' => $this->getTitle(),
      '#type' => 'fieldset',
    ];

    if(!empty($options)){
      foreach ($options as $key => $option){
        $this->itemName = $this->pluginId.'_'.$option['type'].'_'.$option['machineName'];
        $element[$this->itemName] = [
          '#type' => 'container',
          '#weight' => $key,
          '#name' => $this->getFieldName(),
        ];

        $parentName = $this->itemName;
        foreach ($option['children'] as $child) {
          $this->itemName = $this->pluginId.'_'.$child['type'].'_'.$child['machineName'];
          $element[$parentName][$child['type'].'_'.$child['machineName']] = $this->iterateLevels($child);
        }
      }
    }
    return $element;
  }

  protected function weight($value, $level){
    return pow(10, $level) + ($value +1);
  }

  protected function iterateLevels($level){
    if(empty($level['visible'])) {
      $element = $this->buildHidenLevel($level);
    }
    $element[$this->itemName] = $this->buildLevel($level);

    if(!empty($level['children'])){
      $this->parentName = $this->itemName;
      foreach ($level['children'] as $child){
        if($child['level'] > 1) {
          $element[$this->itemName . '_options'] = $this->buildLastLevel($level);
        }
      }
    }
    return $element;
  }

  protected function buildLevel($level){
    $element = [
      '#type' => 'checkboxes',
    ];
    $element['#attributes']['class'] = ['float-right'];
    $element['#attributes']['data-parent'] = 'parent_' . $level['key'];
    $element['#options'] = [$level['name'] => $level['label']];
    $element['#name'] = $this->itemName;
    $element['#weight'] = $this->weight($level['key'], $level['level']);
    foreach ($this->getDefault() as $default) {
      if(in_array($level['name'], $this->getDefault())){
        $element['#default_value'][] = $default;
      }
    }
    return $element;

  }

  protected function buildLastLevel($level){
    $element = [
      '#type' => 'checkboxes',
    ];
    if(is_int($level['parent_id'])){
      $element['#attributes']['data-child'] = 'parent_'.$level['parent_id'];
    }
    $element['#options'] = $this->getOptionsFromChildren($level['children']);
    $element['#name'] = $this->itemName.'_options';
    $element['#weight'] = $this->weight($level['key'], $level['level']+1);

    foreach ($this->getDefault() as $default) {
      if(in_array($default, array_flip($element['#options']))){
        $element['#default_value'][] = $default;
      }
    }
    return $element;
  }

  protected function buildHidenLevel($level){
    $element = [
      '#type' => 'container',
      '#weight' =>  $this->weight($level['key'], $level['level']),
      '#name' => $this->getFieldName(),
    ];
    return $element;
  }

  protected function getOptionsFromChildren($children){
    $options = [];
    foreach ($children as $child){
      $options[$child['name']] = $child['label'];
    }
    return $options;
  }

  protected function buildPlainCheckboxFilter(){
    $options = $this->getOptions();
    return [
      '#type' => 'checkboxes',
      '#title' => $this->getTitle(),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getDefault(),
    ];
  }

  public function buildSelectFilter(){
    return [
      '#type' => 'select',
      '#title' => $this->getTitle(),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getDefault(),
      '#attributes' => [
        'class' => [ 'custom-select'],
      ],
      '#prefix' => '<div class="col-12 mb-4">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * @return \Drupal\csoec_search\Form\SearchFormInterface
   */
  public function getForm(): XtcSearchFormInterface {
    return $this->form;
  }

  /**
   * @param \Drupal\csoec_search\Form\SearchFormInterface $form
   */
  public function setForm(XtcSearchFormInterface $form): void {
    $this->form = $form;
  }

  public function getOptions(){
    if($this->isIterative()){
      $this->prepareOptionsFromBuckets();
    }
    else{
      if($result = $this->getAggregationBuckets()['buckets']){
        foreach ($result as $option) {
          $this->options[$option['key']] = $option['key'] . ' (' . $option['doc_count'] . ')';
        }
      }
    }
    return $this->options;
  }

  public function getDefault() {
    Json::decode(\Drupal::request()->get($this->getPluginId()));
    return \Drupal::request()->get($this->getPluginId());
  }

  public function toQueryString($input) {
    $value = $input[$this->getPluginId()];
    if(!empty($value && is_array($value))){
      $value = array_flip(array_flip(array_values($value)));
    }
    return $value;
  }

  protected function getLibraries():array {
    return [
      'xtcsearch/filter',
//      'xtcsearch/checkbox',
    ];
  }

  public function getLibs(){
    return $this->libs;
  }

  protected function loadLibraries(){
    $this->libs = $this->getLibraries();
  }

  protected function getAggregations(){
    return [
      0 => [
        'name' => $this->getFieldName(),
        'field' => $this->getFieldName(),
        'size' => 100,
      ],
    ];
  }

  /**
   * @param $name
   * @param $field
   * @param $size
   *
   * @return \Elastica\Aggregation\Terms
   */
  public function initAggregation($name, $field, $size){
    if(!empty($this->form)){
      $terms = new Terms($name);
      $terms->setField($field);
      $terms->setSize($size);

      return $terms;
    }
  }

  public function addAggregation(){
    if(!empty($this->form) && !empty($aggs = $this->getAggregations())){
      foreach($aggs as $key => $agg){
        $aggregations[$key] = $this->initAggregation($agg['name'], $agg['field'], $agg['size']);
        if($key > 0 && $aggregation = $aggregations[$key-1]){
          if($aggregation instanceof Terms){
            $aggregation->addAggregation($aggregations[$key]);
          }
        }
      }

      $this->form->getElastica()
        ->getQuery()
        ->addAggregation($aggregations[0]);
    }
  }

  public function getAggregationBuckets() {
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface) {
      $search = $this->form->getSearch();
      $name = $this->getAggregations()[0]['name'];
      if(!empty($search->getAggregation($name))){
//        return $search->getAggregation($name)['buckets'];
        return $search->getAggregation($name);
      }
    }
    return null;

  }

  /**
   * @param string $aggregation
   *
   * @return array $options
   */
  public function prepareOptionsFromBuckets() {
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
  }

  protected function buildCurrent($current, $level = 0, $key = 0) {
    $name = explode('_', $current['key'])[0];
    $current['longkey'] = $current['key'];
    $current['name'] = $name;
    $current['machineName'] = self::transliterate($name);
    unset($current['key']);

    $type = $this->getAggregations()[$level]['name'];
    $next = $this->getAggregations()[$level+1]['name'] ?? null;
    $item = [
      'level' => $level,
      'key' => $key,
      'type' => $type,
      'visible' => $this->getAggregations()[$level]['visible'],
    ];
    $item = array_merge($item, $current);

    if ( !empty($item['doc_count'])){
      $item['machineName'] = $current['machineName'];
      $item['value'] = $current['name'];
      $item['label'] = $current['name'] . ' (' . $current['doc_count'] . ')';
    }

    if (!empty($item[$next]['buckets'])) {
      $item['children'] = $item[$next]['buckets'];
      foreach ($item['children'] as $bid => $bucket){
        $item['children'][$bid]['parent'] = $item['machineName'];
        $item['children'][$bid]['parent_id'] = $item['key'];
      }
    }
    if(!empty($item)){
      return $item;
    }
  }

  static protected function transliterate($phrase){
    $string = strtolower(\Drupal::transliteration()->transliterate($phrase));
    return str_replace(' ', '_', $string);
  }

  /**
   * @param $parent
   * @param int $level
   * @param int $key
   *
   * @return array
   */
  protected function getSubLevel($parent, $level = 0, $key = 0) {
    $current = $this->buildCurrent($parent, $level, $key);
    $next = $this->getAggregations()[$level+1]['name'] ?? null;
    if(!empty($current['children'])){
      $children = $current['children'];
      foreach ($children as $key => $child){
        if(!empty($next)){
          $current['children'][$key] = $this->getSubLevel($child, $current['level'] +1, $key);
        }
      }
    }
    return $current;
  }

}
