<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines a plugin manager to deal with xtcsearch_filters.
 *
 * Modules can define xtcsearch_filters in a MODULE_NAME.xtcsearch_filters.yml file contained
 * in the module's base directory. Each xtcsearch_filter has the following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     description: STRING
 * @endcode
 *
 * @see \Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcsearchFilterDefault
 * @see \Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcsearchFilterInterface
 * @see plugin_api
 */
class XtcsearchFilterPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The xtcsearch_filter id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
    // The xtcsearch_filter label.
    'label' => '',
    // The xtcsearch_filter description.
    'description' => '',
    // Default plugin class.
    'class' => 'Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcsearchFilterDefault',
  ];

  /**
   * Constructs XtcsearchFilterPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('xtcsearch_filter_info');
    $this->setCacheBackend($cache_backend, 'xtcsearch_filter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('xtcsearch_filters', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery->addTranslatableProperty('description', 'description_context');
    }
    return $this->discovery;
  }

}
