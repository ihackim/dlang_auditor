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
    AuditFunctionController::generateCsv('content_types', AuditFunctionController::getThemes());
  }
}
