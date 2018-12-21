<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 20/08/2018
 * Time: 16:37
 */

namespace Drupal\xtcsearch\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtcsearch\Form\XtcSearchForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends ControllerBase
{

  /**
   * @param $id
   *
   * @return array
   */
  public function search() {
    $form = Config::getSearch('xtc_search');
    return [
      '#theme' => 'csoec_agenda',
      '#response' => ['headline' => $this->getTitle()],
      '#form_events' => $form,
    ];
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, $field_name, $count) {
    $input = $request->query->get('q');
    $this->elastic = \Drupal::service('csoec_common.es');
    $search = $this->elastic->getConnection()
                            ->setIndex('contenu,document,publication')
                            ->addSuggest($input);
    $options = $search->getSuggests()['my-suggest-all'][0]['options'];

    $results = [];
    // Get the typed string from the URL, if it exists.
    for ($i = 0; $i < count($options); $i++) {
      $results[] = [
        'value' => '"' . $options[$i]['text'] . '"',
        'label' => '"' . $options[$i]['text'] . '"',
      ];
    }

    return new JsonResponse($results);
  }

  public function getTitle() {
    return 'Recherche';
  }

  protected function getType() {
    return 'document';
  }

}
