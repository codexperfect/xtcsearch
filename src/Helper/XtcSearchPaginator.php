<?php
/**
 * @author <maccherif2001@gmail.com>
 * @file SearchFormPaginator.php
 */

namespace Drupal\xtcsearch\Helper;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\xtcsearch\Form\XtcSearchFormInterface;
use Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase;
use JasonGrimes\Paginator as Paginator;

class XtcSearchPaginator extends Paginator {

  public function toForm(XtcSearchFormInterface &$xtcSearchForm) {
    $form = [];
    if($previous = $this->getPrevPage()){
      $form['prev'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link',
              'btn',
            ],
          'onclick' => 'this.form["page_number"].value = ' . $previous . '; ',
        ],
        '#ajax' => [
          'callback' => [$xtcSearchForm, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
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
    foreach ($this->getPages() as $key => $page) {
      $form[$key] = [
        '#type' => 'submit',
        '#value' => htmlspecialchars($page['num']),
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
              $page['isCurrent'] ? 'active' : 'not-active',
              $page['isCurrent'] ? 'disabled': '',
            ],
          'onclick' => 'this.form["page_number"].value = this.value;',
        ],
        '#ajax' => [
          'callback' => [$xtcSearchForm, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
        '#states' => [
//          'disabled' => [
//            'input[name="page_number"]' => ['value' => $page['num']],
//          ],
          'visible' => [
            'input[name="page_number"]' => ['!value' => $page['num']],
          ],
        ],
      ];
      if ($page['num'] == '...') {
        $form[$key]['#attributes']['class'][] = 'hide';
      }
    }
//    $numberOfPages = ceil($this->totalItems / $this->itemsPerPage);
//    $numberOfPages = $numberOfPages > 1 ? $numberOfPages : 1;

    if($next = $this->getNextPage()){
      $form['next'] = [
        '#type' => 'submit',
        '#value' => '',
        '#attributes' => [
          'class' =>
            [
              'page-link ',
              'btn',
            ],
          'onclick' => 'this.form["page_number"].value = ' . $next . ';',
        ],
        '#ajax' => [
          'callback' => [$xtcSearchForm, 'pagerCallback'],
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Chargement des résultats ') . '...',
          ],
        ],
        '#limit_validation_errors' => [],
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
    return $form;
  }
}
