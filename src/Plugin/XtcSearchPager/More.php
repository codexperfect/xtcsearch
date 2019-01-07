<?php

namespace Drupal\xtcsearch\Plugin\XtcSearchPager;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerPluginBase;

/**
 * Plugin implementation of the xtcsearch_pager.
 *
 * @XtcSearchPager(
 *   id = "more",
 *   label = @Translation("More"),
 *   description = @Translation("More pager for XTC Search.")
 * )
 */
class More extends XtcSearchPagerPluginBase
{

  /**
   * @var integer
   */
  protected $numberOfPages;

  protected function getLibraries():array {
    return ['xtcsearch/pager_more'];
  }

  protected function buildPager(){
    $this->numberOfPages = ceil($this->settings['total'] / $this->settings['size']);
    $this->numberOfPages = $this->numberOfPages > 1 ? $this->numberOfPages : 1;

    $this->pager['submit'] = [
      '#type' => 'submit', //onclick on this one page ++
      '#value' => 'En voir plus',
      '#attributes' => [
        'class' =>
          [
            'see-more-news',
            'btn btn-secondary',
          ],
        'onclick' => 'this.form["page_number"].value = parseInt(this.form["page_number"].value) + 1;',
      ],
      '#ajax' => [
        'callback' => [$this->xtcSearchForm, 'pagerCallback'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Chargement des résultats ') . '...',
        ],
      ],
      '#states' => [
        'visible' => [
          'input[name="page_number"]' => ['!value' => $this->numberOfPages],
        ],
      ],
      '#prefix' => '<div id="pagination" class="row mb-50 mx-0"> <div class="col-12 text-center">',
      '#suffix' => '</div> </div>',
      //      '#weight' => count($this->results) + 100, //Granted to be the last element
    ];
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function callBack(array $form, FormStateInterface $form_state){
    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('#all-items', $form['container']['elements']['items']['results']));

    $removePagination = ($form_state->getUserInput()['page_number'] == (ceil($this->settings['total'] / $this->settings['size'])));
    if ($removePagination) {
      $response->addCommand(new RemoveCommand('#pagination'));
    }
    return $response;
  }

}
