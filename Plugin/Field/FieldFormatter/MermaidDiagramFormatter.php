<?php

namespace Drupal\mermaid_diagram_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * @FieldFormatter(
 *   id = "mermaid_diagram_formatter",
 *   label = @Translation("Mermaid diagram"),
 *   field_types = {
 *     "mermaid_diagram"
 *   }
 * )
 */
class MermaidDiagramFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'enable_pan_zoom' => FALSE,
      'display_in_modal' => FALSE,
      'modal_link_text' => 'View diagram',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['display_in_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display in modal'),
      '#default_value' => $this->getSetting('display_in_modal'),
    ];

    $elements['modal_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal link text'),
      '#default_value' => $this->getSetting('modal_link_text'),
      '#maxlength' => 255,
      '#placeholder' => $this->t('View diagram'),
      '#description' => $this->t('Text for the modal open link. You can include @title as a placeholder for the item title.'),
      // Only show this when "Display in modal" is checked.
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][display_in_modal]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['enable_pan_zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pan & zoom'),
      '#default_value' => $this->getSetting('enable_pan_zoom'),
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display: @mode', [
      '@mode' => $this->getSetting('display_in_modal') ? $this->t('Modal') : $this->t('Inline'),
    ]);
    $summary[] = $this->t('Modal link text: @text', [
      '@text' => $this->getSetting('modal_link_text') ?: $this->t('View diagram'),
    ]);
    $summary[] = $this->t('Pan & zoom: @state', [
      '@state' => $this->getSetting('enable_pan_zoom') ? $this->t('On') : $this->t('Off'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $enable_pan = (bool) $this->getSetting('enable_pan_zoom');
    $as_modal   = (bool) $this->getSetting('display_in_modal');
    $label_tpl  = (string) $this->getSetting('modal_link_text');

    // Base libraries for inline render.
    $base_libs = ['mermaid_diagram_field/diagram'];
    if ($enable_pan) {
      $base_libs[] = 'mermaid_diagram_field/pan_zoom';
    }

    foreach ($items as $delta => $item) {
      if ($as_modal) {
        // Build link label: allow @title placeholder; otherwise fallback chain.
        $link_title = trim($label_tpl) !== ''
          ? str_replace('@title', (string) ($item->title ?? ''), $label_tpl)
          : ((string) ($item->title ?? '') ?: (string) $this->t('View diagram'));

        $url = Url::fromRoute('mermaid_diagram_field.modal', [
          'entity_type' => $items->getEntity()->getEntityTypeId(),
          'entity_id'   => $items->getEntity()->id(),
          'field_name'  => $items->getName(),
          'delta'       => $delta,
        ], [
          'query' => $enable_pan ? ['pz' => 1] : [],
        ]);

        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $link_title,
          '#url' => $url,
          '#attributes' => [
            'class' => ['use-ajax', 'mermaid-diagram-open'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => '90%']),
          ],
          '#attached' => [
            'library' => ['core/drupal.dialog.ajax'],
          ],
        ];
      }
      else {
        $elements[$delta] = [
          '#theme' => 'mermaid_diagram',
          '#mermaid' => $item->diagram,
          '#title' => $item->title,
          '#caption' => $item->caption,
          '#key' => $item->key,
          '#show_code' => $item->show_code,
          '#attached' => [
            'library' => $base_libs,
          ],
        ];
      }
    }

    return $elements;
  }

}
