<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use Illuminate\Support\Facades\Log;

class ContactController extends BaseController
{
    private function sesV2Client()
    {
        $credentials = new \Aws\Credentials\Credentials(\Config::get('aws.credentials.key') , \Config::get('aws.credentials.secret'));
        $client = new  \Aws\SesV2\SesV2Client([
            'credentials' => $credentials,
            'version' => 'latest',

            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);

        return $client;
    }

   
    public function create(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        try{
            $result = $this->sesV2Client()->createContactList([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'Description' => $request->get('description'),
                // 'Tags' => [
                //     [
                //         'Key' => $request->get('tag_key'), // REQUIRED
                //         'Value' => $request->get('tag_value'), // REQUIRED
                //     ],
                //     // ...
                // ],
                // 'Topics' => [
                //     [
                //         'DefaultSubscriptionStatus' => 'OPT_IN|OPT_OUT', // REQUIRED
                //         'Description' => '<string>',
                //         'DisplayName' => '<string>', // REQUIRED
                //         'TopicName' => '<string>', // REQUIRED
                //     ],
                //     // ...
                // ],
            ]);




            return $this->sendResponse($result->toArray(), 'Contact List Created!');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Creating ContactList Failed.' , $e->getMessage());
        }     
    }
    
    public function addTopic(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'topics.*.DisplayName' => 'required',
            'topics.*.TopicName' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        try{

            $result = $this->sesV2Client()->getContactList([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
            ]);

            $topicsOriginal = $result->get('Topics');

            $topics = [];      
            foreach ( $request->get('topics') as  $topic) {
                $topic['DefaultSubscriptionStatus'] = 'OPT_OUT';
                array_push($topics , $topic);
            }

            Log::info($topics);

            $topicsCombined = array_merge($topics , $topicsOriginal);



            $result = $this->sesV2Client()->updateContactList([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'Description' => $request->get('description'),
                 'Topics' => $topicsCombined
  
            ]);




            return $this->sendResponse($result->toArray(), 'Topic has been added');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Creating ContactList Failed.' , $e->getMessage());
        }     
    }

    public function updateContactList(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'topics.*.DisplayName' => 'required',
            'topics.*.TopicName' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $topics = [];      
        foreach ( $request->get('topics') as  $topic) {
            $topic['DefaultSubscriptionStatus'] = 'OPT_OUT';
            array_push($topics , $topic);
        }

        try{
            $result = $this->sesV2Client()->updateContactList([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'Description' => $request->get('description'),
                 'Topics' => $topics
  
            ]);




            return $this->sendResponse($result->toArray(), 'Contact List Updated');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Creating ContactList Failed.' , $e->getMessage());
        }     
    }

    public function getContactList(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        try{
            $result = $this->sesV2Client()->getContactList([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
            ]);
            
            return $this->sendResponse($result->toArray(), 'ContactList get successfully.');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Creating ContactList Failed.' , $e->getMessage());
        }     
    }
    
    public function createContact(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'email' => 'required',
            'topics.*.TopicName' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        
        $topics = [];      
        foreach ( $request->get('topics') as  $topic) {
            $topic['SubscriptionStatus'] = 'OPT_OUT';
            array_push($topics , $topic);
        }


        try{
            $result = $this->sesV2Client()->createContact([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'EmailAddress' => $request->get('email'), // REQUIRED
                'TopicPreferences' => $topics

            ]);

            return $this->sendResponse($result->toArray(), 'Contact Created');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError(' Failed.' , $e->getMessage());
        }     
    }

    public function addContactsTopics(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'emails' => 'required',
            'topic_name' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }


        echo($request);

        
        try{
            
            
            foreach ( $request->get('emails') as  $email) {
                $result = $this->sesV2Client()->createContact([
                    'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                    'EmailAddress' => $email, // REQUIRED
                    'TopicPreferences' => [
                        [
                            'SubscriptionStatus' => 'OPT_OUT', // REQUIRED
                            'TopicName' => $request->get('topic_name'), // REQUIRED
                        ],
                        // ...
                    ],
    
                ]);
            }


            $this->sesV2Client()->sendEmail([
                'ConfigurationSetName' => 'send_EmailListener',
                'Content' => [
                    'Simple' => [
                        'Body' => [
                            'Text' => [
                                'Charset' => 'UTF-8',
                                'Data' => 'Welcome at localFYI.com',
                            ],
                        ],
                        'Subject' => [
                            'Charset' => 'UTF-8',
                            'Data' => 'Welcome Email',
                        ],
                    ],
                ],
                'FromEmailAddress' => 'roy@localfyi.com',
                'Destination' => [
                    'ToAddresses' => [$email],
                ]
            ]);


            return $this->sendResponse($request->get('emails'), 'Contact Created');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError(' Failed.' , $e->getMessage());
        }     
    }


    public function getContact(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'email' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        try{
            $result = $this->sesV2Client()->getContact([
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'EmailAddress' => $request->get('email'), // REQUIRED
            ]);
            
            return $this->sendResponse($result->toArray(), 'Contact get successfully.');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError(' Failed.' , $e->getMessage());
        }     
    }

    public function listContact(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'contact_list_name' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $payload = [];

        if(!empty($request->get('topic_name'))){

            $payload =  [
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
                'Filter' => [
                    'FilteredStatus' => 'OPT_OUT',
                    'TopicFilter' => [
                        'TopicName' => $request->get('topic_name'),
                        'UseDefaultIfPreferenceUnavailable' =>  false,
                    ],
                ],
            ];
            
        }else{
            $payload = [
                'ContactListName' => $request->get('contact_list_name'), // REQUIRED
   
            ];
        }

        try{
            $result = $this->sesV2Client()->listContacts($payload);
            $contacts = $result->get('Contacts');

            $emails = [];
            foreach ($contacts as $key => $contact) {
                array_push($emails , $contact['EmailAddress']);
            }

            
            return $this->sendResponse($emails, 'Contact get successfully.');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError(' Failed.' , $e->getMessage());
        }     
    }
    



   
}