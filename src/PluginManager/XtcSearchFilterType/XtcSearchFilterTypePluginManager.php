<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilterType;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * XtcSearchFilterType plugin manager.
 */
class XtcSearchFilterTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs XtcSearchFilterTypePluginManager object.
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
      'Plugin/XtcSearchFilterType',
      $namespaces,
      $module_handler,
      'Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypeInterface',
      'Drupal\xtcsearch\Annotation\XtcSearchFilterType'
    );
    $this->alterInfo('xtcsearch_filter_type_info');
    $this->setCacheBackend($cache_backend, 'xtcsearch_filter_type_plugins');
  }

}
