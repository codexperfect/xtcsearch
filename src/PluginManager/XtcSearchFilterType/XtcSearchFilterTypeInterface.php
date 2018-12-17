<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilterType;

/**
 * Interface for xtcsearch_filter_type plugins.
 */
interface XtcSearchFilterTypeInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
