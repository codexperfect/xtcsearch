<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 11:38
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\xtc\XtendedContent\API\Config;

trait PrefixSuffixTrait
{
  protected function getContainerPrefix($container){
    return Config::getPrefix('container', $this->getDisplay(), $container);
  }
  protected function getContainerSuffix($container){
    return Config::getSuffix('container', $this->getDisplay(), $container);
  }
  protected function getButtonPrefix($container){
    return Config::getPrefix('button', $this->getDisplay(), $container);
  }
  protected function getButtonSuffix($container){
    return Config::getSuffix('button', $this->getDisplay(), $container);
  }
  protected function getNavPrefix($container){
    return Config::getPrefix('navigation', $this->getDisplay(), $container);
  }
  protected function getNavSuffix($container){
    return Config::getSuffix('navigation', $this->getDisplay(), $container);
  }
  protected function getItemsPrefix($container){
    return Config::getPrefix('items', $this->getDisplay(), $container);
  }
  protected function getItemsSuffix($container){
    return Config::getSuffix('items', $this->getDisplay(), $container);
  }

  protected function getDisplay(){
    return $this->definition['display'];
  }
  protected function loadDisplay(){
    return Config::loadXtcDisplay($this->definition['display']);
  }

}