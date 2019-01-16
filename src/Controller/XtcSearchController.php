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
   */

  /**
   * Handler for autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string                                    $searchId
   * @param string                                    $string
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleAutocomplete($searchId) {
    $items = Config::getAutocomplete($searchId);
    return New JsonResponse($items);
  }

  public function getTitle() {
    $route = \Drupal::routeMatch();
    return $route->getRouteObject()->getDefaults()['_title'];
  }

//  protected function getType() {
//    return 'document';
//  }

}
