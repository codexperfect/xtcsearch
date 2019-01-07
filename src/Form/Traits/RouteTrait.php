<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:53
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\Core\Url;

trait RouteTrait
{

  public function getRouteName(){
    return $this->definition['routeName'];
  }

  public function searchRoute(){
    return Url::fromRoute($this->getRouteName())
              ->toString();
  }

  /**
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function resetLink(){
    $resetRoute = $this->definition['resetRoute'];
    return Url::fromRoute($resetRoute)->toString();
  }


}