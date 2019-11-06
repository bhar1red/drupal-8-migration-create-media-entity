<?php

namespace Drupal\org_content_suite\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin;
use Drupal\migrate\MigrateExecutableInterface as MigrateExecutableInterface;
use Drupal\migrate\Row as Row;
use Drupal\media\Entity\Media as Media;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "create_media_entity"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_article_attachments:
 *   plugin: create_media_entity
 *   source: field_file_upload
 * @endcode
 *
 */
class CreateMediaEntity extends ProcessPluginBase {
   /**
   * {@inheritdoc}
   */
    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property){    
      global $base_url; 
      \Drupal::logger('Value')->notice(print_r($value, TRUE));
      if(!empty($value[0])){
        $type = $value[2];
        $path = $value[3];
        $file_path = substr($value[0], 9);
        $file_name = basename($value[0]);
        //Search if Media exists (Search based on name)
        $media = reset(\Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $file_name]));
        if(!empty($media->id)){
          return $media->id;
        }
       
        $file_data = file_get_contents(DRUPAL_ROOT.$path.$file_path);
        $rel_path = str_replace("/sites/default/files/","/",$path);
        $file = file_save_data($file_data, 'public://'.$rel_path.$file_path, FILE_EXISTS_REPLACE);
        $file_name_no_ext = substr($file_name, 0, strrpos($file_name, "."));
        $file_title = ucwords(str_replace('_', ' ', $file_name_no_ext));
        $description = '';
        if(!empty($value[1])){
          $description = $value[1];
        }
        if($type == 'file'){
          $media = Media::create([
            'bundle'           => $type,
            'uid'              => \Drupal::currentUser()->id(),
            'field_media_file' => [
              'target_id' => $file->id(),
            ],
            'field_media_description' => $description,
            'field_media_title' => $file_title
          ]);
        }
        else if ($type == 'image'){
          $media = Media::create([
            'bundle'           => $type,
            'uid'              => \Drupal::currentUser()->id(),
            'field_media_image' => [
              'target_id' => $file->id(),
              'alt' => $file_title,
              'title' => $file_title
            ],
          ]);  
        }
        if(!empty($media)){
          $media->setName($file_name)->setPublished(TRUE)->save();
          return $media->id();  
        }
      }
       return '';
    }
}

