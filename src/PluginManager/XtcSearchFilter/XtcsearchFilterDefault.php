<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for xtcsearch_filters plugins.
 */
class XtcsearchFilterDefault extends PluginBase implements XtcsearchFilterInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
