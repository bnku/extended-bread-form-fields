<?php

namespace ExtendedBreadFormFields\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use TCG\Voyager\Http\Controllers\Controller;
use TCG\Voyager\Facades\Voyager;

use ExtendedBreadFormFields\ContentTypes\MultipleImagesWithAttrsContentType;
use ExtendedBreadFormFields\ContentTypes\KeyValueJsonContentType;

class ExtendedBreadFormFieldsController extends VoyagerBaseController
{

    public function getContentBasedOnType(Request $request, $slug, $row, $options = null)
    {
        switch ($row->type) {
            case 'key-value_to_json':
                return (new KeyValueJsonContentType($request, $slug, $row, $options))->handle();
            case 'multiple_images_with_attrs':
                return (new MultipleImagesWithAttrsContentType($request, $slug, $row, $options))->handle();
            default:
                return Controller::getContentBasedOnType($request, $slug, $row, $options);
        }
    }


    public function insertUpdateData($request, $slug, $rows, $data)
    {
        foreach ($rows as $row) {
            if ($row->type == 'multiple_images_with_attrs'){
                $is_multiple_image_attrs = 1;
                $fieldName = $row->field;
                $ex_files = json_decode($data->{$row->field}, true);
                $request->except("{$row->field}");
            }
        }

        $new_data = VoyagerBaseController::insertUpdateData($request, $slug, $rows, $data);
        
        if(isset($is_multiple_image_attrs)){
            foreach ($rows as $row) {
                $content = $new_data->$fieldName;
                if ($row->type == 'multiple_images_with_attrs' && !is_null($content) && $ex_files != json_decode($content,1)) {
                    if (isset($data->{$row->field})) {
                        if (!is_null($ex_files)) {
                            $content = json_encode(array_merge($ex_files, json_decode($content,1)));
                        }
                    }
                    $new_content = $content;
                }
            }
            
            if(isset($new_content)) $content = json_decode($new_content,1);
                else $content = json_decode($content,1);
            
            if(isset($content)){
                $end_content = [];
                foreach ($content as $i => $value) {
                    if(isset($request->{$fieldName.'_ext'}[$i])){
                        $end_content[] = array_merge($content[$i], $request->{$fieldName.'_ext'}[$i]);
                    }else{
                        $end_content[] = $content[$i];
                    }
                }
                $data->{$fieldName} = json_encode($end_content);
            }
            
            $data->save();
                    
            return $data;
        } else {
            return $new_data;
        }
    }
}
