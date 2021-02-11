<?php

namespace Drupal\dlang_auditor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides functions for exporting data from the site.
 */
class AuditFunctionController extends ControllerBase {

  /**
   * Retrieves list of languages.
   *
   * @return array
   */
  public function getLanguages() {
    // Get list of languages defined.
    $languages = \Drupal::languageManager()->getLanguages();

    // Prepare data for conversion to csv.
    $count = 0;
    foreach ($languages as $key => $value){
      $language[$count++] = [
        'Lanuage' => $value->getId(),
        'Name' => $value->getName(),
        'Weight' => $value->getWeight(),
      ];
    }

    return $language;
  }

  /**
   * Retrieves list of modules.
   *
   * @return array
   */
  public function getModules() {
    // Get list of modules.
    $module_list = \Drupal::service('extension.list.module')->getList();

    // Prepare module list for conversion to csv.
    $count = 0;
    foreach ($module_list as $key => $value) {
      $modules[$count++] = [
      'Package' => $value->info['package'],
      'Name' => $value->info['name'] . '(' . $key . ')',
      'Type' => $value->info['type'],
      'Status' => ($value->status == 1 ? 'Enabled' : 'Disabled'),
      'Version' => $value->info['version'],
      ];
    }

    return $modules;
  }

  /**
   * Retrieves a list of themes available.
   *
   * @return void
   */
  public function getThemes() {

  }

  /**
   * Retrieves a list of url aliases.
   *
   * @return void
   */
  public function getUrlAlias() {
    // Fetch URL aliases data.
    $url = \Drupal::entityTypeManager()->getStorage('path_alias')->loadMultiple();

    // Prepare aliases data for conversion to csv.
    $count = 0;
    foreach ($url as $key => $value) {
      $url_aliases[$count++] = [
        'Alias' => $value->label(),
        'NID' => $key,
        'Source' => $value->path->value,
        'Language' => $value->langcode->value ,
      ];
    }

    return $url_aliases;
  }

  /**
   * Retrieve a list of content types.
   *
   * @return void
   */
  public function getContentTypes() {
    // Fetch content types with machine name.
    $database = \Drupal::database();
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($types as $key => $value) {
      $content_types[$key] = [
        'label' => $value->label(),
        'machine_name' => $key,
        'count_nid' => '',
        'langcode' => '',
      ];
    }

    // Combine with node count and language code.
    $query = $database->query("SELECT type, count(nid) as nid_count, langcode FROM node GROUP BY type, langcode");
    $result = $query->fetchAll();
    foreach ($result as $field) {
      $content_types[$field->type]['count_nid'] = $field->nid_count;
      $content_types[$field->type]['langcode'] = $field->langcode;
    }

    // Prepare data for csv export.
    $content_types = [];
    $count = 0;
    foreach ($content_types as $content) {
      $content_types[$count++] = [
        'Machine Name' => $content['machine_name'],
        'Label' => $content['label'],
        'Count' => $content['count_nid'],
        'Language' => $content['langcode'],
      ];
    }

    return $content_types;
  }

  /**
   * Retrieves a list of fields from every content type.
   *
   * @return void
   */
  public function getContentFields() {
    // Fetch content types with machine name.
    $database = \Drupal::database();
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($types as $key => $value) {
      $content_types[$key] = [
        'label' => $value->label(),
        'machine_name' => $key,
        'count_nid' => '',
        'langcode' => '',
      ];
    }
    // Combine with node count and language code.
    $query = $database->query("SELECT type, count(nid) as nid_count, langcode FROM node GROUP BY type, langcode");
    $result = $query->fetchAll();
    foreach ($result as $field) {
      $content_types[$field->type]['count_nid'] = $field->nid_count;
      $content_types[$field->type]['langcode'] = $field->langcode;
    }

    // Get fields per content type.
    $bundle_fields = [];
    foreach ($content_types as $content_type) {
      $bundle = $content_type['machine_name'];
      $fields = \Drupal::entityManager()->getFieldDefinitions('node', $bundle); //get fields of bundle xxx
      // Generate table.
      $table = "<table style='border: 1px solid black'><tr> <th>Label</th> <th>Machine Name</th> <th>Field Type</th> <th></th></tr>";
      foreach ($fields as $key => $value) {
        $table = $table . "<tr> <td>" .$value->getLabel(). "</td> <td>" .$key. "</td> <td> " .$value->getType(). "</td> </tr>";
      }
      $table = $table . "</table>";
    }
  }

  /**
   * Retrieves a list of paragraphs.
   *
   * @return void
   */
  public function getParagraphs() {
    // Get paragraph bundles.
    $entityManager = \Drupal::service('entity.manager');
    $bundles = $entityManager->getBundleInfo('paragraph');
    $paragraphs = [];
    $count = 0;
    foreach ($bundles as $key => $value) {
      $paragraph = $entityManager->getFieldDefinitions('paragraph', $key);
      $translatable = [];

      // Get translatable fields.
      foreach ($paragraph as $field => $val) {
        if (str_starts_with($field, 'field_') && $val->isTranslatable()) {
          array_push($translatable, $field);
        }
      }
      $translatable = implode(' / ', $translatable);

      // Prepare list for csv export.
      $paragraphs[$count++] = [
        'Label' => $value['label'],
        'Machine Name' => $key,
        'Translatable Fields' => $translatable,
      ];
    }

    return $paragraphs;
  }

  /**
   * Retrieves a list of media entities used from the site.
   *
   * @return void
   */
  public function getMedia() {
    // Fetch media data using Drupal API.
    $media_entity = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
    foreach ( $media_entity as $key =>$value ){
      $medias[$key] = [
        'machine_name' => $key,
        'name' => $value->label(),
        'count_mid' => '',
        'langcode' => '',
      ];

      // Fetch media data using database.
      $database = \Drupal::database();
      $query = $database->query("SELECT bundle, count(mid) as mid_count, langcode FROM media GROUP BY bundle, langcode;");
      $result = $query->fetchAll();
      foreach ( $result as $media_db ){
        $medias[$media_db->bundle]['count_mid'] = $media_db->mid_count;
        $medias[$media_db->bundle]['langcode'] = $media_db->langcode;
    }
    }

    // Prepare media entity data for conversion to csv.
    $media_csv = array();
    $count = 0;
    foreach ($medias as $media) {
      $media_csv[$count++] = [
        'Machine Name'=>$media['machine_name'],
        'Name'=>$media['name'],
        'Count'=>$media['count_mid'],
        'Language'=>$media['langcode'],
      ];
    }

    return $media_csv;
  }

  /**
   * Retrieves a list of taxonomy.
   *
   * @return void
   */
  public function getVocabularies() {
    // Get list of vocabularies.
    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();

    // Prepare vocabularies for csv export.
    $vocab = [];
    $count = 0;
    foreach ($vocabularies as $key => $vocabulary) {
      $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($key);
      $vocab[$count++] = [
        'Vocabulary' => $vocabulary->label(),
        'Vid' => $key,
        'Term Count' => count($terms),
        'Translatable' => ($vocabulary->language()->isLocked() ? 'No' : 'Yes')
      ];
    }

    return $vocab;
  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public function getBlockTypes() {

  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public function getBlocks() {

  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public function getMenus() {

  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public function getViews() {
    // Fetch Views with data needed.
    $views_entity = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();

    // Prepare Views data for convertion to csv.
    $views = [];
    $count = 0;
    foreach ($views_entity as $key => $value) {
      $displays = array();
      foreach ( $value->get('display') as $index => $item ){
        if ( $index != 'default' ){
          if ( $item['display_options']['path'] != null ) {
            $displays[] = $item['display_plugin'] . '(/' . $item['display_options']['path'] . ")";
          }
          else{
            $displays[] = $item['display_plugin'];
          }
        }
      }

      $views[$count++] = [
        'Machine Name' => $key,
        'Name' => $value->label(),
        'Status' => ( $value->status() == TRUE ? 'Enabled' : 'Disabled' ),
        'Displays' => $displays ,
      ];
    }

    return $views;
  }

  /**
   * Retrieves list of items in configuration settings page.
   *
   * @return void
   */
  public function getConfigSettings() {
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
    $menu_items = [];
    $count = 0;
    foreach ($items as $item) {
      foreach ($item as $menuTitle){
        $menu_items[$count++] = [
          'Type' => $menuTitle
        ];
      }
    }

    return $menu_items;
  }

}
