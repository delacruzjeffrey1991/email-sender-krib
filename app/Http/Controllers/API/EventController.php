<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;

use App\Models\Event;



class EventController extends BaseController
{

    public function insertData(Request $request)
    {

        $input = $request->all();

        $tableName = 'Events';
        $timestamp = time();
        $item = [
            'event_id' => strval($timestamp),
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
}