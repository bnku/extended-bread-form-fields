<?php

namespace ExtendedBreadFormFields\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerMediaController;

class ExtendedBreadFormFieldsMediaController extends VoyagerMediaController
{
    
    public function remove(Request $request)
    {
        if($request->get('multiple_ext')){
            try {
                // GET THE SLUG, ex. 'posts', 'pages', etc.
                $slug = $request->get('slug');
    
                // GET image name
                $image = $request->get('image');
    
                // GET record id
                $id = $request->get('id');
    
                // GET field name
                $field = $request->get('field');
    
                // GET THE DataType based on the slug
                $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
    
                // Check permission
	            $user = Auth::user();
	            $user->hasPermission('delete_'.$dataType->name);
                //Voyager::canOrFail('delete_'.$dataType->name);
    
                // Load model and find record
                $model = app($dataType->model_name);
                $data = $model::find([$id])->first();
    
                // Check if field exists
                if (!isset($data->{$field})) {
                    throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
                }
    
                // Check if valid json
                if (is_null(@json_decode($data->{$field}))) {
                    throw new Exception(__('voyager::json.invalid'), 500);
                }
                
                // Decode field value
                $fieldData = @json_decode($data->{$field}, true);
                foreach ($fieldData as $i => $single) {
                    // Check if image exists in array
                    if(in_array($image,array_values($single)))
                        $founded = $i;
                }
                if(!isset($founded))
                    throw new Exception(__('voyager::media.image_does_not_exist'), 400);
                
                // Remove image from array
                unset($fieldData[$founded]);
    
                // Generate json and update field
                $data->{$field} = json_encode($fieldData);
                $data->save();
    
                return response()->json([
                   'data' => [
                       'status'  => 200,
                       'message' => __('voyager::media.image_removed'),
                   ],
                ]);
            } catch (Exception $e) {
                $code = 500;
                $message = __('voyager::generic.internal_error');
    
                if ($e->getCode()) {
                    $code = $e->getCode();
                }
    
                if ($e->getMessage()) {
                    $message = $e->getMessage();
                }
    
                return response()->json([
                    'data' => [
                        'status'  => $code,
                        'message' => $message,
                    ],
                ], $code);
            }
        } else{
            VoyagerMediaController::remove($request);
        }
    }

}
