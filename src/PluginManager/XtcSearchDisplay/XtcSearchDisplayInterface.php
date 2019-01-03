<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchDisplay;

/**
 * Interface for xtcsearch_display plugins.
 */
interface XtcSearchDisplayInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
