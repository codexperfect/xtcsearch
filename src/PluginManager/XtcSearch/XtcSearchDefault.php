<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearch;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for xtc_searchs plugins.
 */
class XtcSearchDefault extends PluginBase implements XtcSearchInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
