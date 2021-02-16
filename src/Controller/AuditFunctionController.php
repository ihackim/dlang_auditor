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
  public static function getLanguages() {
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
  public static function getModules() {
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
  public static function getThemes() {
    // Fetch the themes.
    $theme_list = \Drupal::service('extension.list.theme')->getList();
    // Get the default theme.
    $active_theme = \Drupal::config('system.theme')->get('default');
    $themes = [];
    $theme_output = [];
    $default_path = '';
    // Prepare Themes data for convertion to csv.
    $count = 0;
    foreach( $theme_list as $key => $value ){
      $themes[ $count++ ] = [
        'Theme' => $value->info['name'],
        'Machine Name' => $value->getName(),
        'Version' => $value->info['version'],
        'Type' => $value->info['package'],
        'Status' => ($value->status == 1 ? 'Enabled' : 'Disabled')
      ];
      if( $value->getName() == $active_theme ){
        $default_path = $value->subpath;
      }
    }
    function scanDirAndSubdir( $dir, &$files = [] ) {
      // Fetch directories.
      $paths = scandir( $dir );
      foreach ( $paths as $key => $filename ) {
          $file = realpath( $dir . DIRECTORY_SEPARATOR . $filename );
          if (!is_dir( $file )) {
              $files[] = $file;
          }
          elseif ( $filename != "." && $filename != ".." ) {
              scanDirAndSubdir( $file, $files );
              $files[] = $file;
          }
      }
      return $files;
    }
    $files = scanDirAndSubdir( $default_path );
    $theme_files = [];
    foreach ( $files as $key => $filename ) {
      if ( strpos( $filename, '.twig') !== FALSE ) {
        $theme_files['twig'][]= [
          'Theme' => strstr( $filename,'/custom'),
          'Machine Name' => '',
          'Version' => '',
          'Type' => '',
          'Status' => ''
        ];
      }
      elseif ( strpos( $filename, '.js') !== FALSE ){
        $theme_files['js'][]= [
          'Theme' => strstr( $filename,'/custom'),
          'Machine Name' => '',
          'Version' => '',
          'Type' => '',
          'Status' => ''
        ];
      }
      elseif ( strpos( $filename, '.css') !== FALSE ){
        $theme_files['css'][]= [
          'Theme' => strstr( $filename,'/custom'),
          'Machine Name' => '',
          'Version' => '',
          'Type' => '',
          'Status' => ''
        ];
      }
    }

    foreach($theme_files as $key=>$value){
      $space = [
        'Theme' => '',
        'Machine Name' => '',
        'Version' => '',
        'Type' => '',
        'Status' => ''
      ];
      $header = [
        'Theme' => 'all '.$key.' in custom theme',
        'Machine Name' => count($theme_files[$key]),
        'Version' => '',
        'Type' => '',
        'Status' => ''
      ];
      array_unshift($theme_files[$key],$space,$header);
    }

    $theme_output = array_merge($themes,$theme_files['js'],$theme_files['twig'],$theme_files['css']);
    return $theme_output;

  }

  /**
   * Retrieves a list of url aliases.
   *
   * @return void
   */
  public static function getUrlAlias() {
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
  public static function getContentTypes() {
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
    $content_type = [];
    $count = 0;
    foreach ($content_types as $content) {
      $content_type[$count++] = [
        'Machine Name' => $content['machine_name'],
        'Label' => $content['label'],
        'Count' => $content['count_nid'],
        'Language' => $content['langcode'],
      ];
    }

    return $content_type;
  }

  /**
   * Retrieves a list of fields from every content type.
   *
   * @return void
   */
  public static function getContentFields() {
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
    $collection = [];
    foreach ($content_types as $content_type) {
      $bundle_fields = [];
      $bundle = $content_type['machine_name'];
      $fields = \Drupal::entityManager()->getFieldDefinitions('node', $bundle);

      foreach ($fields as $key => $val) {
        $bundle_fields[] = [
          'Label' => $val->getLabel(),
          'Machine Name' => $key,
          'Field Type' => $val->getType(),
          'Translatable' => ($val->isTranslatable() ? 'Yes' : 'No'),
        ];
      }
      $collection[$bundle] = $bundle_fields;
    }

    return $collection;
  }

  /**
   * Retrieves a list of paragraphs.
   *
   * @return void
   */
  public static function getParagraphs() {
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
        if ((strncmp($field, 'field_', 6) === 0) && $val->isTranslatable()) {
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
  public static function getMedia() {
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
  public static function getVocabularies() {
    // Get list of vocabularies.
    $vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    $entityManager = \Drupal::service('entity.manager');

    // Prepare vocabularies for csv export.
    $vocab = [];
    $count = 0;
    foreach ($vocabularies as $key => $vocabulary) {
      $vocabs = $entityManager->getFieldDefinitions('taxonomy_term', $key);
      $fields = [];

      // Get translatable fields.
      foreach ($vocabs as $field => $val) {
        if (strncmp($field, 'field_', 6) === 0) {
          array_push($fields, $field);
        }
      }
      $fields = implode(' / ', $fields);

      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($key);
      $vocab[$count++] = [
        'Vocabulary' => $vocabulary->label(),
        'Vid' => $key,
        'Term Count' => count($terms),
        'Translatable' => ($vocabulary->language()->isLocked() ? 'No' : 'Yes'),
        'Fields' => $fields,
      ];
    }

    return $vocab;
  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function getBlockTypes() {
    // Fetch block types with data needed.
    $block_types_entity = \Drupal::entityTypeManager()->getStorage('block_content_type')->loadMultiple();
    $count = 0;
    foreach ($block_types_entity as $key => $value) {
        $block_types[$count++] = [
          'Machine Name' => $key,
          'Name' => $value->label(),
        ];
    }

    return $block_types;
  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function getBlocks() {
    // Fetch blocks content with data needed.
    $database = \Drupal::database();
    $query = $database->query("SELECT id, type, langcode, status, info FROM block_content_field_data;");
    $result = $query->fetchAll();

    // Prepare Block Contents data for conversion to csv.
    $count = 0;
    foreach( $result as $fields ){
        $block_contents[$count++] = [
          'Machine Name' => $fields->type,
          'Name' => $fields->info,
          'Status' => ( $fields->status == 1 ? 'Enabled' : 'Disabled' ),
          'Language' => $fields->langcode,
        ];
    }

    return $block_contents;
  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function getMenus() {

  }

  /**
   * Undocumented function
   *
   * @return void
   */
  public static function getViews() {
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
      $displays = implode(', ', $displays);
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
  public static function getConfigSettings() {
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

  /**
   * Generates csv file and save it to the root directory.
   *
   * @param string $type
   * @param array $data
   * @return void
   */
  public static function generateCsv($type, $data) {
    $path = DRUPAL_ROOT. '/language_audit/.';
    $dirname = dirname($path);
    if (!is_dir($dirname))
    {
        mkdir($path, 0775, true);
    }



    // Create file name.
    if ($type == 'content_type_fields'){
      $content_path = $path. '/content_types';
      if (!is_dir($content_path)) {
        mkdir($content_path, 0775, true);
      }


      foreach($data as $key => $value) {
        // Get the Headers.
        if (!empty($value)) {
          foreach($value as $keys => $val ){
            $headers = array_keys($val);
          }
        }

        // Store to csv file.
        $fp = fopen($content_path. '/' .$key. '.csv', 'w');
        if (!empty($value)) {
          fputcsv($fp, $headers);
          foreach ($value as $fields ){
            fputcsv($fp, $fields);
          }
        }
        else {
          fputcsv($fp, array('No available ' .$key. ' data found.'));
        }

        // Close the file.
        fclose($fp);

      }

    }
    else {
      // Get the Headers.
      if (!empty($data)) {
        foreach($data as $key => $value ){
          $headers = array_keys($value);
        }
      }

      // Store to csv file.
      $fp = fopen($path. '/' .$type. '.csv', 'w');
      if (!empty($data)) {
        fputcsv($fp, $headers);
        foreach ( $data as $fields ){
          fputcsv($fp, $fields);
        }
      }
      else {
        fputcsv($fp, array('No available ' .$type. ' data found.'));
      }

      // Close the file.
      fclose($fp);
    }
  }

}
