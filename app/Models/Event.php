<?php

namespace App\Models;
use \Aws\DynamoDb\Marshaler;
use \Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Log;

class Event extends Marshaler
{
    private $dynamoDbClient;

    public function __construct()
    {
        $credentials = new \Aws\Credentials\Credentials(\Config::get('aws.credentials.key') , \Config::get('aws.credentials.secret'));
        $this->dynamoDbClient  = new  DynamoDbClient([
            'credentials' => $credentials,
            'version' => 'latest',

            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);
        // $this->dynamoDbClient = new DynamoDbClient([
        //     'region'  => 'us-west-2',
        //     'version' => 'latest'
        // ]);
    }

    public function putItem($tableName, $item)
    {
        $marshaler = $this;

        $item = $marshaler->marshalItem($item);

        $params = [
            'TableName' => $tableName,
            'Item' => $item
        ];

        return $this->dynamoDbClient->putItem($params);
    }


   public function getItem($tableName , $city = null , $when = null)
{
    $params = [
        'TableName' => $tableName,
    ];

    if ($city !== null) {
        $params['FilterExpression'] = '#city = :city';
        $params['ExpressionAttributeNames'] = [
            '#city' => 'city',
        ];
        $params['ExpressionAttributeValues'] = [
            ':city' => ['S' => $city],
        ];
    }

    // Add filter expression for event_date hour
    // if ($when !== null) {
    //     if($when == 'Morning'){
    //         $eventDateStart = date('Y-m-d\T05:00:00\Z', strtotime('now'));
    //          $eventDateEnd = date('Y-m-d\T12:00:00\Z', strtotime('now'));
    //     }else if($when == 'Afternoon'){
    //         $eventDateStart = date('Y-m-d\T12:00:00\Z', strtotime('now'));
    //          $eventDateEnd = date('Y-m-d\T17:00:00\Z', strtotime('now'));
    //     }else if($when == 'Evening'){
    //         $eventDateStart = date('Y-m-d\T17:00:00\Z', strtotime('now'));
    //          $eventDateEnd = date('Y-m-d\T21:00:00\Z', strtotime('now'));
    //     }else if($when == 'Night'){
    //         $eventDateStart = date('Y-m-d\T21:00:00\Z', strtotime('now'));
    //          $eventDateEnd = date('Y-m-d\T05:00:00\Z', strtotime('+1 day'));
    //     }

    //     if (isset($params['FilterExpression'])) {
    //         $params['FilterExpression'] .= ' AND ';
    //     } else {
    //         $params['FilterExpression'] = '';
    //     }



    //      $params['FilterExpression'] .= ' #event_date BETWEEN :start AND :end';
    //       $params['ExpressionAttributeNames']['#event_date'] = 'event_date';
    //     $params['ExpressionAttributeValues'][':start'] = ['S' => $eventDateStart];
    //     $params['ExpressionAttributeValues'][':end'] = ['S' => $eventDateEnd];
    // }

    $scan_response = $this->dynamoDbClient->scan($params);
    return $scan_response;
}
}