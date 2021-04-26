<?php

declare(strict_types = 1);

namespace Drupal\img_annotator\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for annotation actions.
 */
class AnnotationsActionsController extends ControllerBase {

  /**
   * Image Annotator database table.
   *
   * @var string
   */
  const IMAGE_ANNOTATOR_TABLE = 'img_annotator';

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $database,
    RequestStack $requestStack,
    TimeInterface $time
  ) {
    $this->database = $database;
    $this->request = $requestStack->getCurrentRequest() ?: new Request();
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('datetime.time')
    );
  }

  /**
   * Update action.
   */
  public function update() : JsonResponse {
    $response = FALSE;

    // Ajax POST data.
    $postReq = $this->request->request->get('annotation');
    $nid = isset($postReq['nid']) ? $postReq['nid'] : FALSE;
    $aid = isset($postReq['aid']) ? $postReq['aid'] : FALSE;
    $annotation = isset($postReq) ? $postReq : FALSE;

    $validParams = $nid && $aid && $annotation;
    if (!$validParams || !$this->userHasAccess($nid, 'img_annotator edit', 'img_annotator own edit')) {
      return new JsonResponse($response);
    }

    $rowCount = $this->database->update(self::IMAGE_ANNOTATOR_TABLE)
      ->fields([
        'uid' => $this->currentUser()->id(),
        'annotation' => Json::encode($annotation),
        'updated' => $this->time->getCurrentTime(),
      ])
      ->condition('aid', $aid)
      ->execute();
    $response = (bool) $rowCount;

    return new JsonResponse($response);
  }

  /**
   * Delete action.
   */
  public function delete() : JsonResponse {
    $response = FALSE;

    // Ajax POST data.
    $postReq = $this->request->request->get('annotation');
    $nid = isset($postReq['nid']) ? $postReq['nid'] : FALSE;
    $aid = isset($postReq['aid']) ? $postReq['aid'] : FALSE;
    $annotation = isset($postReq) ? $postReq : FALSE;

    $validParams = $nid && $aid && $annotation;
    if (!$validParams || !$this->userHasAccess($nid, 'img_annotator edit', 'img_annotator own edit')) {
      return new JsonResponse($response);
    }

    $deleted_count = $this->database->delete(self::IMAGE_ANNOTATOR_TABLE)
      ->condition('aid', $aid)
      ->execute();
    $response = (bool) $deleted_count;

    return new JsonResponse($response);
  }

  /**
   * Save action.
   */
  public function save() : JsonResponse {
    $response = FALSE;

    // Ajax POST data.
    $postReq = $this->request->request->all();
    $nid = isset($postReq['nid']) ? $postReq['nid'] : FALSE;
    $img_field = isset($postReq['img_field']) ? $postReq['img_field'] : FALSE;
    $annotation = isset($postReq['annotation']) ? $postReq['annotation'] : FALSE;

    $validParams = $nid && $img_field && $annotation;
    if (!$validParams || !$this->userHasAccess($nid, 'img_annotator create', 'img_annotator own create')) {
      return new JsonResponse($response);
    }

    $aid = $this->database->insert(self::IMAGE_ANNOTATOR_TABLE)
      ->fields([
        'nid' => $nid,
        'uid' => $this->currentUser()->id(),
        'field' => $img_field,
        'annotation' => Json::encode($annotation),
        'updated' => $this->time->getCurrentTime(),
      ])
      ->execute();
    $response = $aid;

    return new JsonResponse($response);
  }

  /**
   * Retrieve action.
   */
  public function retrieve() : JsonResponse {
    $response = FALSE;

    // Ajax POST data.
    $postReq = $this->request->request->get('nid');
    $nid = isset($postReq) ? $postReq : FALSE;
    if (!$nid) {
      return new JsonResponse($response);
    }

    $node = $this->entityTypeManager()->getStorage('node')
      ->load($nid);

    if (!($node instanceof NodeInterface)) {
      return new JsonResponse($response);
    }

    // User permissions.
    $canEdit = $this->currentUser()->hasPermission('img_annotator edit');
    $canEditOwn = $this->currentUser()->hasPermission('img_annotator own edit');
    $canCreate = $this->currentUser()->hasPermission('img_annotator create');
    $canCreateOwn = $this->currentUser()->hasPermission('img_annotator own create');
    $canView = $this->currentUser()->hasPermission('img_annotator view');
    $canViewOwn = $this->currentUser()->hasPermission('img_annotator own view');

    $canRetrieve = $canEdit || $canCreate || $canView;
    $canRetrieveOwn = $canEditOwn || $canCreateOwn || $canViewOwn;

    $isNodeOwner = ($node->getOwnerId() == $this->currentUser()->id()) ? TRUE : FALSE;

    if (!$canRetrieve || !($isNodeOwner && $canRetrieveOwn)) {
      return new JsonResponse($response);
    }

    $annotations = [];
    // Existing Annotation from table.
    $existing_annotations = $this->database->select(self::IMAGE_ANNOTATOR_TABLE)
      ->fields(self::IMAGE_ANNOTATOR_TABLE, ['aid', 'annotation'])
      ->condition('nid', $nid)
      ->execute();

    if (!is_null($existing_annotations)) {
      $anno_editable = FALSE;
      if ($canEdit) {
        $anno_editable = TRUE;
      }

      // User can not view.
      // It does not load library because of code at .module alteration.
      // Prepare annotation response.
      while ($record = $existing_annotations->fetchObject()) {
        $anno_val = Json::decode($record->annotation);
        $anno_val['editable'] = $anno_editable;
        $anno_val['aid'] = $record->aid;

        // Sanitize the text that gets rendered in the annotation.
        $anno_val['text'] = Xss::filter($anno_val['text']);

        $annotations[] = $anno_val;
      }
    }
    $response = Json::encode($annotations);

    return new JsonResponse($response);
  }

  /**
   * Check user access for action on a node.
   *
   * @param string $nid
   *   The NID of the node to check against.
   * @param string $allPermission
   *   The 'all' permission of the action.
   * @param string $ownPermission
   *   The 'own' permission of the action.
   *
   * @return bool
   *   TRUE if the user can do the action. FALSE otherwise.
   */
  protected function userHasAccess(string $nid, string $allPermission, string $ownPermission) : bool {
    if ($this->currentUser()->hasPermission($allPermission)) {
      return TRUE;
    }

    // Check if current user is owner of the node.
    if ($this->currentUser()->hasPermission($ownPermission)) {
      $node = $this->entityTypeManager()->getStorage('node')
        ->load($nid);

      if (($node instanceof NodeInterface) && $node->getOwnerId() == $this->currentUser()->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
