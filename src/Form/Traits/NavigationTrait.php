<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:48
 */

namespace Drupal\xtcsearch\Form\Traits;


use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Trait NavigationTrait
 *
 * use PrefixSuffixTrait;
 * use RouteTrait;
 *
 * @package Drupal\xtcsearch\Form\Traits
 */
trait NavigationTrait
{

  /**
   * @var array
   */
  protected $navigation;

  /**
   * @var array
   */
  protected $nav = [];

  /**
   * @var array
   */
  protected $links = [];


  protected function initNav(){
    if(!empty($this->definition['nav'])){
      foreach ($this->definition['nav'] as $name => $value) {
        $this->nav[$name] = $value;
      }
    }
  }

  protected function getNavigation() {
    if (!empty($this->nav['top_navigation']) || !empty($this->nav['bottom_navigation'])) {
      $this->getNav();
    }
    if (!empty($this->nav['top_navigation'])) {
      $this->getTopNavigation();
    }
    if (!empty($this->nav['bottom_navigation'])) {
      $this->getBottomNavigation();
    }

    if (!empty($this->nav['top_link']) || !empty($this->nav['bottom_link'])) {
      $this->preProcessLinks();
      $this->buildLinks();
    }
  }

  public function getNav() {
    $this->navigation['current'] = '';
    $this->navigation['previous']['label'] = 'previous';
    $this->navigation['previous']['link'] = $this->searchRoute();
    $this->navigation['next']['label'] = 'next';
    $this->navigation['next']['link'] = $this->searchRoute();
  }

  protected function preProcessLinks(){
    $links = [
      'top_link' => $this->nav['top_link'] ?? [],
      'bottom_link' => $this->nav['bottom_link'] ?? [],
    ];
    foreach($links as $name => $link){
      if(!empty($link['type'])){
        $this->links[$name] = [
          'type' => $link['type'] ?? '',
          'label' => $link['label'] ?? 'View',
          'route' => $link['route'] ?? '',
          'url' => $link['url'] ?? '',
          'path' => $link['path'] ?? '',
          'parameters' => $link['parameters'] ?? [],
          'options' => $link['options'] ?? [],
        ];
      }
    }
  }

  protected function buildLinks() {
    foreach($this->links as $name => $link) {
      switch($link['type']){
        case 'route':
          $url = $this->buildLinkFromRoute($link);
          break;
        case 'url':
          $url = $this->buildLinkFromUrl($link);
          break;
        case 'path':
          $url = $this->buildLinkFromPath($link);
          break;
        default:
      }
      if(!empty($url)){
        $this->form['xtc_links'][$name] = [
          'label' => $link['label'],
          'url' => $url,
        ];
      }
    }
  }

  protected function buildLinkFromRoute($link){
    return Url::fromRoute($link['route'], $link['parameters'])
              ->toString();
  }
  protected function buildLinkFromUrl($link){
    return Url::fromUri($link['url'], ['absolute' => true])->toString();
  }
  protected function buildLinkFromPath($link){
    return Url::fromUri($link['path'], ['absolute' => false])->toString();
  }

  protected function getTopNavigation() {
    $name = 'top';
    $containerName = 'navigation_'.$name;
    $this->form['container']['elements'][$containerName] = [
      '#type' => 'container',
      '#prefix' => $this->getNavPrefix('top'),
      '#suffix' => $this->getNavSuffix('top'),
      '#weight' => '-10',
    ];
    $this->form['container']['elements'][$containerName]['buttons'] = [
      '#type' => 'container',
      '#prefix' => '<div class="float-left"><span class="events-date">'
                   . $this->navigation['current']
                   . '</span></div>'
                   .$this->getNavPrefix('top_buttons'),
      '#suffix' => $this->getNavSuffix('top_buttons'),
      '#weight' => '1',
    ];
    $this->form['container']['elements'][$containerName]['buttons']['prev'] = [
      '#type' => 'button',
      '#value' => '',
      '#weight' => '-1',
      '#attributes' => [
        'class' => ['prev-month'],
        'onclick' => 'window.location = "' . $this->navigation['previous']['link'] . '"; return false;',
      ],
    ];
    $this->form['container']['elements'][$containerName]['buttons']['next'] = [
      '#type' => 'button',
      '#value' => '',
      '#weight' => '1',
      '#attributes' => [
        'class' => ['next-month'],
        'onclick' => 'window.location = "' . $this->navigation['next']['link'] . '"; return false;',
      ],
    ];
  }

  protected function getBottomNavigation() {
    $this->form['container']['elements']['bottomNav'] = [
      '#type' => 'container',
      '#prefix' => $this->getNavPrefix('bottom'),
      '#suffix' => $this->getNavSuffix('bottom'),
      '#weight' => '1000',
    ];
    $this->form['container']['elements']['bottomNav']['prev'] = [
      '#type' => 'button',
      '#value' => $this->navigation['previous']['label'],
      '#weight' => '-1',
      '#attributes' => [
        'class' => ['prev-month'],
        'onclick' => 'window.location = "' . $this->navigation['previous']['link'] . '"; return false;',
      ],
      '#prefix' => $this->getNavPrefix('bottom_prev'),
      '#suffix' => $this->getNavSuffix('bottom_prev'),
    ];
    $this->form['container']['elements']['bottomNav']['next'] = [
      '#type' => 'button',
      '#value' => $this->navigation['next']['label'],
      '#weight' => '1',
      '#attributes' => [
        'class' => ['next-month'],
        'onclick' => 'window.location = "' . $this->navigation['next']['link'] . '"; return false;',
      ],
      '#prefix' => $this->getNavPrefix('bottom_next'),
      '#suffix' => $this->getNavSuffix('bottom_next'),
    ];
  }


}