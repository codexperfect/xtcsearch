<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchDisplay;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines a plugin manager to deal with xtcsearch_displays.
 *
 * Modules can define xtcsearch_displays in a MODULE_NAME.xtcsearch_displays.yml file contained
 * in the module's base directory. Each xtcsearch_display has the following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     label: STRING
 *     description: STRING
 * @endcode
 *
 * @see \Drupal\xtcsearch\PluginManager\XtcSearchDisplay\XtcSearchDisplayDefault
 * @see \Drupal\xtcsearch\PluginManager\XtcSearchDisplay\XtcSearchDisplayInterface
 * @see plugin_api
 */
class XtcSearchDisplayPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The xtcsearch_display id. Set by the plugin system based on the top-level YAML key.
    'id' => '',
    // The xtcsearch_display label.
    'label' => '',
    // The xtcsearch_display description.
    'description' => '',
    // Default plugin class.
    'class' => 'Drupal\xtcsearch\PluginManager\XtcSearchDisplay\XtcSearchDisplayDefault',
  ];

  /**
   * Constructs XtcSearchDisplayPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('xtcsearch_display_info');
    $this->setCacheBackend($cache_backend, 'xtcsearch_display_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('xtcsearch_displays', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery->addTranslatableProperty('description', 'description_context');
    }
    return $this->discovery;
  }

}
