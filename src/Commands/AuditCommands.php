<?php

namespace Drupal\dlang_auditor\Commands;

use Drush\Commands\DrushCommands;
use Drupal\dlang_auditor\Controller\AuditFunctionController;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Set of Drush Commands for dlang_auditor module.
 */
class AuditCommands extends DrushCommands {

  /**
   * Generates files for auditing languages.
   *
   * @command dlang_auditor:generate
   * @aliases gaudit
   * @usage dlang_auditor:generate
   *    Generates batch operation for syncing
   *    then process it.
   */
  public function generate() {
    $batch = array(
      'operations' => [],
    );

    $batch['operations'] = [
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['languages', AuditFunctionController::getLanguages()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['modules', AuditFunctionController::getModules()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['themes', AuditFunctionController::getThemes()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['url_alias', AuditFunctionController::getUrlAlias()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['content_types', AuditFunctionController::getContentTypes()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['content_type_fields', AuditFunctionController::getContentFields()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['paragraphs', AuditFunctionController::getParagraphs()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['media', AuditFunctionController::getMedia()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['taxonomy', AuditFunctionController::getVocabularies()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['block_types', AuditFunctionController::getBlockTypes()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['blocks', AuditFunctionController::getBlocks()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['views', AuditFunctionController::getViews()]],
      ['\Drupal\dlang_auditor\Controller\AuditFunctionController::generateCsv', ['config_settings', AuditFunctionController::getConfigSettings()]]
    ];

    batch_set($batch);
    drush_backend_batch_process();
  }
}
