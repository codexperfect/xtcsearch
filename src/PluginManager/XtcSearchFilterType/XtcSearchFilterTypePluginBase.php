<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilterType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtcsearch\Form\XtcSearchFormBase;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;
use Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcSearchFilterDefault;
use Elastica\Aggregation\Terms;
use Elastica\Exception\InvalidException;

/**
 * Base class for xtcsearch_filter_type plugins.
 */
abstract class XtcSearchFilterTypePluginBase extends PluginBase implements XtcSearchFilterTypeInterface
{

  /**
   * @var \Drupal\xtcsearch\Form\XtcSearchFormBase
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

  /**
   * @var XtcSearchFilterDefault
   */
  protected $filter;

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

  /**
   * @param XtcSearchFilterDefault $filter
   */
  public function setFilter(XtcSearchFilterDefault $filter) : void {
    $this->filter = $filter;
  }

  public function getFieldName() {
    return $this->filter->getFieldName();
  }

  public function getQueryName() {
    return $this->filter->getQueryName();
  }

  public function getFilterId() {
    return $this->filter->getPluginId();
  }

  public function getTitle() {
    return $this->filter->getTitle();
  }

  public function getPlaceholder() {
    return $this->filter->getPlaceholder();
  }

  public function getParams() {
    return $this->filter->getParams();
  }

  public function getRequest(){
    $request = \Drupal::request();
    $must = [];
    if(!empty($request->get($this->getQueryName())) ) {
      if (!is_array($request->get($this->getQueryName()))) {
        $filterRequest = $this->getDefault();
        $values = array_values($filterRequest);
      }
      else {
        $values = array_values($request->get($this->getQueryName()));
      }
    }
    if(!empty($values) ){
      foreach ($values as $key => $editor) {
        $must['bool']['should'][0][$key] = ['term' => [$this->getFieldName() => $editor]];
      }
    }
    return $must;
  }

  /**
   * @return XtcSearchFormInterface
   */
  public function getForm(): XtcSearchFormInterface {
    return $this->form;
  }

  /**
   * @param XtcSearchFormInterface $form
   */
  public function setForm(XtcSearchFormBase $form): void {
    $this->form = $form;
  }

  public function getOptions(){
    if (!empty($this->getAggregationBuckets()['buckets']) &&
        $result = $this->getAggregationBuckets()['buckets']
    ) {
      foreach ($result as $option) {
        $this->options[$option['key']] = $option['key'] . ' (' . $option['doc_count'] . ')';
      }
    }
    return $this->options;
  }

  public function getDefault() {
    if(!empty(\Drupal::request()->get($this->getQueryName()))){
      return Json::decode(\Drupal::request()->get($this->getQueryName()));
    }
  }

  public function toQueryString($input) {
    $value = $input[$this->getQueryName()] ?? \Drupal::request()->get
      ($this->getQueryName());
    if(!empty($value) && is_array($value) && !is_string($value)){
      $value = array_flip(array_flip(array_values($value)));
    }
    return Json::encode($value);
  }

  protected function getLibraries():array {
    return [
      'xtcsearch/filter',
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
      $this->form->getQuery()
        ->addAggregation($aggregations[0]);
    }
  }

  public function getAggregationBuckets() {
    $agg = [];
    if(!empty($this->form)
       && $this->form instanceof XtcSearchFormBase
    ) {
      $search = $this->form->getResultSet();
      $name = $this->getAggregations()[0]['name'];
      try{
        $agg = $search->getAggregation($name);
      }
      catch(InvalidException $e){
      }
      finally{
      }
    }
    return $agg;

  }

  public function hasSuggest() {
    return false;
  }

  public function hasCompletion() {
    return false;
  }

  public function initSuggest(){
  }

  public function initCompletion(){
  }

  public function getSuggest(){
  }

  protected function buildCurrent($current, $level = 0, $id = 0) {
    $current['longkey'] = $current['key'];

    $type = $this->getAggregations()[$level]['name'];
    $name = $this->getName($current, $level, $id);
    $current['name'] = $name;
    $current['machineName'] = Config::transliterate($name);
    unset($current['key']);

    $item = [
      'level' => $level,
      'key' => (empty($current['parent_id']))
        ? $this->titleId($id)
        : $this->titleId($id, $current['parent_id']),
      'type' => $type,
      'visible' => $this->getAggregations()[$level]['visible'],
      'prefix'  => $this->getAggregations()[$level]['prefix'],
      'suffix'  => $this->getAggregations()[$level]['suffix'],
    ];
    $item = array_merge($item, $current);

    if ( !empty($item['doc_count'])){
      $item['machineName'] = $current['machineName'];
      $item['value'] = $current['name'];
      $item['label'] = $current['label'] ?? $current['name'] . ' (' . $current['doc_count'] . ')';
    }
    return $item;
  }

  protected function getName($current, $level = 0, $id = 0){
    return $current['key'];
  }

  protected function titleId($value, $parent = ''){
    return (empty($parent)) ? ($value +1): $parent .'-'. ($value +1);
  }

}
