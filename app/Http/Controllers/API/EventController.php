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
        $tableName = 'Events';
        $timestamp = time();
        $item = [
            'event_id' => strval($timestamp),
            'name' => 'John Doe',
            'email' => 'johndoe@example.com'
        ];

        $dynamoDbModel = new Event;
        $result = $dynamoDbModel->putItem($tableName, $item);

        return $result;
    }
    


   
}