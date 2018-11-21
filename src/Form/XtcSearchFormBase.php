<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 31/10/2018
 * Time: 16:33
 */

namespace Drupal\xtcsearch\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\csoec_common\EsService;
use Elastica\Document;
use JasonGrimes\Paginator;

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
  protected $musts_not;

  protected $results;

  protected $search;

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
    'mode' => 'more', // Available: "more" or "page"
  ];

  /**
   * @var array
   */
  protected $filters = [];

  /**
   * @var
   */
  public $elastica;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getSearchId() . '_form';
  }

  abstract protected function getSearchId();

  protected function init() {
    $this->definition = \Drupal::service('plugin.manager.xtc_search')
      ->getDefinition($this->getSearchId());

    foreach ($this->definition['pagination'] as $name => $value) {
      $this->pagination[$name] = $value;
    }
    $this->filters = $this->definition['filters'];
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
    $this->getSearch();

    $this->getFilters();
    $this->getFilterButton();

    $this->getItems();
    $this->getNavigation();

    return $this->form;
  }

  public function getElastica() {
    // TODO from plugin.manager.xtc_search
    if ($this->elastica === NULL) {
      //      $this->elastica = \Drupal::service('csoec_common.es');
      //      $this->elastica->getConnection();
      return NULL;
    }
    return $this->elastica;
  }

  /**
   * @return \Elastica\ResultSet
   */
  public function getSearch() {
    $request = \Drupal::request();
    if (empty($this->search)
      || !$this->searched
    ) {
      $this->pagination['page'] = $request->get('page_number') ?? 1;
      $this->pagination['from'] = $this->pagination['size'] * ($this->pagination['page'] - 1);

      $must = [];
      foreach ($this->musts as $request) {
        if (!empty($request)) {
          $must['query']['bool']['must'][] = $request;
        }
      }

      $this->getElastica()
        ->setRawQuery($must)
        ->setIndex($this->definition['index'])
        ->setType($this->definition['type'])
        ->setFrom($this->pagination['from'])
        ->setSize($this->pagination['size'])
        ->setSort(
          $this->definition['sort']['field'],
          $this->definition['sort']['dir']
        );
      $this->addAggregations();
      $this->search = $this->getElastica()->search();

      $this->pagination['total'] = $this->search->getTotalHits();
      $this->results = $this->search->getDocuments();
      $this->searched = TRUE;
    }

    return $this->search;
  }

  protected function addAggregations() {
    foreach ($this->filters as $key => $name) {
      $type = \Drupal::service('plugin.manager.xtc_search_filter');
      $filter = $type->createInstance($name);
      $filter->setForm($this);
      $filter->addAggregation();
    }
  }

  protected function getFilters() {
    foreach ($this->filters as $key => $name) {
      $type = \Drupal::service('plugin.manager.xtc_search_filter');
      $filter = $type->createInstance($name);
      $filter->setForm($this);
      $this->form['container']['container_filters'][$filter->getPluginId()] = $filter->getFilter();
      $this->form['container']['container_filters'][$filter->getPluginId()]['#weight'] = $key;
    }
  }

  protected function getCriteria() {
    foreach ($this->filters as $key => $name) {
      $type = \Drupal::service('plugin.manager.xtc_search_filter');
      $filter = $type->createInstance($name);
      $filter->setForm($this);
      $this->musts[$filter->getPluginId()] = $filter->getRequest();
    }
  }

  protected function getFilterButton() {
    $this->form['container']['container_filters']['filtrer'] = [
      '#type' => 'submit', //onclick on this one: page reset to 0
      '#value' => $this->t('Filtrer'),
      '#attributes' => [
        'class' =>
          [
            'btn',
            'btn-dark',
            'filter-submit',
          ],
//        'onclick' => 'this.form["page_number"].value = 1;',
      ],
      '#prefix' => '<div class="col-12 mt-3"> <div class="form-group text-right">',
      '#suffix' => '</div> </div>',
      '#weight' => '3',
    ];
  }

  protected function getContainers() {
    $this->form['container'] = [
      '#prefix' => ' <div class="row m-0" id="container-news-filter"> ',
      '#suffix' => '</div>',
    ];

    $this->containerElements();
    $this->containerFilters();
  }

  protected function containerFilters() {
    $this->form['container']['container_filters'] = [
      '#prefix' => '<div id="filter-div" class="order-1 order-md-2 mb-4 mb-md-0 col-12 col-md-4">
          <div class="row mr-md-0 h-100">
            <div class="col-12 filter-div pt-3">',
      '#suffix' => '</div> </div> </div>',
      '#weight' => 1,
    ];
    $this->form['container']['container_filters']['hide'] = [
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
    $this->form['container']['container_filters']['reset'] = [
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
      '#prefix' => '<div class="col-12 text-right">',
      '#suffix' => '</div>',
    ];
  }

  protected function getNavigation() {
    if ($this->pagination['top_navigation'] || $this->pagination['bottom_navigation']) {
      $this->getNav();
    }

    if ($this->pagination['top_navigation']) {
      $this->getTopNavigation();
    }
    if ($this->pagination['bottom_navigation']) {
      $this->getBottomNavigation();
    }
  }

  public function getNav() {
    $this->navigation['current'] = '';
    $this->navigation['previous']['label'] = 'previous';
    $this->navigation['previous']['link'] = Url::fromRoute('xtcelastica.xtcsearch')
      ->toString();
    $this->navigation['next']['label'] = 'next';
    $this->navigation['next']['link'] = Url::fromRoute('xtcelastica.xtcsearch')
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
    $this->form['#attached']['library'][] = 'xtcsearch/filter';
    switch ($this->pagination['mode']) {
      case 'more':
        $this->form['#attached']['library'][] = 'xtcsearch/pager_more';
        $this->paginationMore();
        break;
      case 'page':
        $this->form['#attached']['library'][] = 'xtcsearch/pager_page';
        $this->paginationPage();
        break;
      default:
    }
    $this->form['#attached']['drupalSettings']['xtc']['pagination'] = $this->pagination;
  }

  protected function paginationMore() {
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

  protected function getResults(){
    $this->preprocessResults();
    $this->getPagination();
    foreach ($this->results as $key => $result) {
      if($result instanceof Document){
        $data = $result->getData();
        $data['id'] = $result->getId();
        $element = [
          '#theme' => $this->getItemsTheme(),
          '#response' => $data,
        ];
        $this->form['container']['elements']['items']['results'][] = [
          '#markup' => render($element),
        ];
      }
    }
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
//    return $this->form['container']['elements'];
  }

  /**
   * @return \Drupal\Core\GeneratedUrl|string
   */
  abstract protected function resetLink();

  public function pagerCallback(array $form, FormStateInterface $form_state) {
    $this->isCallback = TRUE;
//    $this->form = [];
    $this->form = $form;

    $this->pagination['total'] = $this->getSearch()->getTotalHits();
    $form_state->setCached(FALSE);
    $form_state->disableCache();
//    $this->getCallbackResults($form, $form_state);
    $this->getCallbackResults();
//    $this->getPagination();

    switch ($this->pagination['mode']) {
      case 'more':
        $removePagination = ($form_state->getUserInput()['page_number'] == (ceil($this->pagination['total'] / $this->pagination['size'])));
        return $this->pagerCallbackMore($removePagination);
        break;
      case 'page':
        $input = $form_state->getUserInput();
        $this->pagination['page'] = $input['page_number'];
        return $this->pagerCallbackPage();
        break;
      default:
    }
  }

  protected function getItemsTheme(){
    return 'xtc_search_item';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function getCallbackResults(){
    $this->searched = false;
    $this->results = $this->getSearch()->getDocuments();
    $this->containerElements();
    $this->getResults();
  }

  /**
   * @param array $results
   * @param bool $removePagination
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
//  public function pagerCallbackMore($results, $removePagination = false) {
  public function pagerCallbackMore($removePagination = FALSE) {
    $response = new AjaxResponse();
//    $response->addCommand(new AppendCommand('#news-list-div', $this->form['container']['elements']['results']));
    $response->addCommand(new AppendCommand('#all-items', $this->form['container']['elements']['items']['results']));

    if ($removePagination) {
      $response->addCommand(new RemoveCommand('#pagination'));
    }
    return $response;
  }

  /**
   * @param array $results
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function pagerCallbackPage() {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#news-list-div', $this->form['container']['elements']['items']['results']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //$form_state->setLimitValidationErrors([]);
  }

  protected function paginationPage(){
    /**
     * Es query from /
     */
    $this->form['container']['page_number'] = [
      '#type' => 'hidden',
      '#value' => $this->pagination['page'],
      '#attributes' => [
        'id' => ['page_number'],
      ],
    ];

    $this->form['container']['elements']['container_pagination'] = [
      '#prefix' => '<div class="row justify-content-center"> <nav>',
      '#suffix' => '</nav></div>',
      '#weight' => 100,
    ];
    $this->form['container']['elements']['container_pagination']['ajax_pagination'] = $this->getPager();
  }

  public function getPager() {
//    $paginator = new SearchFormPaginator($this->pagination['total'], $this->pagination['size'], $this->pagination['page']);
    $paginator = new Paginator($this->pagination['total'], $this->pagination['size'], $this->pagination['page']);
    $paginator->setMaxPagesToShow(10);
    $paginator->setNextText('Next');
    $paginator->setPreviousText('Previous');
//    return $paginator->toForm($this);
    return $this->buildPager($paginator);
  }

  public function buildPager(Paginator $paginator) {
    if ($paginator->getNumPages() <= 1) {
      return [];
    }

    $form['pagination'] = [
      '#prefix' => '<div id="ajax_pagination" class="pagination">',
      '#suffix' => '</div>',
    ];
    if ($paginator->getPrevPage()) {
      $form['pagination']['previous'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link',
              'btn',
            ],
          'onclick' => 'this.form["page_number"].value = parseInt(this.form["page_number"].value) - 1; ',
        ],
        '#ajax' => [
          'callback' => [$this, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
        '#states' => [
          'disabled' => [
            'input[name="page_number"]' => ['value' => '1'],
          ],
          'visible' => [
            'input[name="page_number"]' => ['!value' => '1'],
          ],
        ],
      ];
    }
    foreach ($paginator->getPages() as $key => $page) {
      $form['pagination'][$key] = [
        '#type' => 'submit',
        '#value' => htmlspecialchars($page['num']),
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
              $page['isCurrent'] ? 'active' : 'not-active',
            ],
          'onclick' => 'this.form["page_number"].value = this.value;',
        ],
        '#ajax' => [
          'callback' => [$this, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
        '#states' => [
          'disabled' => [
            'input[name="page_number"]' => ['value' => $page['num']],
          ],
        ],
      ];
//      if ($page['num'] == '...') {
//        $form['pagination'][$key]['#attributes']['class'][] = 'hide';
//      }
    }
    if ($paginator->getNextPage()) {
      $form['pagination']['next'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
            ],
          'onclick' => 'this.form["page_number"].value = parseInt(this.form["page_number"].value) + 1;',
        ],
        '#ajax' => [
          'callback' => [$this, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
        '#states' => [
          'disabled' => [
            'input[name="page_number"]' => ['value' => $paginator->getCurrentPage()],
          ],
          'visible' => [
            'input[name="page_number"]' => ['!value' => $paginator->getCurrentPage()],
          ],
        ],
      ];
    }
    return $form['pagination'];
  }

}