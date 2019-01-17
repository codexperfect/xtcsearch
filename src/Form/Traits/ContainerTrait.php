<?php
/**
 * Created by PhpStorm.
 * User: aisrael
 * Date: 2019-01-04
 * Time: 14:59
 */

namespace Drupal\xtcsearch\Form\Traits;

/**
 * Trait ContainerTrait
 *
 * use PrefixSuffixTrait;
 * use RouteTrait;
 *
 * @package Drupal\xtcsearch\Form\Traits
 */
trait ContainerTrait
{

  protected function getContainers() {
    $this->form['container'] = [
      '#prefix' => $this->getContainerPrefix('main'),
      '#suffix' => $this->getContainerSuffix('main'),
    ];

    $this->containerElements();
    $this->containerHeader();
    $this->containerSidebar();
  }

  protected function containerHeader() {
    $name = 'header';
    $this->form['container']['container_'.$name] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
      '#weight' => -10,
    ];
    $this->getDescription($name);
  }

  protected function getDescription($name) {
    $route = \Drupal::routeMatch();
    $parameters = $route->getRouteObject()->getDefaults();
    if(!empty($parameters['description'])){
      $this->form['container']['container_'.$name]['description'] = [
        '#type' => 'item',
        '#markup' => '<p>'.$parameters['description'].'</p>',
        '#prefix' => $this->getContainerPrefix('description'),
        '#suffix' => $this->getContainerSuffix('description'),
      ];
    }
  }

  protected function containerElements(){
    $name = 'content';
    $this->form['container']['elements'] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
      '#weight' => 0,
    ];
    $this->form['container']['elements']['items'] = [
      '#prefix' => $this->getItemsPrefix('items'),
      '#suffix' => $this->getItemsSuffix('items'),
      '#weight' => 0,
    ];
  }

  protected function containerSidebar() {
    $name = 'sidebar';
    $containerName = 'container_'.$name;
    $this->form['container'][$containerName] = [
      '#prefix' => $this->getContainerPrefix($name),
      '#suffix' => $this->getContainerSuffix($name),
      '#weight' => 1,
    ];
    if(!empty($this->loadDisplay()['hide'])){
      $this->form['container'][$containerName]['hide'] = [
        '#type' => 'button',
        '#value' => $this->t('Cacher les filtres'),
        '#weight' => '-1',
        '#attributes' => [
          'class' =>
            [
              'filter-button',
              'filter-button-active',
            ],
          'id' => 'filter-button-sm',
        ],
        '#prefix' => $this->getButtonPrefix('hide'),
        '#suffix' => $this->getButtonSuffix('hide'),
      ];
    }
    $this->form['container'][$containerName]['buttons'] = [
      '#prefix' => $this->getButtonPrefix('buttons'),
      '#suffix' => $this->getButtonSuffix('buttons'),
    ];
    if(!empty($this->loadDisplay()['reset'])){
      $this->form['container'][$containerName]['buttons']['reset'] = [
        '#type' => 'button',
        '#value' => $this->t('Réinitialiser'),
        '#weight' => '0',
        '#attributes' => [
          'class' =>
            [
              'button-reset',
              'd-inline-block p-1',
            ],
          'onclick' => 'window.location = "' . $this->resetLink() . '"; return false;',
        ],
        '#prefix' => $this->getButtonPrefix('reset'),
        '#suffix' => $this->getButtonSuffix('reset'),
      ];
    }
  }


}