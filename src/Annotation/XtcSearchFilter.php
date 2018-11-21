<?php

namespace Drupal\xtcsearch\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a XTC Search Filter item annotation object.
 *
 * @see \Drupal\xtcsearch\PluginManager\XtcSearchFilter\XtcSearchFilterManager
 * @see plugin_api
 *
 * @Annotation
 */
class XtcSearchFilter extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
