<?php

namespace Drupal\xtcsearch\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Checkbox as CoreCheckbox;

/**
 * Provides a form element for a single checkbox.
 *
 * Properties:
 * - #return_value: The value to return when the checkbox is checked.
 *
 * Usage example:
 * @code
 * $form['copy'] = array(
 *   '#type' => 'checkbox',
 *   '#title' => $this->t('Send me a copy'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 *
 * @FormElement("xtc_checkbox")
 */
class Checkbox extends CoreCheckbox {

}
