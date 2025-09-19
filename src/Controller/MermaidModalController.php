<?php

namespace Drupal\mermaid_diagram_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds modal content for Mermaid Diagram field items.
 */
class MermaidModalController extends ControllerBase {

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   The entity type manager service.
   */
  public function __construct(private EntityTypeManagerInterface $etm) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $c) {
    return new static($c->get('entity_type.manager'));
  }

  /**
   * Renders a Mermaid diagram field item in a modal.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'media').
   * @param int|string $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The Mermaid field machine name.
   * @param int $delta
   *   The field item delta to render.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request (to read query params without global calls).
   *
   * @return array
   *   Render array for the modal content.
   */
  public function view($entity_type, $entity_id, $field_name, $delta, Request $request) {
    $entity = $this->etm->getStorage($entity_type)->load($entity_id);
    $item = $entity->get($field_name)->get($delta);

    // Read ?pz=1 to attach the pan-zoom library inside the modal.
    $pz = $request->query->getBoolean('pz');

    return [
      '#theme' => 'mermaid_diagram',
      '#mermaid' => $item->diagram,
      '#title' => $item->title,
      '#caption' => $item->caption,
      '#attached' => [
        'library' => array_merge(
          ['mermaid_diagram_field/diagram'],
          $pz ? ['mermaid_diagram_field/pan_zoom'] : []
        ),
      ],
    ];
  }

}
