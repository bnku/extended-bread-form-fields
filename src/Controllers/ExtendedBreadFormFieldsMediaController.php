<?php

namespace ExtendedBreadFormFields\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\ContentTypes\Image;
use TCG\Voyager\Http\Controllers\VoyagerMediaController;
use TCG\Voyager\Http\Controllers\VoyagerController;

class ExtendedBreadFormFieldsMediaController extends VoyagerMediaController
{
    public function upload(Request $request)
    {
        $slug = $request->input('slug');

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $user = Auth::user();
        $user->hasPermission('edit_' . $dataType->name);
        $user->hasPermission('add_' . $dataType->name);

        // $this->authorize('edit_'.$dataType->name);
        //$this->authorize('add_'.$dataType->name);

        $file    = $request->file('image');
        $row     = unserialize(base64_decode($request->input('row')));
        $options = unserialize($request->input('options'));
        $image   = new Image($request, $slug, $row, $options);
        $id      = $request->input('id');

        $fieldName = $row->field;

        $obj = new \stdClass;
        $img = new \stdClass;

        if ($image) {
            $obj->success  = true;
            $obj->filename = $image->handle();
            $obj->image    = Voyager::image($obj->filename);

            try {
                $model     = app($dataType->model_name);
                $data      = $model::find([$id])->first();
                $fieldData = json_decode($data->{$fieldName});

                $img->name  = $obj->filename;
                $img->alt   = null;
                $img->title = null;
                $img->sort  = 0;

                $fieldData[]        = $img;
                $data->{$fieldName} = json_encode($fieldData);
                $data->save();

            } catch (Exception $e) {
                $data      = new $model();
                $fieldData = [];

                $img->name   = $obj->filename;
                $img->alt    = null;
                $img->title  = null;
                $img->sort   = 0;
                $fieldData[] = $img;

                $data->{$fieldName} = json_encode($fieldData);
                $data->save();

            }

        } else {
            $obj->success = false;
        }


        echo json_encode($obj);
    }

    public function remove(Request $request)
    {


        if ($request->get('multiple_ext')) {
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
                $user     = Auth::user();
                $user->hasPermission('delete_' . $dataType->name);


                // Load model and find record
                $model = app($dataType->model_name);
                $data  = $model::find([$id])->first();

                // Check if field exists
                if ( ! isset($data->{$field})) {
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
                    if (in_array($image, array_values($single))) {
                        $founded = $i;
                    }
                }
                if ( ! isset($founded)) {
                    throw new Exception(__('voyager::media.image_does_not_exist'), 400);
                }

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
                $code    = 500;
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
        } else {
            VoyagerMediaController::remove($request);
        }
    }

    public function sort(Request $request)
    {

        $slug     = $request->input('slug');
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $user = Auth::user();
        $user->hasPermission('edit_' . $dataType->name);
        $user->hasPermission('add_' . $dataType->name);

        $id         = $request->input('id');
        $field      = $request->input('field');
        $sortedList = $request->input('sortedList');

        $model          = app($dataType->model_name);
        $data           = $model::find([$id])->first();
        $data->{$field} = $sortedList;
        $data->save();
    }

}
