<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;

use App\Models\Event;

use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;


class EventController extends BaseController
{

    public function insertData(Request $request)
    {


        $input = $request->all();

        // $tableName = 'Events';
        $tableName = 'Submitted_Events';
        $timestamp = time();

        if(isset($input['image'])) {
            $file = $this->uploadFile($input['image']);
            unset($input['image']);
        }

        $item = [
            'event_id' => strval($timestamp),
            'event_image' => isset($file) ? $file : "",
           ...$input
        ];


        $dynamoDbModel = new Event;
        $result = $dynamoDbModel->putItem($tableName, $item);

        return $item;
    }
    

    public function getBusiness()
    {
        $dynamoDbModel = new Event;
        $tableName = 'Business';

      
        $result = $dynamoDbModel->getItem($tableName);
        
        Log::info( $result['Items']);
        return $result['Items'];
    }




    public function getEvents(Request $request, $city = null, $when = null)
    {

        $cityParam = $request->input('city', $city);
        $whenParam = $request->input('when', $when);

        $dynamoDbModel = new Event;
        $tableName = 'Events';

      
        $result = $dynamoDbModel->getItem($tableName , $cityParam , $whenParam);

        

        if(!empty($result) && !empty($when)){
            $items = $result['Items'];
            if(!empty($items) && $when !== 'Anytime'){
                $filteredItems = collect($items)->filter(function ($item )  use ($when) {
                    $eventDate = strtotime($item['event_date']['S']);
                    $eventTime = date('H', $eventDate);

                    if($when == 'Morning'){
                        return $eventTime >= 5 &&  $eventTime < 12;
                    }else if($when == 'Afternoon'){
                         return $eventTime >= 12 &&  $eventTime < 17;
                    }else if($when == 'Evening'){
                         return $eventTime >= 17 &&  $eventTime < 21;
                    }else if($when == 'Night'){
                         return $eventTime >= 21 ||  $eventTime < 5;
                    }
                })->values()->all();
                $result['Items'] = $filteredItems;
            }
                 
        }
        
        Log::info( $result['Items']);
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

