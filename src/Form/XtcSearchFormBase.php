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
use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcSearchFilterDefault;
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

  public function __construct(array $definition){
    $this->definition = $definition;
  }

  protected function getSearchId(){
    return $this->definition['pluginId'];
  }

  protected function init() {
    $this->filters = $this->definition['filters'];
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

      if($filter->hasSuggest()){
        $this->form['container']['container_'.$container][$filter->getFilterId().'_suggest'] = $filter->getSuggest();
        $this->form['container']['container_'.$container][$filter->getFilterId().'_suggest']['#weight'] = $weight;
      }

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
      if($filter->hasCompletion()){
        $this->suggests[$filter->getFilterId()]['completion_'.$filter->getFilterId()] =
          $filter->initCompletion();
      }
      if($filter->hasSuggest()){
        $this->suggests[$filter->getFilterId()]['suggest_' .$filter->getFilterId()] =
          $filter->initSuggest();
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
    ];

    $bottom = $top = $filterElement;
    $top['#weight'] = -100;
    $top['#prefix'] = $this->getButtonPrefix('filter_top');
    $top['#suffix'] = $this->getButtonSuffix('filter_top');
    $this->form['container']['container_sidebar']['buttons']['filter_top'] = $top;

    $bottom['#weight'] = 100;
    $bottom['#prefix'] = $this->getButtonPrefix('filter_bottom');
    $bottom['#suffix'] = $this->getButtonSuffix('filter_bottom');
    $this->form['container']['container_sidebar']['filter_bottom'] = $bottom;
  }

  protected function getHeaderButton() {
    $this->form['container']['container_header']['total'] = [
      '#type' => 'xtctotal',
      '#markup' => '<div id="total"> ' . $this->pagination['total'] . t(' Résultat(s)') . '</div>',
      '#weight' => '2',
    ];
  }

  protected function getContainers() {
    $this->form['container'] = [
      '#prefix' => $this->getContainerPrefix('main'),
      '#suffix' => $this->getContainerSuffix('main'),
    ];

    $this->containerElements();
    $this->containerHeader();
    $this->containerSidebar();
  }

  protected function getContainerPrefix($container){
    return Config::getPrefix('container', $this->getDisplay(), $container);
  }
  protected function getContainerSuffix($container){
    return Config::getSuffix('container', $this->getDisplay(), $container);
  }
  protected function getButtonPrefix($container){
    return Config::getPrefix('button', $this->getDisplay(), $container);
  }
  protected function getButtonSuffix($container){
    return Config::getSuffix('button', $this->getDisplay(), $container);
  }
  protected function getNavPrefix($container){
    return Config::getPrefix('navigation', $this->getDisplay(), $container);
  }
  protected function getNavSuffix($container){
    return Config::getSuffix('navigation', $this->getDisplay(), $container);
  }
  protected function getItemsPrefix($container){
    return Config::getPrefix('items', $this->getDisplay(), $container);
  }
  protected function getItemsSuffix($container){
    return Config::getSuffix('items', $this->getDisplay(), $container);
  }

  protected function containerHeader() {
    $name = 'header';
    $this->form['container']['container_'.$name] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
      '#weight' => -10,
    ];
  }

  protected function containerSidebar() {
    $name = 'sidebar';
    $containerName = 'container_'.$name;
    $this->form['container'][$containerName] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
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
      '#prefix' => $this->getButtonPrefix('hide'),
      '#suffix' => $this->getButtonSuffix('hide'),
    ];
    $this->form['container'][$containerName]['buttons'] = [
      '#prefix' => $this->getButtonPrefix('buttons'),
      '#suffix' => $this->getButtonSuffix('buttons'),
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
      '#prefix' => $this->getButtonPrefix('reset'),
      '#suffix' => $this->getButtonSuffix('reset'),
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
    $name = 'top';
    $containerName = 'navigation_'.$name;
    $this->form['container']['elements'][$containerName] = [
      '#type' => 'container',
      '#prefix' => $this->getNavPrefix('top'),
      '#suffix' => $this->getNavSuffix('top'),
      '#weight' => '-10',
    ];
    $this->form['container']['elements'][$containerName]['buttons'] = [
      '#type' => 'container',
      '#prefix' => '<div class="float-left"><span class="events-date">'
                   . $this->navigation['current']
                   . '</span></div>'
                   .$this->getNavPrefix('top_buttons'),
      '#suffix' => $this->getNavSuffix('top_buttons'),
      '#weight' => '1',
    ];
    $this->form['container']['elements'][$containerName]['buttons']['prev'] = [
      '#type' => 'button',
      '#value' => '',
      '#weight' => '-1',
      '#attributes' => [
        'class' => ['prev-month'],
        'onclick' => 'window.location = "' . $this->navigation['previous']['link'] . '"; return false;',
      ],
    ];
    $this->form['container']['elements'][$containerName]['buttons']['next'] = [
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
      '#prefix' => $this->getNavPrefix('bottom'),
      '#suffix' => $this->getNavSuffix('bottom'),
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
      '#prefix' => $this->getNavPrefix('bottom_prev'),
      '#suffix' => $this->getNavSuffix('bottom_prev'),
    ];
    $this->form['container']['elements']['bottomNav']['next'] = [
      '#type' => 'button',
      '#value' => $this->navigation['next']['label'],
      '#weight' => '1',
      '#attributes' => [
        'class' => ['next-month'],
        'onclick' => 'window.location = "' . $this->navigation['next']['link'] . '"; return false;',
      ],
      '#prefix' => $this->getNavPrefix('bottom_next'),
      '#suffix' => $this->getNavSuffix('bottom_next'),
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
        '#prefix' => $this->getItemsPrefix('results'),
        '#suffix' => $this->getItemsSuffix('results'),
        '#weight' => 0,
      ];
      $this->getResults();
    }
  }

  protected function buildEmptyResultMessage($msg_none, $msg_reset){
    $name = 'noresults';
    $this->form['container']['elements']['items'][$name] = [
      '#type' => 'container',
      '#prefix' => $this->getItemsPrefix($name),
      '#suffix' => $this->getItemsSuffix($name),
      '#weight' => '0',
    ];
    $this->form['container']['elements']['items'][$name]['message'] = [
      '#type' => 'item',
      '#markup' => $msg_none,
    ];
    $this->form['container']['elements']['items'][$name]['reset']['button'] = [
      '#type' => 'button',
      '#value' => $this->t('Réinitialiser ma recherche'),
      '#weight' => '0',
      '#attributes' => [
        'class' => ['btn', 'btn-light', 'd-block', 'd-lg-inline-block', 'mt-4', 'mt-lg-0', 'ml-lg-5', 'm-0'],
        'onclick' => 'window.location = "' . $this->resetLink() . '"; return false;',
      ],
      '#prefix' => $this->getItemsPrefix($name.'_button')
                   . '<div class="reset-txt"><p>' . $msg_reset . '</p></div>',
      '#suffix' => $this->getItemsSuffix($name.'_button'),
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
    $name = 'content';
    $this->form['container']['elements'] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
      '#weight' => 0,
    ];
    $this->form['container']['elements']['items'] = [
      '#prefix' => $this->getItemsPrefix('items'),
      '#suffix' => $this->getItemsSuffix('items'),
      '#weight' => 0,
    ];
  }

  public function getRouteName(){
    return $this->definition['routeName'];
  }

  /**
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function resetLink(){
    $resetRoute = $this->definition['resetRoute'];
    return Url::fromRoute($resetRoute)->toString();
  }

  public function searchRoute(){
    return Url::fromRoute($this->getRouteName())
               ->toString();
  }

  protected function getItemsTheme(){
    return $this->definition['items']['theme'] ?? 'xtc_search_item';
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
    $filter = $this->getFilter($name);
    return $filter->getFilterType();
  }

  protected function getFilter($name) : XtcSearchFilterDefault{
    $filters = \Drupal::service('plugin.manager.xtcsearch_filter');
    return  $filters->createInstance($name);
  }

  protected function getDisplay(){
    return $this->definition['display'];
  }

}