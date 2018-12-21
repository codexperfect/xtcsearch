<?php

namespace Drupal\xtcsearch\Element;

use Drupal\Core\Render\Element\Item;

/**
 * Provides a form element for a single checkbox.
 *
 * Properties:
 * - #return_value: The value to return when the checkbox is checked.
 *
 * Usage example:
 * @code
 * $form['copy'] = array(
 *   '#type' => 'xtctotal',
 *   '#title' => $this->t('Send me a copy'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Item
 *
 * @FormElement("xtctotal")
 */
class XtcTotal extends Item {

}
