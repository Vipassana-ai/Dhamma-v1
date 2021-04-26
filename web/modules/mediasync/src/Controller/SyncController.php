<?php

namespace Drupal\mediasync\Controller;

use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

/**
 * Sync the file system with the media entities.
 */
class SyncController {

  /**
   * File mapping.
   *
   * @var array
   */
  private $fileMapping = [];
  
  /**
   * File mapping.
   *
   * @var array
   */
  private $tagMapping = [];

  /**
   * User id of the new media owner.
   *
   * @var int
   */
  private $uid;

  /**
   * Starts the sync of media entities with the filesystem.
   */
  public function sync() {

    $config = \Drupal::config('mediasync.settings');

    $this->uid = $config->get('user');

    $this->createMappingTable($config->get('type'));
    $this->createTagTable($config->get('tags'));

    $query = \Drupal::entityQuery('media');
    $res = $query->execute();
    $existingMediaFiles = [];

    foreach ($res as $mid) {
      $media = Media::load($mid);
      $mediaSource = $media->getSource();
      $mediaSourceValue = $mediaSource->getSourceFieldValue($media);
      $fid = $media->getSource()->getSourceFieldValue($media);
      $file = File::load($fid);
      $uri = $file->getFileUri();
      $existingMediaFiles[] = $uri;
    }

    $directories = explode(PHP_EOL, $config->get('folder'));

    $systemFiles = [];

    foreach ($directories as $dir) {
      if (is_dir($dir)) {
        $systemFiles = array_merge($systemFiles, $this->getDirContents($dir));
      }
    }

    $diff = array_diff($systemFiles, $existingMediaFiles);
    $this->createMedia($diff);
  }

  /**
   * Create an array with all files of the specified folders.
   */
  private function getDirContents($dir) {

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
      if ($file->isDir()) {
        continue;
      }
      $files[] = $file->getPathname();
    }
    return $files;
  }

  /**
   * Return array whith keys as mapping target, values as supported extensions.
   */
  private function createMappingTable($configValues) {
    $mappingValues = explode(PHP_EOL, $configValues);
    foreach ($mappingValues as $value) {
      $values = explode("=>", $value);
      $fileTypes = explode(", ", $values[0]);
      $field = str_replace(" ", "", $values[2]);
      $field = str_replace("\t", "", $field);
      $field = str_replace("\r", "", $field);
      $field = str_replace("\n", "", $field);
      $key = str_replace(" ", "", $values[1]) . "." . $field;
      $this->fileMapping[$key] = $fileTypes;
    }
  }
  
   /**
   * Return array whith keys as mapping target, values as supported extensions.
   */
  private function createTagTable($configValues) {
    
    $tagValues = explode(PHP_EOL, $configValues);
    
    foreach ($tagValues as $value) {
      $config = [];
      $values = explode("=>", $value);
      
      $type = str_replace(" ", "", $values[0]);
      $type = str_replace("\t", "", $type);
      $type = str_replace("\r", "", $type);
      $type = str_replace("\n", "", $type);
            
      $field = str_replace(" ", "", $values[1]);
      $field = str_replace("\t", "", $field);
      $field = str_replace("\r", "", $field);
      $field = str_replace("\n", "", $field);
      
      $voc = str_replace(" ", "", $values[2]);
      $voc = str_replace("\t", "", $voc);
      $voc = str_replace("\r", "", $voc);
      $voc = str_replace("\n", "", $voc);
      
      $ignoreList = str_replace(" ", "", $values[3]);
      $ignoreList = str_replace("\t", "", $ignoreList);
      $ignoreList = str_replace("\r", "", $ignoreList);
      $ignoreList = str_replace("\n", "", $ignoreList);
      
      $ignoreListValues = explode(",", $ignoreList); 
      
      $config['field'] = $field;
      $config['voc'] = $voc;
      $config['ignoreList'] = $ignoreListValues;
      
      $this->tagMapping[$type] = $config;
    }
  }
  
  

  /**
   * Create the media entities.
   */
  private function createMedia($mediaFiles) {
    
    foreach ($mediaFiles as $filePath) {
      
      $fileType = end(explode(".", $filePath));

      $fileName = end(explode("/", $filePath));
      $fileName = str_replace(".", "", $fileName);
      $fileName = str_replace($fileType, "", $fileName);

      $extensionInfo = explode(".", $this->getMediaType($fileType));
      
      $bundle = $extensionInfo[0];
      $field_name = $extensionInfo[1];

      if ($bundle !== NULL) {

        $file = File::create([
          'uid' => $this->uid,
          'filename' => $fileName . "." . $fileType,
          'uri' => $filePath,
          'status' => 1,
        ]);

        $file->save();
                
        if (array_key_exists($bundle, $this->tagMapping)) {
          $tags = $this->createTags($filePath, $bundle);
          $tids = $this->createTerms($tags, $bundle);
          
          $media = Media::create([
            'bundle' => $bundle,
            'uid' => $this->uid,
            $field_name => [
              'target_id' => $file->id(),
            ],
            $this->tagMapping[$bundle]['field'] => $tids,
          ]);
          $media->setName($fileName)->setPublished(TRUE)->save();
        } else {
          $media = Media::create([
            'bundle' => $bundle,
            'uid' => $this->uid,
            $field_name => [
              'target_id' => $file->id(),
            ],
          ]);
          $media->setName($fileName)->setPublished(TRUE)->save();
        }
      }
    }
  }

  /**
   * Based on the file extension and the mapping table return the media type.
   */
  public function getMediaType($fileType) {
    foreach ($this->fileMapping as $key => $types) {
      if (in_array($fileType, $types)) {
        return $key;
      }
    }
    return NULL;
  }
  
  /*
   * create Tags based on the file path.
   */
  private function createTags($filePath, $bundle) {
    
    $d = NULL;
    
    if(strpos($filePath, '/') !== false) {
      $d = '/';
    }elseif(strpos($filePath,'\\') !== false) {
      $d = '\\';
    }
    
    if ($d != NULL) {
      $tmp_ = explode("://", $filePath);
      $tags = explode($d, $tmp_[1]);
      array_pop($tags);
    }
    
    $ignoreList = $this->tagMapping[$bundle]['ignoreList'];
    
    for ($i = 0; $i < sizeof($tags); $i++) {
      if (in_array($tags[$i], $ignoreList)) {
        unset($tags[$i]);        
      }
    }
    
    return $tags;
  }
  
  /*
   * check if tag exist, if not create term.
   */
  private function createTerms($tags, $bundle) {
    $ids = [];
    $voc = $this->tagMapping[$bundle]['voc'];
    foreach ($tags as $tag) {
      $id = $this->getTidByName($tag, $voc);
      if(isset($id)) {
        array_push($ids, $id);
      } else {
        Term::create([
          'name' => $tag, 
          'vid' => $voc,
        ])->save();
        $id = $this->getTidByName($tag, $voc);
        array_push($ids, $id);
      }
    }  
    return $ids;
  }
  
  /**
   * source: https://drupal.stackexchange.com/questions/225209/load-term-by-name/answer-225220
   * Utility: find term by name and vid.
   * @param null $name
   *  Term name
   * @param null $vid
   *  Term vid
   * @return int
   *  Term id or 0 if none.
   */
  protected function getTidByName($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term->id() : NULL;
  }

}
