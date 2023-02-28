<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;

use App\Models\Event;

use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;


class EventController extends BaseController
{

    public function insertData(Request $request)
    {


        $input = $request->all();

        $file = $this->uploadFile($input['image']);
        $tableName = 'Events';
        $timestamp = time();

        unset($input['image']);

        $item = [
            'event_id' => strval($timestamp),
            'event_image' => $file,
           ...$input
        ];


        $dynamoDbModel = new Event;
        $result = $dynamoDbModel->putItem($tableName, $item);

        return $item;
    }


    public function getEvents()
    {

        $dynamoDbModel = new Event;
        $tableName = 'Events';

        $result = $dynamoDbModel->getItem($tableName);
        // code...

        return $result['Items'];
    }



    public function uploadFile($fileItem)
    {

        // Get the file from the request
        $file = $fileItem;

        // Generate a unique filename for the image
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        // Upload the image to S3
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $s3->putObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $filename,
            'Body' => fopen($file, 'r'),
            'ACL' => 'public-read',
        ]);

        // Save the image URL to the database or return it in the response
        $imageUrl = $s3->getObjectUrl( env('AWS_BUCKET') , $filename);

        return $imageUrl;
    }

}

