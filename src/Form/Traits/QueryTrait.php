<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 16:14
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\Core\Site\Settings;
use Elastica\Client;
use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\Type;

/**
 * Trait QueryTrait
 *
 * use PaginationTrait;
 *
 * @package Drupal\xtcsearch\Form\Traits
 */
trait QueryTrait
{

  /**
   * @var Query
   */
  protected $query;

  /**
   * @var Search
   */
  protected $search;

  /**
   * @var \Elastica\ResultSet
   */
  protected $resultSet;

  protected $results;

  /**
   * @var Client
   */
  protected $elastica;

  /**
   * @var array
   */
  protected $musts;

  /**
   * @var array
   */
  protected $musts_not;


  protected function initQuery(){
    $this->query = New Query();
    $this->search = New Search($this->elastica);
  }

  protected function initSearch(){
    $this->search = New Search($this->elastica);
  }

  protected function initElastica() {
    $serverName = $this->definition['server'];
    $settings = Settings::get('csoec.serve_client')['xtc']['serve_client']['server'];
    $server = \Drupal::service('plugin.manager.xtc_server')->getDefinition($serverName);
    $env = $settings[$serverName]['env'] ?? $server['env'];
    $connection = [
      'host' => $server['connection'][$env]['host'],
      'port' => $server['connection'][$env]['port'],
    ];
    $this->elastica = New Client($connection);
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
      foreach ($this->suggests as $suggestion) {
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

  protected function setFrom(){
    $this->query->setParam('from', $this->pagination['from']);
  }

  protected function setSize(){
    $this->query->setParam('size', $this->pagination['size']);
  }

  /**
   * @return \Elastica\Client
   */
  public function getElastica() : Client {
    return $this->elastica;
  }

  /**
   * @param \Elastica\Index $index
   *
   * @return \Elastica\Type
   */
  protected function buildType(Index $index){
    return New Type($index, $this->definition['type']);
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



}