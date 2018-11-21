<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Component\Plugin\PluginBase;
use Drupal\csoec_common\EsService;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;

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
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
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

  public function addAggregation(){
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface){
      $es = $this->form->getElastica();
      if($es instanceof EsService){
        $es->addAggregation($this->getPluginId(), $this->getPluginId());
      }
    }
  }

  /**
   * @param $aggregation
   *
   * @return mixed
   */
  public function getAggregationBuckets($aggregation) {
    if(!empty($this->form) && $this->form instanceof XtcSearchFormInterface) {
      $search = $this->form->getSearch();
      if(!empty($search->getAggregation($aggregation))){
        return $search->getAggregation($aggregation)['buckets'];
      }
    }
    return null;

  }

  /**
   * @param string $aggregation
   *
   * @return array $options
   */
  public function prepareOptionsFromBuckets($aggregation) {
    $options = [];
    if ($result = $this->getAggregationBuckets($aggregation)){
      foreach ($result as $option) {
        $options[$option['key']] = $option['key'] . ' (' . $option['doc_count'] . ')';
      }
    }
    return $options;
  }
}
