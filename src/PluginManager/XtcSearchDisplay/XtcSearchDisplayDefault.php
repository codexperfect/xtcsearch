<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchDisplay;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for xtcsearch_displays plugins.
 */
class XtcSearchDisplayDefault extends PluginBase implements XtcSearchDisplayInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
