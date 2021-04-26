<?php

namespace Drupal\export_tools\Helper;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A helper to generate spreadsheet.
 */
class SpreadsheetGeneratorHelper {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The spreadsheet.
   *
   * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
   */
  protected $spreadsheet;

  /**
   * The extension to generate.
   *
   * @var string
   */
  protected $extension = 'csv';

  /**
   * The generated file if it's asked to be saved.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $generatedFile;

  /**
   * OrderExportController constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function __construct(FileSystemInterface $file_system, AccountInterface $account, ModuleHandlerInterface $module_handler) {
    $this->fileSystem = $file_system;
    $this->currentUser = $account;
    $this->moduleHandler = $module_handler;
    $this->initSpreadsheet();
  }

  /**
   * Initialise the spreadsheet object.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function initSpreadsheet(): void {
    $this->spreadsheet = new Spreadsheet();
    $this->spreadsheet->removeSheetByIndex(0);
    $datetime = new DrupalDateTime();
    $this->spreadsheet->getProperties()
      ->setCreated($datetime->getTimestamp())
      ->setCreator($this->currentUser->getDisplayName())
      ->setTitle('Export - ' . $datetime->format('m-d-Y H:i'))
      ->setLastModifiedBy($this->currentUser->getDisplayName());

    $worksheet = $this->spreadsheet->createSheet();
    $worksheet->setTitle('Export');
  }

  /**
   * Get available extensions with their class writer.
   *
   * @return array
   *   The available extensions.
   */
  public function getAvailableExtensions(): array {
    return [
      'csv' => Csv::class,
      'html' => Html::class,
      'ods' => Ods::class,
      'pdf' => Pdf::class,
      'xls' => Xls::class,
      'xlsx' => Xlsx::class,
    ];
  }

  /**
   * Generate the spreadsheet file.
   *
   * @param string $extension
   *   Extension file to generate.
   * @param string $filename
   *   The filename to generate.
   * @param string $destination
   *   The filepath destination to create file. Keep empty to not generate a
   *   file.
   * @param bool $saveAsTemporary
   *   Save destination file as temporary file.
   *
   * @return string
   *   The generated content.
   *
   * @throws \Exception
   */
  public function generate($extension, $filename = '', $destination = '', $saveAsTemporary = TRUE): string {
    $output = '';

    $this->checkExtension($extension);
    $this->extension = $extension;
    if ($filename === '') {
      $filename = $this->randomFilename();
    }

    $filepath = 'temporary://' . $filename;

    try {
      $availableExtensions = $this->getAvailableExtensions();
      // Write spreadsheet in an excel temporary file.
      $objWriter = new $availableExtensions[$extension]($this->getSpreadsheet());

      // Catch the output of the spreadsheet.
      ob_start();
      $objWriter->save($this->fileSystem->realpath($filepath));
      ob_get_clean();

      $output = file_get_contents($filepath);

      if ($destination !== '') {
        $this->createDestinationFile($filepath, $destination, $saveAsTemporary);
      }
    }
    catch (\Exception $e) {
      throw $e;
    }
    finally {
      // Always remove temporary file.
      $this->fileSystem->delete($filepath);
    }

    return $output;
  }

  /**
   * Get current spreadsheet object.
   *
   * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
   *   The spreadsheet.
   */
  public function getSpreadsheet(): Spreadsheet {
    return $this->spreadsheet;
  }

  /**
   * Set headers to current sheet with one level array.
   *
   * @param array $headers
   *   Header labels.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function setHeaders(array $headers) {
    $worksheet = $this->getSpreadsheet()->getActiveSheet();

    $col_index = 1;
    foreach ($headers as $label) {
      $worksheet->setCellValueExplicitByColumnAndRow($col_index++, 1, trim($label), DataType::TYPE_STRING);
    }
  }

  /**
   * Add rows to current worksheet.
   *
   * @param array $rows
   *   The rows data to add.
   *
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  public function addRows(array $rows) {
    $worksheet = $this->getSpreadsheet()->getActiveSheet();
    // Let the first line for headers, so start at second line.
    $worksheet->fromArray($rows, NULL, 'A2');
  }

  /**
   * Generate a random filename.
   *
   * @return string
   *   The randomized filename.
   */
  public function randomFilename(): string {
    return 'spreadsheet-export-' . substr(hash('ripemd160', uniqid($this->currentUser->id(), TRUE)), 0, 20) . '.' . $this->extension;
  }

  /**
   * Check extension is available.
   *
   * @param string $extension
   *   The extension to check.
   */
  public function checkExtension($extension): void {
    $availableExtensions = array_keys($this->getAvailableExtensions());
    if (!in_array(strtolower($extension), $availableExtensions, TRUE)) {
      throw new UnknownExtensionException(sprintf('Following extension is not available to export : %s', $extension));
    }
  }

  /**
   * Set the generated file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   */
  public function setGeneratedFile(FileInterface $file): void {
    $this->generatedFile = $file;
  }

  /**
   * Get the generated file.
   *
   * @return \Drupal\file\FileInterface
   *   The file.
   */
  public function getGeneratedFile(): FileInterface {
    return $this->generatedFile;
  }

  /**
   * Create the destination file.
   *
   * @param string $source
   *   The source file.
   * @param string $destination
   *   The destination file.
   * @param bool $saveAsTemporary
   *   TRUE if destination file has to be saved as temporary file.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createDestinationFile($source, $destination, $saveAsTemporary): void {
    // Move temporary to final file.
    $exploded_path = explode('/', $destination);

    // Remove and get the filename from the path.
    $destination_filename = array_pop($exploded_path);
    $destination_directory = implode('/', $exploded_path);
    $this->fileSystem->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY);
    $this->fileSystem->move($source, $destination, FileSystemInterface::EXISTS_REPLACE);

    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => $destination,
    ]);
    // Save file in Drupal as temporary file so it will be automatically
    // deleted.
    if ($saveAsTemporary) {
      $file->setTemporary();
    }
    $file->setFilename($destination_filename);
    $file->setOwnerId($this->currentUser->id());
    $file->save();
    $this->setGeneratedFile($file);
  }

  /**
   * Create the destination file from output.
   *
   * @param string $output
   *   The output to create.
   * @param string $destination
   *   The destination file.
   * @param bool $saveAsTemporary
   *   TRUE if destination file has to be saved as temporary file.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createDestinationFileFromOutput($output, $destination, $saveAsTemporary): void {
    $filepath = 'temporary://' . $this->randomFilename();
    file_put_contents($filepath, $output);
    $this->createDestinationFile($filepath, $destination, $saveAsTemporary);
  }

  /**
   * Generate the downloadable response.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The binary file response to send to browser to download the file.
   */
  public function downloadResponse(): BinaryFileResponse {
    $file = $this->getGeneratedFile();
    $filepath = $file->getFileUri();
    if (file_exists($filepath)) {
      // Let other modules provide headers and controls access to the file.
      $headers = $this->moduleHandler->invokeAll('file_download', [$filepath]);
      $headers += [
        'Content-disposition' => 'attachment;filename="' . $file->getFilename() . '"',
      ];

      foreach ($headers as $result) {
        if ($result === -1) {
          throw new AccessDeniedHttpException();
        }
      }

      if (count($headers)) {
        // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
        // sets response as not cacheable if the Cache-Control header is not
        // already modified. We pass in FALSE for non-private schemes for the
        // $public parameter to make sure we don't change the headers.
        return new BinaryFileResponse($filepath, 200, $headers, FALSE);
      }

      throw new AccessDeniedHttpException();
    }

    throw new NotFoundHttpException();
  }

}
