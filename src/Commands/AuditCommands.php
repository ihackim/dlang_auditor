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
    $menu_tree = \Drupal::menuTree();
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin_config')->excludeRoot()->onlyEnabledLinks();
    $mainTree = $menu_tree->load(NULL, $parameters);
    $items = [];
    $cou = 1;

    // Get main menu grouping.
    foreach ($mainTree as $key => $value) {
      $title = $mainTree[$key]->link->getTitle();
      $items['' .$mainTree[$key]->link->getWeight(). '' .$cou] = [];

      // Get subtree.
      foreach ($mainTree[$key]->subtree as $item => $val) {
        $main = $mainTree[$key]->subtree;
        $itemTitle = $main[$item]->link->getTitle();
        array_push($items['' .$mainTree[$key]->link->getWeight(). '' .$cou], $title. " > ". $itemTitle);
      }
      $cou++;
    }

    // Sort items according to weight.
    ksort($items);

    // Prepare data for conversion to csv.
    $menu_items = [
      array('Type')
    ];
    foreach ($items as $item) {
      foreach ($item as $menuTitle){
        $array = [$menuTitle];
        array_push($menu_items, $array);
      }
    }

    // Open/Create the file
    $f = fopen(DRUPAL_ROOT. '/configs.csv', 'a');

    // Write to the csv
    foreach ($menu_items as $fields) {
      fputcsv($f, $fields);
    }

    // Close the file
    fclose($f);
  }
}
