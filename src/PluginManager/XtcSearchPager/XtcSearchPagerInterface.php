<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchPager;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for xtcsearch_pager plugins.
 */
interface XtcSearchPagerInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  public function callBack(array $form, FormStateInterface $form_state);

  public function getPager();

  public function getLibs();

  public function getSettings();

  public function set($name, $value);

}
