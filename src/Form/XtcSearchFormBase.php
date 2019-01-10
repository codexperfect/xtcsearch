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
use Drupal\Core\Url;
use Drupal\xtcsearch\Form\Traits\ContainerTrait;
use Drupal\xtcsearch\Form\Traits\FilterContainerTrait;
use Drupal\xtcsearch\Form\Traits\NavigationTrait;
use Drupal\xtcsearch\Form\Traits\PagerTrait;
use Drupal\xtcsearch\Form\Traits\PrefixSuffixTrait;
use Drupal\xtcsearch\Form\Traits\RouteTrait;
use Drupal\xtcsearch\SearchBuilder\XtcSearchBuilder;
use Elastica\Document;
use Elastica\Query;
use Elastica\ResultSet;

abstract class XtcSearchFormBase extends FormBase implements XtcSearchFormInterface
{

  use ContainerTrait;
  use FilterContainerTrait;
  use NavigationTrait;
  use PagerTrait;
  use PrefixSuffixTrait;
  use RouteTrait;

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

  protected $searched = FALSE;

  protected $isCallback = FALSE;

  /**
   * @var XtcSearchBuilder
   */
  protected $searchBuilder;

  /**
   * @var array
   */
  protected $results;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getSearchId() . '_form';
  }

  public function __construct(array $definition){
    $this->definition = $definition;
  }

  public function getSearchId(){
    return $this->definition['pluginId'];
  }

  protected function init() {
    $this->initFilters();
    $this->initNav();
    $this->initSearchBuilder();
  }

  protected function initSearchBuilder(){
    $this->searchBuilder = New XtcSearchBuilder($this);
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

    $this->searchBuilder->triggerSearch();

    $this->getFilters();
    $this->getHeaderButton();

    $this->getItems();
    $this->getNavigation();

    return $this->form;
  }

  public function getResultSet() : ResultSet{
    return $this->searchBuilder->getResultSet();
  }

  public function getQuery() : Query {
    return $this->searchBuilder->getQuery();
  }

  protected function getCallbackResults(){
    $this->searched = false;
    $this->results = $this->searchBuilder->getDocuments();
    $this->containerElements();
    $this->getResults();
  }

  protected function getItems(){
    $this->results = $this->searchBuilder->getDocuments();
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

  protected function getResults(){
    $this->preprocessResults();
    $this->getPagination();
    foreach ($this->results as $result) {
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
      $queryString[$filter->getQueryName()] = $filter->toQueryString($input);
    }

    // Pager
    $request = \Drupal::request();
    $query = $request->query->all();
    unset($query['page_number']);
    if($query == $queryString){
      $queryString['page_number'] = ('pagerCallback' == $form_state->getTriggeringElement()['#ajax']['callback'][1])
        ? $form_state->getTriggeringElement()['#value']
        : $this->searchBuilder->paginationGet('page');
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

  /**
   * @return array
   */
  public function getDefinition() : array {
    return $this->definition;
  }

}