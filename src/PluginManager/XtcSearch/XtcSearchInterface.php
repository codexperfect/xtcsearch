<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearch;

/**
 * Interface for xtc_search plugins.
 */
interface XtcSearchInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
