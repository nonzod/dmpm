<?php

declare(strict_types=1);

namespace Drupal\odv;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Render\Renderer;
use Drupal\odv\DTO\Submission;
use Drupal\system\Plugin\Archiver\Zip;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;

/**
 * Generate the receipt to be downloaded to the user.
 *
 * This implementation generate a zip file that contains:
 *  - a pdf generated using the submission info.
 *  - all the files attached to the submission.
 */
class PdfZipReceiptGenerator implements ReceiptGeneratorInterface {

  use PathTrait;

  /**
   * The Archiver manager service.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  private ArchiverManager $archiveManager;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  private Renderer $renderer;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * ZipGenerator constructor.
   *
   * @param \Drupal\Core\Archiver\ArchiverManager $archive_manager
   *   The Archiver manager service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The Renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   */
  public function __construct(
    ArchiverManager $archive_manager,
    Renderer $renderer,
    LoggerInterface $logger
  ) {
    $this->archiveManager = $archive_manager;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function generate(Submission $submission): string {
    $zipName = sprintf('%s', $this->generateUuid());
    $zipPath = $this->getReceiptPath($zipName);

    $archiver = $this->archiveManager->getInstance(['filepath' => $zipPath]);
    assert($archiver instanceof Zip);

    foreach ($submission->getAttachments() as $attachment) {
      $archiver
        ->getArchive()
        ->addFromString(
            $attachment->getFilename(),
            file_get_contents($attachment->getPathname()
          )
        );
    }

    $archiver
      ->getArchive()
      ->addFromString('receipt.pdf', $this->generatePdf($submission));

    return $zipName;
  }

  /**
   * Generate and return a random UUID.
   *
   * @return string
   *   A random UUID.
   */
  private function generateUuid(): string {
    $uuid = new Php();

    return $uuid->generate();
  }

  /**
   * Generate a pdf with submission data and return it as a string.
   *
   * @param \Drupal\odv\DTO\Submission $submission
   *   The submission data.
   *
   * @return string
   *   The pdf with submission data as string.
   */
  private function generatePdf(Submission $submission): string {
    $element = [
      '#theme' => 'odv_pdf',
      '#submission' => $submission,
    ];
    $html = $this->renderer->renderRoot($element);

    try {
      $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'tempDir' => '/tmp',
      ]);
      $mpdf->WriteHTML($html);

      return $mpdf->Output('', Destination::STRING_RETURN);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf('Error in generating the pdf file: %s', $e->getMessage()));

      return 'Error in generating the pdf file';
    }
  }

}
