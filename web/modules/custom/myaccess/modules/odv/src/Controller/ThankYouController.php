<?php

declare(strict_types=1);

namespace Drupal\odv\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\odv\PathTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the Thank-you page.
 */
class ThankYouController extends ControllerBase {

  use PathTrait;

  /**
   * View action to show the thank-you page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string[]
   *   A render array.
   */
  public function view(Request $request): array {
    return [
      '#theme' => 'odv_thank_you',
      '#file_id' => $request->query->get('id'),
      '#thank_you_message' => $this->config('odv.settings')->get('thank_you'),
    ];
  }

  /**
   * Action to download the compressed file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   BinaryFile response with attached file.
   */
  public function download(Request $request): Response {
    $zipName = $request->query->get('id');
    $zipPath = $this->getReceiptPath($zipName);

    if (is_file($zipPath)) {
      $response = new BinaryFileResponse($zipPath);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        sprintf('%s.zip', $zipName)
      );

      return $response;
    }

    throw new NotFoundHttpException();
  }

}
