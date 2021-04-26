<?php

namespace Drupal\archivesspace;

/**
 * Class ArchivesSpaceUtils.
 */
class ArchivesSpaceUtils {

  /**
   * Maps AS uri prefixes type to their respective migrations.
   *
   * This is populated from the
   * `archivesspace.settings.batch_update.uri_migration_map` config
   * in the constructor. Not all of the possible item types are currently
   * supported. Unsupported types are:
   *   - repository: Doesn't appear to work as a type filter.
   *   - classification: No existing migrations and it doesn't appear to work
   *     as a type filter, but needs to be verified.
   *   - digital_object: not sure what to do with this one. The question is how
   *     submodules can update a parent module's config. Might need an install
   *     hook to work.
   *   - digital_object_component: see digital_object.
   *   - agent_software: No migrations are provided yet.
   * Site implementors, if they develop a migration for any of these can update
   * their own `archivesspace.settings.batch_update`.
   *
   * @var array
   */
  protected $uriMigrationMap = [];

  /**
   * Constructor to set defaults.
   */
  public function __construct() {
    // Map regex pattern to migration_id.
    $archivesspace_settings = \Drupal::config('archivesspace.settings');
    $migration_map = $archivesspace_settings->get('batch_update.uri_migration_map');
    if (!is_array($migration_map)) {
      throw new \Exception("ArchivesSpace URI migration map configuration is invalid.");
    }
    foreach ($migration_map as $regex_migration_pair) {
      $this->uriMigrationMap[$regex_migration_pair['uri_regex']] = $regex_migration_pair['migration_id'];
    }
  }

  /**
   * URI to Migration Map.
   */
  public function getUriMigration(string $uri) {
    foreach ($this->uriMigrationMap as $regex => $migration_id) {
      if (preg_match($regex, $uri) == 1) {
        return $migration_id;
      }
    }
    return FALSE;
  }

}
