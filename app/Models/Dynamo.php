<?php

namespace App\Models;
use \Aws\DynamoDb\Marshaler;
use \Aws\DynamoDb\DynamoDbClient;

class Dynamo extends Marshaler
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


    public function getItem($tableName)
    {
            $scan_response =  $this->dynamoDbClient->scan(array(
                'TableName' => $tableName 
            ));
        return $scan_response;
    }

      public function getItemById($tableName, $userId)
    {
           $scan_response = $this->dynamoDbClient->scan([
            'TableName' => $tableName,
            'FilterExpression' => '#id = :userId', // Condition to filter by userId
            'ExpressionAttributeNames' => [
                '#id' => 'id' // Attribute name mapping for the condition
            ],
            'ExpressionAttributeValues' => [
                ':userId' => ['S' => $userId] // Attribute value mapping for the condition
            ]
    ]);

    return $scan_response;
    }
}