<?php

/**
 * @file
 * Contains \Drupal\multiselect\Plugin\Field\FieldWidget\MultiselectWidget.
 */

namespace Drupal\multiselect\Plugin\Field\FieldWidget;

use Drupal\options\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'multiselect' widget.
 *
 * @FieldWidget(
 *   id = "multiselect",
 *   label = @Translation("Multiselect"),
 *   field_types = {
 *     "list_text",
 *     "list_float",
 *     "list_integer",
 *     "user_reference",
 *     "node_reference",
 *     "entity_reference",
 *     "taxonomy_term_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class MultiselectWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Prepare some properties for the child methods to build the actual form element.
    $this->required = $element['#required'];
    $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $this->has_value = isset($items[0]->{$this->column});

    $options = $this->getOptions($items[$delta]);
    $selected = $this->getSelectedOptions($items);

    $element += array(
      '#type' => 'multiselect',
      '#size' => $this->getSetting('size'),
      '#options' => $options,
      '#multiple' => $this->multiple,
      '#key_column' => $this->column,
      '#default_value' => $selected,
    );
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   */
  public static function validateElement(array $element, array &$form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      \Drupal::formBuilder()->setError($element, $form_state, t('!name field is required.', array('!name' => $element['#title'])));
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.

    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = array($element['#value']);
    }

    // Filter out the 'none' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('_none', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = array();
    foreach ($values as $value) {
      $items[] = array($element['#key_column'] => $value);
    }
    NestedArray::setValue($form_state['values'], $element['#parents'], $items);
  }

}
