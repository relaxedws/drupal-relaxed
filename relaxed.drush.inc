<?php

use Drupal\Core\Database\Database;
use Psr\Log\LogLevel;

/**
 * Implements of hook_drush_command().
 */
function relaxed_drush_command() {
  $items = [];

  $items['relaxed-uninstall'] = [
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'description' => 'Uninstall Relaxed.',
    'aliases' => ['relun'],
  ];

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_relaxed_uninstall() {
  $extension = 'relaxed';
  $uninstall = TRUE;
  $extension_info = drush_get_extensions();
  $required = drush_drupal_required_modules($extension_info);
  if (in_array($extension, $required)) {
    $info = $extension_info[$extension]->info;
    $explanation = !empty($info['explanation']) ? ' ' . dt('Reason: !explanation.', ['!explanation' => strip_tags($info['explanation'])])  : '';
    drush_log(dt('!extension is a required extension and can\'t be uninstalled.', ['!extension' => $extension]) . $explanation, LogLevel::INFO);
    $uninstall = FALSE;
  }
  elseif (!$extension_info[$extension]->status) {
    drush_log(dt('!extension is already uninstalled.', ['!extension' => $extension]), LogLevel::INFO);
    $uninstall = FALSE;
  }
  elseif (drush_extension_get_type($extension_info[$extension]) == 'module') {
    $dependents = [];
    foreach (drush_module_dependents([$extension], $extension_info) as $dependent) {
      if (!in_array($dependent, $required) && ($extension_info[$dependent]->status)) {
        $dependents[] = $dependent;
      }
    }
    if (count($dependents)) {
      drush_log(dt('To uninstall !extension, the following extensions must be uninstalled first: !required', ['!extension' => $extension, '!required' => implode(', ', $dependents)]), LogLevel::ERROR);
      $uninstall = FALSE;
    }
  }

  if ($uninstall) {
    drush_print(dt('Relaxed will be uninstalled.'));
    if(!drush_confirm(dt('Do you really want to continue?'))) {
      return drush_user_abort();
    }

    try {
      // Set values for all fields provided by Relaxed to NULL in the database
      // (for workspace_pointer entity type), so the module can be uninstalled.
      $entity_field_manager = \Drupal::service('entity_field.manager');
      $storage = \Drupal::entityTypeManager()->getStorage('workspace_pointer');
      $fields = [];
      foreach ($entity_field_manager->getFieldStorageDefinitions('workspace_pointer') as $storage_definition) {
        if ($storage_definition->getProvider() === 'relaxed') {
          $fields[$storage_definition->getName()] = NULL;
        }
      }
      if (!empty($fields)) {
        $connection = Database::getConnection();
        $connection->update($storage->getEntityType()->getBaseTable())
          ->fields($fields)
          ->execute();
      }

      drush_module_uninstall([$extension]);
    }
    catch (Exception $e) {
      drush_log($e->getMessage(), LogLevel::ERROR);
    }

    // Inform the user of final status.
    drush_log(dt('!extension was successfully uninstalled.', ['!extension' => $extension]), LogLevel::INFO);
  }

}
