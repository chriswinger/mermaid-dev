<?php

namespace Drupal\mermaid_diagram_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the Mermaid Diagram formatter.
 *
 * @FieldFormatter(
 *   id = "mermaid_diagram_formatter",
 *   label = @Translation("Mermaid diagram"),
 *   field_types = {
 *     "mermaid_diagram"
 *   }
 * )
 */
class MermaidDiagramFormatter extends FormatterBase {

  public static function defaultSettings() {
    return [
      'enable_pan_zoom' => FALSE,
      'display_in_modal' => FALSE,
    ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['display_in_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display in modal'),
      '#default_value' => $this->getSetting('display_in_modal'),
      '#description' => $this->t('Show a link that opens the diagram in a modal dialog.'),
    ];

    $elements['enable_pan_zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pan & zoom'),
      '#default_value' => $this->getSetting('enable_pan_zoom'),
      '#description' => $this->t('Attach svg-pan-zoom to the diagram (inline or in modal).'),
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display: @mode', [
      '@mode' => $this->getSetting('display_in_modal') ? $this->t('Modal') : $this->t('Inline'),
    ]);
    $summary[] = $this->t('Pan & zoom: @state', [
      '@state' => $this->getSetting('enable_pan_zoom') ? $this->t('On') : $this->t('Off'),
    ]);
    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $enable_pan = (bool) ($this->getSetting('enable_pan_zoom') ?? FALSE);
    $as_modal  = (bool) ($this->getSetting('display_in_modal') ?? FALSE);

    // Base libraries for inline render.
    $base_libs = ['mermaid_diagram_field/diagram'];
    if ($enable_pan) {
      $base_libs[] = 'mermaid_diagram_field/pan_zoom';
    }

    foreach ($items as $delta => $item) {
      if ($as_modal) {
        // Build a modal link. Pass ?pz=1 so the controller can load pan-zoom too.
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
          '#title' => $item->title ?: $this->t('View diagram'),
          '#url' => $url,
          '#attributes' => [
            'class' => ['use-ajax', 'button', 'mermaid-diagram-open'],
            // Optional: data-dialog options
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => '90%']),
          ],
          '#attached' => [
            'library' => [
              'core/drupal.dialog.ajax',
            ],
          ],
        ];
      }
      else {
        // Inline render (what you already had), with libs toggled by the setting.
        $elements[$delta] = [
          '#theme'   => 'mermaid_diagram',
          '#mermaid' => $item->diagram,
          '#title'   => $item->title,
          '#caption' => $item->caption,
          '#key'     => $item->key,
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
