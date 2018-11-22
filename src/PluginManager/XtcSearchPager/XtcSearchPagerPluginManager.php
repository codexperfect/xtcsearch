<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchPager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * XtcSearchPager plugin manager.
 */
class XtcSearchPagerPluginManager extends DefaultPluginManager {

  /**
   * Constructs XtcSearchPagerPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/XtcSearchPager',
      $namespaces,
      $module_handler,
      'Drupal\xtcsearch\PluginManager\XtcSearchPager\XtcSearchPagerInterface',
      'Drupal\xtcsearch\Annotation\XtcSearchPager'
    );
    $this->alterInfo('xtcsearch_pager_info');
    $this->setCacheBackend($cache_backend, 'xtcsearch_pager_plugins');
  }

}
