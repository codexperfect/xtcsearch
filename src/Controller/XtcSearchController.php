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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class XtcSearchController extends ControllerBase
{

  public function search() {
    $form = Config::getSearch('xtc_search');
    return [
      '#theme' => 'xtc_search_form',
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
    return 'Search';
  }

  protected function getType() {
    return 'document';
  }

}
