<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilterType;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;
use Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcsearchFilterDefault;
use Elastica\Aggregation\Terms;

/**
 * Base class for xtcsearch_filter_type plugins.
 */
abstract class XtcSearchFilterTypePluginBase extends PluginBase implements XtcSearchFilterTypeInterface
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

  /**
   * @var XtcsearchFilterDefault
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
   * @param XtcsearchFilterDefault $filter
   */
  public function setFilter(XtcsearchFilterDefault $filter) : void {
    $this->filter = $filter;
  }

  public function getFieldName() {
    return $this->filter->getFieldName();
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
    if(!empty($request->get($this->getFilterId())) ) {
      if (!is_array($request->get($this->getFilterId()))) {
        $filterRequest = $this->getDefault();
        $values = array_values($filterRequest);
      }
      else {
        $values = array_values($request->get($this->getFilterId()));
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
  public function setForm(XtcSearchFormInterface $form): void {
    $this->form = $form;
  }

  public function getOptions(){
    if ($result = $this->getAggregationBuckets()['buckets']) {
      foreach ($result as $option) {
        $this->options[$option['key']] = $option['key'] . ' (' . $option['doc_count'] . ')';
      }
    }
    return $this->options;
  }

  public function getDefault() {
    if(!empty(\Drupal::request()->get($this->getFilterId()))){
      return Json::decode(\Drupal::request()->get($this->getFilterId()));
    }
  }

  public function toQueryString($input) {
    $value = $input[$this->getFilterId()];
    if(!empty($value && is_array($value))){
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
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface) {
      $search = $this->form->getResultSet();
      $name = $this->getAggregations()[0]['name'];
      if(!empty($search->getAggregation($name))){
        return $search->getAggregation($name);
      }
    }
    return null;

  }

  public function hasSuggest() {
    return false;
  }

  public function initSuggest(){
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
