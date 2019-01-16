<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchPager;


use Drupal\Core\Url;
use Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase;
use JasonGrimes\Paginator;

/**
 * Plugin implementation of the xtcsearch_pager.
 *
 * @XtcSearchPager(
 *   id = "page",
 *   label = @Translation("Page"),
 *   description = @Translation("Page pager for XTC Search.")
 * )
 */
class Page extends XtcSearchPagerPluginBase
{

  /**
   * @var Paginator
   */
  protected $paginator;

  protected function getLibraries():array {
    return ['xtcsearch/pager_page'];
  }

  protected function buildPager(){
    $this->pager['container']['page_number'] = $this->pageNumber;
    $this->pager['submit']['container_pagination'] = [
      '#prefix' => '<div class="row justify-content-center"> <nav> <div id="ajax_pagination" 
class="pagination"> ',
      '#suffix' => '</div> </nav></div>',
      '#weight' => 1000,
    ];

    $this->paginator = new Paginator($this->settings['total'], $this->settings['size'], $this->settings['page']);
    $this->paginator->setMaxPagesToShow(10);
    $this->paginator->setNextText('Next');
    $this->paginator->setPreviousText('Previous');
    $this->toForm();
  }

  public function toForm() {
    $form = [];
    $request = \Drupal::request();
    $query = $request->query->all();
    if($previous = $this->paginator->getPrevPage()){
      $query['page_number'] = $this->paginator->getPrevPage();
      $link = Url::fromRoute($this->getRouteName(), $query)
        ->toString();
      $form['prev'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link',
              'btn',
            ],
          'onclick' => 'window.location = "' . $link . '"; return false;',
        ],
        '#states' => [
          'disabled' => [
            'input[name="page_number"]' => ['value' => $previous],
          ],
          'visible' => [
            'input[name="page_number"]' => ['!value' => $previous],
          ],
        ],
      ];
    }
    foreach ($this->paginator->getPages() as $key => $page) {
      $query['page_number'] = $page['num'];
      if ($page['num'] == '...' && $key < ($this->paginator->getMaxPagesToShow()/2)){
        $query['page_number'] = $this->paginator->getCurrentPage() - $this->paginator->getMaxPagesToShow() + 2;
      }
      if ($page['num'] == '...' && $key > ($this->paginator->getMaxPagesToShow()/2)){
        $query['page_number'] = $this->paginator->getCurrentPage() + $this->paginator->getMaxPagesToShow() -2;
      }
      $link = Url::fromRoute($this->getRouteName(), $query)
        ->toString();
      $form[$key] = [
        '#type' => 'submit',
        '#value' => $page['num'],
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
              $page['isCurrent'] ? 'active' : 'not-active',
              $page['isCurrent'] ? 'disabled': '',
            ],
          'onclick' => 'window.location = "' . $link . '"; return false;',
        ],
      ];
    }
    if($next = $this->paginator->getNextPage()){
      $query['page_number'] = $this->paginator->getNextPage();
      $link = Url::fromRoute($this->getRouteName(), $query)
        ->toString();
      $form['next'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
            ],
          'onclick' => 'window.location = "' . $link . '"; return false;',
        ],
        '#states' => [
          'disabled' => [
            'input[name="page_number"]' => ['value' => $next],
          ],
          'visible' => [
            'input[name="page_number"]' => ['!value' => $next],
          ],
        ],
      ];
    }
    $this->pager['submit']['container_pagination']['ajax_pagination'] = $form;
  }

}
