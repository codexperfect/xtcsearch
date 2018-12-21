<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearch;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines a plugin manager to deal with xtc_searchs.
 *
 * Modules can define xtc_searchs in a MODULE_NAME.xtc_searchs.yml file contained
 * in the module's base directory. Each xtc_search has the following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     description: STRING
 * @endcode
 *
 * @see \Drupal\xtcsearch\PluginManager\XtcSearch\XtcSearchDefault
 * @see \Drupal\xtcsearch\PluginManager\XtcSearch\XtcSearchInterface
 * @see plugin_api
 */
class XtcSearchPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The xtc_search id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
    // The xtc_search label.
    'label' => '',
    // The xtc_search description.
    'description' => '',
    // Default plugin class.
    'class' => 'Drupal\xtcsearch\PluginManager\XtcSearch\XtcSearchDefault',
  ];

  /**
   * Constructs XtcSearchPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('xtcsearch_info');
    $this->setCacheBackend($cache_backend, 'xtcsearch_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('xtc_searchs', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery->addTranslatableProperty('description', 'description_context');
    }
    return $this->discovery;
  }

}
