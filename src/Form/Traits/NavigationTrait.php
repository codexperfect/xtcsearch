<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:48
 */

namespace Drupal\xtcsearch\Form\Traits;


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
  }

  public function getNav() {
    $this->navigation['current'] = '';
    $this->navigation['previous']['label'] = 'previous';
    $this->navigation['previous']['link'] = Url::fromRoute($this->getRouteName())
                                               ->toString();
    $this->navigation['next']['label'] = 'next';
    $this->navigation['next']['link'] = Url::fromRoute($this->getRouteName())
                                           ->toString();
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