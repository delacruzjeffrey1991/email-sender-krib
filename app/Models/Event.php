<?php

namespace App\Models;
use \Aws\DynamoDb\Marshaler;
use \Aws\DynamoDb\DynamoDbClient;

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


    public function getItem($tableName , $city = null )
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

        $scan_response = $this->dynamoDbClient->scan($params);
        return $scan_response;
    }
}