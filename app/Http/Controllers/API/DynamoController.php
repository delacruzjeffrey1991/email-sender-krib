<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;

use App\Models\Dynamo;
use Illuminate\Support\Str;


class DyanamoController extends BaseController
{

    public function getCities()
    {

        $dynamoDbModel = new Dynamo;
        $tableName = 'Cities';

        $result = $dynamoDbModel->getItem($tableName);

        return $result['Items'];
    }


    public function subscribeUser(Request $request)
    {

        $input = $request->all();
        $uuid = Str::uuid()->toString(); 
        $referralLink = strval("https://localfyi.com/subscribe/" . $uuid);

        $tableName = 'Users';
        $item = [
            'id' => $uuid,
            'referralLink' => $referralLink,
            'city' => $input['city'],
            'email' => $input['email']
           // ...$input
        ];

        $dynamoDbModel = new Dynamo;
        $result = $dynamoDbModel->putItem($tableName, $item);

        if($input['referral_id'] != '0') {
            $tableNameReferral = 'Referral';
            $itemReferral = [
                'referrer' => $input['referral_id'],
                'referee' => $uuid,
            ];

            $result = $dynamoDbModel->putItem($tableNameReferral, $itemReferral);

            return $itemReferral;
        }
        
        return $item;
    }

}