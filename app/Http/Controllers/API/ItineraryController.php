<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;

use App\Models\Itinerary;



class ItineraryController extends BaseController
{

    public function insertData(Request $request)
    {

        $input = $request->all();

        $tableName = 'Iteneraries';
        $timestamp = time();
        $item = [
            'itenerary_id' => strval($timestamp),
           ...$input
        ];

        $dynamoDbModel = new Itinerary;
        $result = $dynamoDbModel->putItem($tableName, $item);

        return $item;
    }


    public function getItinerarys()
    {

        $dynamoDbModel = new Itinerary;
        $tableName = 'Itinerary';

        $result = $dynamoDbModel->getItem($tableName);
        // code...

        return $result['Items'];
    }
}