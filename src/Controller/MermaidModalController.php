// src/Controller/MermaidModalController.php
namespace Drupal\mermaid_diagram_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MermaidModalController extends ControllerBase {
  public function __construct(private EntityTypeManagerInterface $etm) {}
  public static function create(ContainerInterface $c) {
    return new static($c->get('entity_type.manager'));
  }

  public function view($entity_type, $entity_id, $field_name, $delta) {
    $entity = $this->etm->getStorage($entity_type)->load($entity_id);
    $item = $entity->get($field_name)->get($delta);
    $build = [
      '#theme' => 'mermaid_diagram',
      '#mermaid' => $item->diagram,
      '#title' => $item->title,
      '#caption' => $item->caption,
      '#attached' => [
        // Ensure whatever library renders Mermaid is available in the modal.
        'library' => ['mermaid_diagram_field/mermaid'],
      ],
    ];
    return $build;
  }
}
