<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchPager;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xtcsearch\Form\XtcSearchFormBase;

/**
 * Base class for xtcsearch_pager plugins.
 */
abstract class XtcSearchPagerPluginBase extends PluginBase
  implements XtcSearchPagerInterface, PluginInspectionInterface
{

  /**
   * @var array;
   */
  var $pageNumber = [];

  /**
   * @var array;
   */
  var $pager = [];

  /**
   * @var array;
   */
  var $libs = [];

  /**
   * @var XtcSearchFormBase
   */
  var $xtcSearchForm;

  /**
   * @var array
   *
   * An associative array of additional URL options, with the
   * following elements:
   * - 'from'
   * - 'size'
   * - 'total'
   * - 'page'
   * - 'pagerSize'
   * - 'next'
   * - 'previous'
   */
  protected $settings = [
    'from' => 0,
    'size' => 5,
    'total' => 0,
    'page' => 1,
    'pagerSize' => 10,
    'next' => 'Next',
    'previous' => 'Previous',
    'masonry' => FALSE,
  ];

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->initPageNumber();
    $this->loadLibraries();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function getPager($pageNumber){
    $this->initPageNumber($pageNumber);
    $this->buildPager();
    return $this->pager;
  }

  protected function getRouteName(){
    return $this->xtcSearchForm->getRouteName();
  }

  protected function searchRoute($query = []){
    return $this->xtcSearchForm->searchRoute($query);
  }

  public function getLibs(){
    return $this->libs;
  }

  public function getSettings(){
    return $this->settings;
  }

  public function set($name, $value){
    $this->settings[$name] = $value;
  }

  public function initPageNumber($pageNumber = null){
    $this->pageNumber = [
      '#type' => 'hidden',
      '#value' => $pageNumber ?? $this->settings['page'],
      '#attributes' => [
        'id' => ['page_number'],
      ],
    ];
  }

  public function getPageNumber(){
    return $this->pageNumber;
  }

  protected function buildPager(){
  }

  protected function getLibraries():array {
    return [];
  }
  protected function loadLibraries(){
    $this->libs = $this->getLibraries();
  }

  /**
   * @param \Drupal\xtcsearch\Form\XtcSearchFormBase $xtcSearchForm
   */
  public function setXtcSearchForm(XtcSearchFormBase &$xtcSearchForm): void {
    $this->xtcSearchForm = $xtcSearchForm;
  }

  public function callBack(array $form, FormStateInterface $form_state) {
    // TODO: Implement callBack() method.
    return New AjaxResponse();
  }

}
