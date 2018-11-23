<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\csoec_common\EsService;
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
  var $libs = [];


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

  public function getFieldName() {
    return $this->getPluginId();
  }

  public function buildCheckboxFilter(){
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
    return $this->prepareOptionsFromBuckets();
  }

  public function getDefault() {
    return \Drupal::request()->get($this->getPluginId());
  }

  public function toQueryString($input) {
    return $input[$this->getPluginId()];
  }

  protected function getLibraries():array {
    return ['xtcsearch/filter'];
  }

  public function getLibs(){
    return $this->libs;
  }

  protected function loadLibraries(){
    $this->libs = $this->getLibraries();
  }

  public function addAggregation(){
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface){
//      $es = $this->form->getElastica();
//      if($es instanceof EsService){
//        $es->addAggregation($this->getFieldName(), $this->getFieldName());
//      }

      $terms = new Terms($this->getFieldName());
      $terms->setField($this->getFieldName());
      $terms->setSize(100);


      $this->form->getElastica()
        ->getQuery()
        ->addAggregation($terms);

    }
  }

  public function getAggregationBuckets() {
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface) {
      $search = $this->form->getSearch();
      if(!empty($search->getAggregation($this->getFieldName()))){
        return $search->getAggregation($this->getFieldName())['buckets'];
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
    $options = [];
    if($result = $this->getAggregationBuckets()){
      foreach ($result as $option) {
        $options[$option['key']] = $option['key'] . ' (' . $option['doc_count'] . ')';
      }
    }
    return $options;
  }

}
