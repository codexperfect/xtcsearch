<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 31/10/2018
 * Time: 16:33
 */

namespace Drupal\xtcsearch\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;
use Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase;
use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\Type;

abstract class XtcSearchFormBase extends FormBase implements XtcSearchFormInterface {

  /**
   * @var array
   */
  protected $form;

  /**
   * @var XtcSearchFormBase
   */
  protected $fullForm;

  /**
   * @var array
   */
  protected $definition;

  /**
   * @var array
   */
  protected $navigation;

  /**
   * @var array
   */
  protected $musts;

  /**
   * @var array
   */
  protected $suggests;

  /**
   * @var array
   */
  protected $musts_not;

  protected $results;

  /**
   * @var \Elastica\Search
   */
  protected $search;

  /**
   * @var \Elastica\ResultSet
   */
  protected $resultSet;

  protected $searched = FALSE;

  protected $isCallback = FALSE;

  /**
   * @var array
   *
   * An associative array of additional URL options, with the
   * following elements:
   * - 'from'
   * - 'size'
   * - 'total'
   * - 'page'
   */
  protected $pagination = [
    'top_navigation' => FALSE,
    'bottom_navigation' => FALSE,
    'from' => 0,
    'size' => 5,
    'total' => 0,
    'page' => 1,
    'masonry' => TRUE,
  ];

  /**
   * @var array
   */
  protected $filters = [];

//  /**
//   * @var array
//   */
//  protected $mainFilters = [];

  /**
   * @var array
   */
  protected $nav = [];

  /**
   * @var \Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase
   */
  protected $pager;

  /**
   * @var Client
   */
  protected $elastica;

  /**
   * @var Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getSearchId() . '_form';
  }

  abstract protected function getSearchId();

  protected function init() {
    $this->definition = \Drupal::service('plugin.manager.xtcsearch')
      ->getDefinition($this->getSearchId());

    $this->filters = $this->definition['filters'];
//    $this->mainFilters = $this->definition['mainfilters'];
    if(!empty($this->definition['pager'])){
      foreach ($this->definition['pager'] as $name => $value) {
        $this->pagination[$name] = $value;
      }
    }
    if(!empty($this->definition['nav'])){
      foreach ($this->definition['nav'] as $name => $value) {
        $this->nav[$name] = $value;
      }
    }

    $this->initElastica();
    $this->query = New Query();
    $this->search = New Search($this->elastica);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->form = $form;

    $this->init();
    $form_state->cleanValues();
    $form_state->setCached(FALSE);
    $form_state->setRebuild(TRUE);
    $this->searched = FALSE;

    $this->getContainers();

    $this->getCriteria();
    $this->getResultSet();

//    $this->getMainfilters();
    $this->getFilters();
    $this->getFilterButton();
    $this->getHeaderButton();

    $this->getItems();
    $this->getNavigation();

    return $this->form;
  }

  protected function initElastica() {
    $serverName = $this->definition['server'];
    $settings = Settings::get('csoec.serve_client')['xtc']['serve_client']['server'];
    if(!empty($settings[$serverName]['env'])){
      $env = $settings[$serverName]['env'];
    }

    $server = \Drupal::service('plugin.manager.xtc_server')->getDefinition($serverName);
    $connection = [
      'host' => $server['connection'][$env]['host'],
      'port' => $server['connection'][$env]['port'],
    ];
    $this->elastica = New Client($connection);
  }

  /**
   * @return \Elastica\Client
   */
  public function getElastica() : Client {
    return $this->elastica;
  }

  /**
   * @return \Elastica\Query
   */
  public function getQuery() : Query {
    return $this->query;
  }

  protected function buildQuery() {
    $must = [];
    foreach ($this->musts as $request) {
      if (!empty($request)) {
        $must['query']['bool']['must'][] = $request;
      }
    }
    $this->query->setRawQuery($must);

    $this->setIndices();
    $this->setFrom();
    $this->setSize();
    return $this;
  }

  protected function buildSuggest(){
    if(!empty($this->suggests)){
      $suggest = [];
      foreach ($this->suggests as $pluginName => $suggestion) {
        if (!empty($suggestion)) {
          $suggest = array_merge($suggest, $suggestion);
        }
      }
      $this->query->setParam('suggest', ['suggest' => $suggest]);
    }
  }

  protected function setIndices(){
    foreach($this->definition['index'] as $indexName){
      $index = New Index($this->elastica, $indexName);
      $this->buildType($index);
      $this->search->addIndex($index);
    }

  }

  /**
   * @param \Elastica\Index $index
   *
   * @return \Elastica\Type
   */
  protected function buildType(Index $index){
    return New Type($index, $this->definition['type']);
  }

  protected function setFrom(){
    $this->query->setParam('from', $this->pagination['from']);
  }

  protected function setSize(){
    $this->query->setParam('size', $this->pagination['size']);
  }

  protected function getFilters() {
    $weight = 0;
    foreach ($this->filters as $name => $container) {
      $weight++;
      $filter = $this->loadFilter($name);
      $filter->setForm($this);
      $this->form['container']['container_'.$container][$filter->getFilterId()] =
        $filter->getFilter();
      $this->form['container']['container_'.$container][$filter->getFilterId()]['#weight'] = $weight;

      foreach ($filter->getLibs() as $lib) {
        $this->form['#attached']['library'][] = $lib;
      }
      $this->form['#attached']['drupalSettings']['xtcsearch']['pager'] = $this->pagination;
    }
  }

  /**
   * @return \Elastica\ResultSet
   */
  public function getResultSet() : ResultSet{
    $request = \Drupal::request();
    if (empty($this->resultSet)
      || !$this->searched
    ) {
      $this->pagination['page'] = (!empty($request->get('page_number'))) ? $request->get('page_number') : 1;
      $this->pagination['from'] = $this->pagination['size'] * ($this->pagination['page'] - 1);

      $this->buildQuery();
      $this->searchSort();
      $this->buildSuggest();
      $this->addAggregations();

      $this->resultSet = $this->search->search($this->query);

      $this->pagination['total'] = $this->resultSet->getTotalHits();
      $this->results = $this->resultSet->getDocuments();
      $this->searched = TRUE;
    }

    return $this->resultSet;
  }

  protected function searchSort(){
    if(!empty($this->definition['sort']['field'])
      && !empty($this->definition['sort']['dir'])
    ){
      $sortArgs = [$this->definition['sort']['field'] => $this->definition['sort']['dir']];
      $this->query->setSort($sortArgs);
    }
  }

  protected function addAggregations() {
    foreach ($this->filters as $name => $container) {
      $filter = $this->loadFilter($name);
      $filter->setForm($this);
      $filter->addAggregation();
    }
  }

  protected function getCriteria() {
    foreach ($this->filters as $name => $container) {
      $filter = $this->loadFilter($name);
      $filter->setForm($this);
      $this->musts[$filter->getFilterId()] = $filter->getRequest();
      if($filter->hasSuggest()){
        $this->suggests[$filter->getFilterId()] = $filter->initSuggest();
      }
    }
  }

  protected function getFilterButton() {
    $filterElement = [
      '#type' => 'submit',
      '#value' => $this->t('Filtrer'),
      '#attributes' => [
        'class' =>
          [
            'btn',
            'btn-dark',
            'filter-submit',
          ],
      ],
      '#prefix' => '<div class="col-12 mt-3"> <div class="form-group text-right">',
      '#suffix' => '</div> </div>',
    ];

    $this->form['container']['container_sidebar']['filter_bottom'] =
      $filterElement;
    $this->form['container']['container_sidebar']['filter_bottom']['#weight'] = 100;

    $this->form['container']['container_sidebar']['buttons']['filter_top'] =
      $filterElement;
    $this->form['container']['container_sidebar']['buttons']['filter_top']['#weight'] = -100;
    $this->form['container']['container_sidebar']['buttons']['filter_top']['#prefix'] = '<div class="col-6"> <div class="form-group text-left">';
  }

  protected function getHeaderButton() {
    $this->form['container']['container_header']['total'] = [
      '#type' => 'item',
      '#markup' => '<div id="total"> ' . $this->pagination['total'] . t(' Résultat(s)') . '</div>',
      '#weight' => '2',
    ];
  }

  protected function getContainers() {
    $this->form['container'] = [
      '#prefix' => ' <div class="row m-0" id="container-news-filter"> ',
      '#suffix' => '</div>',
    ];

    $this->containerElements();
    $this->containerHeader();
    $this->containerSidebar();
  }

  protected function containerHeader() {
    $this->form['container']['container_header'] = [
      '#prefix' => '<div id="mainfilter-div" class="col-12 mainfilter-div pt-3"> <div class="row">',
      '#suffix' => '</div> </div>',
      '#weight' => -10,
    ];
  }

  protected function containerSidebar() {
    $containerName = 'container_sidebar';
    $this->form['container'][$containerName] = [
      '#prefix' => '<div id="filter-div" class="order-1 order-md-2 mb-4 mb-md-0 col-12 col-md-4">
          <div class="row mr-md-0 h-100">
            <div class="col-12 filter-div pt-3">',
      '#suffix' => '</div> </div> </div>',
      '#weight' => 1,
    ];
    $this->form['container'][$containerName]['hide'] = [
      '#type' => 'button',
      '#value' => $this->t('Cacher les filtres'),
      '#weight' => '-1',
      '#attributes' => [
        'class' =>
          [
            'filter-button',
            'filter-button-active',
          ],
        'id' => 'filter-button-sm',
      ],
      '#prefix' => '<div class="col-12 mt-3 mb-3 d-block d-md-none"> <div class="text-center text-sm-right d-block">',
      '#suffix' => '</div> </div>',
    ];
    $this->form['container'][$containerName]['buttons'] = [
      '#prefix' => '<div class="row col-12 mt-3 pr-md-0">',
      '#suffix' => '</div>',
    ];
    $this->form['container'][$containerName]['buttons']['reset'] = [
      '#type' => 'button',
      '#value' => $this->t('Réinitialiser'),
      '#weight' => '0',
      '#attributes' => [
        'class' =>
          [
            'button-reset',
            'd-inline-block p-1',
          ],
        'onclick' => 'window.location = "' . $this->resetLink() . '"; return false;',
      ],
      '#prefix' => '<div class="col-6 text-right mt-1 pr-md-0">',
      '#suffix' => '</div>',
    ];
  }

  protected function getNavigation() {
    if (!empty($this->nav['top_navigation']) || !empty($this->nav['bottom_navigation'])) {
      $this->getNav();
    }
    if (!empty($this->nav['top_navigation'])) {
      $this->getTopNavigation();
    }
    if (!empty($this->nav['bottom_navigation'])) {
      $this->getBottomNavigation();
    }
  }

  public function getNav() {
    $this->navigation['current'] = '';
    $this->navigation['previous']['label'] = 'previous';
    $this->navigation['previous']['link'] = Url::fromRoute($this->getRouteName())
      ->toString();
    $this->navigation['next']['label'] = 'next';
    $this->navigation['next']['link'] = Url::fromRoute($this->getRouteName())
      ->toString();
  }

  protected function getTopNavigation() {
    $this->form['container']['elements']['topNav'] = [
      '#type' => 'container',
      '#prefix' => '<div class="row mx-0 mb-30"><div class="col-12 px-0 px-md-15">',
      '#suffix' => '</div></div>',
      '#weight' => '-10',
    ];
    $this->form['container']['elements']['topNav']['buttons'] = [
      '#type' => 'container',
      '#prefix' => '<div class="float-left">
                  <span class="events-date">' . $this->navigation['current'] . '</span>
                </div>
                <div class="float-right">',
      '#suffix' => '</div>',
      '#weight' => '1',
    ];
    $this->form['container']['elements']['topNav']['buttons']['prev'] = [
      '#type' => 'button',
      '#value' => '',
      '#weight' => '-1',
      '#attributes' => [
        'class' => ['prev-month'],
        'onclick' => 'window.location = "' . $this->navigation['previous']['link'] . '"; return false;',
      ],
    ];
    $this->form['container']['elements']['topNav']['buttons']['next'] = [
      '#type' => 'button',
      '#value' => '',
      '#weight' => '1',
      '#attributes' => [
        'class' => ['next-month'],
        'onclick' => 'window.location = "' . $this->navigation['next']['link'] . '"; return false;',
      ],
    ];
  }

  protected function getBottomNavigation() {
    $this->form['container']['elements']['bottomNav'] = [
      '#type' => 'container',
      '#prefix' => '<div class="row mx-0 mb-50">
              <div class="col-12 bottom-months px-0 px-md-15">',
      '#suffix' => '</div></div>',
      '#weight' => '1000',
    ];
    $this->form['container']['elements']['bottomNav']['prev'] = [
      '#type' => 'button',
      '#value' => $this->navigation['previous']['label'],
      '#weight' => '-1',
      '#attributes' => [
        'class' => ['prev-month'],
        'onclick' => 'window.location = "' . $this->navigation['previous']['link'] . '"; return false;',
      ],
      '#prefix' => '<div class="float-left">',
      '#suffix' => '</div>',
    ];
    $this->form['container']['elements']['bottomNav']['next'] = [
      '#type' => 'button',
      '#value' => $this->navigation['next']['label'],
      '#weight' => '1',
      '#attributes' => [
        'class' => ['next-month'],
        'onclick' => 'window.location = "' . $this->navigation['next']['link'] . '"; return false;',
      ],
      '#prefix' => '<div class="float-right">',
      '#suffix' => '</div>',
    ];
  }


  protected function getPagination() {
    $type = \Drupal::service('plugin.manager.xtcsearch_pager');
    if(!empty($this->definition['pager'])){
      foreach ($this->definition['pager'] as $name => $value) {
        $this->pagination[$name] = $value;
      }
    }
    if(!empty($this->pagination['name'])){
      $this->pager = $type->createInstance($this->pagination['name']);
      $this->pager->setXtcSearchForm($this);
    }

    if($this->pager instanceof XtcSearchPagerPluginBase){
      foreach ($this->pagination as $name => $value){
        $this->pager->set($name, $value);
      }
      foreach ($this->pager->getLibs() as $lib) {
        $this->form['#attached']['library'][] = $lib;
      }
      $this->form['container']['page_number'] = [
        '#type' => 'hidden',
        '#value' => $this->pagination['page'],
        '#attributes' => [
          'id' => ['page_number'],
        ],
      ];
      $this->form['container']['elements']['items']['ajax_'.$this->pager->getPluginId()] = $this->pager->getPager();
    }
  }

  public function pagerCallback(array $form, FormStateInterface $form_state) {
    $this->isCallback = TRUE;
    $this->form = $form;

    $this->pagination['total'] = $this->getResultSet()->getTotalHits();

    $form_state->setCached(FALSE);
    $form_state->disableCache();
    $this->getCallbackResults();
    return $this->pager->callBack($this->form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function getCallbackResults(){
    $this->searched = false;
    $this->results = $this->getResultSet()->getDocuments();
    $this->containerElements();
    $this->getResults();
  }

  protected function getResults(){
    $this->preprocessResults();
    $this->getPagination();
    foreach ($this->results as $key => $result) {
      if($result instanceof Document){
        $result->id = $result->getId();
        $element = [
          '#theme' => $this->getItemsTheme(),
          '#response' => $result,
        ];
        $this->form['container']['elements']['items']['results'][] = [
          '#markup' => render($element),
        ];
      }
    }
  }

  protected function getItems(){
    if(empty($this->results)){
      $this->emptyResultMessage();
    }
    else{
      $this->form['container']['elements']['items']['results'] = [
        '#prefix' => '<div id="all-items" class="gallery-wrapper clearfix">
                <div class="col-sm-12 col-md-6 col-lg-4 grid-sizer px-0 px-md-3"></div>',
        '#suffix' => '</div>',
        '#weight' => 0,
      ];
      $this->getResults();
    }
  }

  protected function buildEmptyResultMessage($msg_none, $msg_reset){
    $this->form['container']['elements']['items']['no_results'] = [
      '#type' => 'container',
      '#prefix' => '<div class="row mx-0 mb-30"><div class="col-12 px-0 px-md-15 no-result">',
      '#suffix' => '</div></div>',
      '#weight' => '0',
    ];
    $this->form['container']['elements']['items']['no_results']['message'] = [
      '#type' => 'item',
      '#markup' => $msg_none,
    ];
    $this->form['container']['elements']['items']['no_results']['reset']['button'] = [
      '#type' => 'button',
      '#value' => $this->t('Réinitialiser ma recherche'),
      '#weight' => '0',
      '#attributes' => [
        'class' => ['btn', 'btn-light', 'd-block', 'd-lg-inline-block', 'mt-4', 'mt-lg-0', 'ml-lg-5', 'm-0'],
        'onclick' => 'window.location = "' . $this->resetLink() . '"; return false;',
      ],
      '#prefix' => '<div class="reset">
          <div class="chevron"></div>
          <div class="reset-txt"><p>' . $msg_reset . '</p></div>',
      '#suffix' => '</div>',
    ];
  }

  protected function emptyResultMessage(){
    $msg_none = '<p><span>Aucun contenu</span> trouvé</p>';
    $msg_reset = '<span>Réinitialiser ma recherche </span> et voir tous les contenus';
    $this->buildEmptyResultMessage($msg_none, $msg_reset);
  }

  protected function preprocessResults(){
  }

  protected function containerElements(){
    $this->form['container']['elements'] = [
      '#prefix' => '<div id="news-elements" class="col-12 p-0 order-2 order-md-1">',
      '#suffix' => '</div>',
      '#weight' => 0,
    ];
    $this->form['container']['elements']['items'] = [
      '#prefix' => '<div id="news-list-div">',
      '#suffix' => '</div>',
      '#weight' => 0,
    ];
  }

  public function getRouteName(){
    return 'xtcsearch.search';
  }

  /**
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function resetLink(){
    return Url::fromRoute($this->getRouteName())->toString();
  }

  public function searchRoute(){
    return Url::fromRoute($this->getRouteName())
               ->toString();
  }

  protected function getItemsTheme(){
    return 'xtc_search_item';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //$form_state->setLimitValidationErrors([]);
  }

  /**
   * @return array
   */
  public function getForm(): array {
    return $this->form;
  }

  protected function preprocessQueryString(array &$queryString){
  }

  protected function submitQueryString(array &$form, FormStateInterface $form_state){
    $input = $form_state->getUserInput();

    // Filters
    foreach ($this->filters as $name => $container) {
      $filter = $this->loadFilter($name);
      $queryString[$name] = $filter->toQueryString($input);
    }

    // Pager
    $request = \Drupal::request();
    $query = $request->query->all();
    unset($query['page_number']);
    if($query == $queryString){
      $queryString['page_number'] = ('pagerCallback' == $form_state->getTriggeringElement()['#ajax']['callback'][1])
        ? $form_state->getTriggeringElement()['#value']
        : $this->pagination['page'];
    }
    else {
      $queryString['page_number'] = 1;
    }

    return $queryString;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queryString = $this->submitQueryString($form, $form_state);
    $this->preprocessQueryString($queryString);
    $url = Url::fromRoute(
      $this->getRouteName(),
      $queryString
    );
    $form_state->setRedirectUrl($url);
  }

  protected function loadFilter($name) : XtcSearchFilterTypePluginBase{
    $filters = \Drupal::service('plugin.manager.xtcsearch_filter');
    $filter = $filters->createInstance($name);
    return $filter->getFilter();

  }
}