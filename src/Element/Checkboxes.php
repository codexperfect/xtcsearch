<?php

namespace Drupal\xtcsearch\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes as CoreCheckboxes;

/**
 * Provides a form element for a set of checkboxes.
 *
 * Properties:
 * - #options: An associative array whose keys are the values returned for each
 *   checkbox, and whose values are the labels next to each checkbox. The
 *   #options array cannot have a 0 key, as it would not be possible to discern
 *   checked and unchecked states.
 *
 * Usage example:
 * @code
 * $form['high_school']['tests_taken'] = array(
 *   '#type' => 'checkboxes',
 *   '#options' => array('SAT' => $this->t('SAT'), 'ACT' => $this->t('ACT')),
 *   '#title' => $this->t('What standardized tests did you take?'),
 *   ...
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Checkbox
 *
 * @FormElement("xtc_checkboxes")
 */
class Checkboxes extends CoreCheckboxes {


  /**
   * Processes a checkboxes form element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : [];
    $element['#tree'] = TRUE;
    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
        $element['#default_value'] = [];
      }
      $weight = 0;
      foreach ($element['#options'] as $key => $choice) {
        // Integer 0 is not a valid #return_value, so use '0' instead.
        // @see \Drupal\Core\Render\Element\Checkbox::valueCallback().
        // @todo For Drupal 8, cast all integer keys to strings for consistency
        //   with \Drupal\Core\Render\Element\Radios::processRadios().
        if ($key === 0) {
          $key = '0';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += [$key => []];
        $element[$key] += [
          '#type' => 'xtc_checkbox',
          '#title' => $choice,
          '#return_value' => $key,
          '#default_value' => isset($value[$key]) ? $key : NULL,
          '#attributes' => $element['#attributes'],
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          // Errors should only be shown on the parent checkboxes element.
          '#error_no_message' => TRUE,
          '#weight' => $weight,
        ];
      }
    }
    return $element;
  }

}
