<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

/**
 * Interface for xtcsearch_filter plugins.
 */
interface XtcSearchFilterInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
