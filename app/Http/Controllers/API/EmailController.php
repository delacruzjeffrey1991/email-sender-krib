<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\EmailApproval;
// use PhpSpellcheck\Spellchecker\Aspell;
use Mekras\Speller\Aspell\Aspell;
use Mekras\Speller\Source\StringSource;



class EmailController extends BaseController
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

    public function sendEmail(Request $request)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'emails' => 'required',
            'message' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }


        $SesClient =  \AWS::createClient('ses');
        // <h1>AWS Amazon Simple Email Service Test Email</h1>
        // <p>This email was sent with <a href="https://aws.amazon.com/ses/">
        // Amazon SES</a> using the <a href="https://aws.amazon.com/sdk-for-php/">
        // AWS SDK for PHP</a>.</p>

        $html_body = $request->get('message');
        $subject = 'Amazon SES test (AWS SDK for PHP)';
        $plaintext_body = '';
        $sender_email = 'jeffrey@localfyi.com';
        $recipient_emails = ['jeffrey@localfyi.com'];
        $char_set = 'UTF-8';
        $configuration_set = 'ConfigSet';
        
        try {
            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $recipient_emails,
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
        
                    'Body' => [
                        'Html' => [
                            'Charset' => $char_set,
                            'Data' => $html_body,
                        ],
                        'Text' => [
                            'Charset' => $char_set,
                            'Data' => $plaintext_body,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => $char_set,
                        'Data' => $subject,
                    ],
                ],

            ]);
            return $this->sendResponse($recipient_emails, 'Emails Sent successfully.');
        } catch (\Aws\Ses\Exception\SesException $e) {
             return $this->sendError('Emails Sending Failed.' , $e->getMessage());
        }      
    }
    
    public function sendEmailByTopic(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'message' => 'required',
            'topic_name' => 'required',
            'subject' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $html_body = $request->get('message');
        $char_set = 'UTF-8';
        $subject = $request->get('subject');

        try{
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

            $result = $this->sesV2Client()->listContacts($payload);
            $recepients = [];
            $contact_lists = $result->get('Contacts');
            foreach ($contact_lists as $key => $contact) {
                
                $result = $this->sesV2Client()->sendEmail([
                    'Content' => [ // REQUIRED
    
                        'Simple' => [
                            'Body' => [ // REQUIRED
                                'Html' => [
                                    'Charset' => $char_set,
                                    'Data' => $html_body, // REQUIRED
                                ],
    
                            ],
                            'Subject' => [ // REQUIRED
                                'Charset' => $char_set,
                                'Data' => $subject, // REQUIRED
                            ],
                        ],
                        
                    ],
                    'Destination' => [
                        'ToAddresses' => [$contact['EmailAddress']],
                    ],
                    'FromEmailAddress' => 'jeffrey@localfyi.com',
                    'ReplyToAddresses' => ['jeffrey@localfyi.com'],
                    
                ]);

                array_push($recepients , $contact['EmailAddress']);
            }

            return $this->sendResponse($recepients, 'Email Sent!');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Failed.' , $e->getMessage());
        }     
    }


public function saveEmail(Request $request)
{
        $input = $request->all();

        $validator = Validator::make($input, [
            'contact_list_name' => 'required',
            'message' => 'required',
            'topic_name' => 'required',
            'subject' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $speller = new Aspell("/usr/bin/aspell");
        $source = new StringSource(strip_tags($input['message']));
        $issues = $speller->checkText($source, ['en']);

        $wordIssues = [];
        foreach($issues as $issue) {
            array_push($wordIssues, $issue->word);
        }

        if(count($wordIssues)){
            return $this->sendError('Error words.', $wordIssues);
        }

         $data = DB::table('email_approvals')->insert([
            ...$request->all(),
            "status" => "pending"
         ]);

        return $this->sendResponse($data, 'Email created successfully.');
}

    public function updateEmail(Request $request, $id)
    {
        $email = EmailApproval::find($id);
        if (is_null($email)) {
            session()->flash('error', "The page is not found !");
            return redirect()->route('admin.emailApprovals.index');
        }

        $request->validate([
            'subject'  => 'required',
            'message'  => 'required'
        ]);
        Log::info('jepri');
        Log::info($request->get("message"));
        // if($validator->fails()){
        //     return $this->sendError('Validation Error.', $validator->errors());       
        // }


        try {
            $email->subject = $request->get("subject");
            $email->message = $request->get("message");
            $email->save();

            session()->flash('success', 'Blog has been updated successfully !!');
            return $this->sendResponse($email, 'Email created successfully.');
        } catch (\Exception $e) {
            session()->flash('sticky_error', $e->getMessage());
            return back();
        }
    }


    public function updateEmailStatus(Request $request, $id)
    {
        $email = EmailApproval::find($id);
        if (is_null($email)) {
            session()->flash('error', "The page is not found !");
            return redirect()->route('admin.emailApprovals.index');
        }

        $request->validate([
            'status'  => 'required|max:100',
        ]);

        try {
            $email->status = $request->get("status");
            $email->save();

            session()->flash('success', 'Blog has been updated successfully !!');
            return $this->sendResponse($email, 'Email created successfully.');
        } catch (\Exception $e) {
            session()->flash('sticky_error', $e->getMessage());
            return back();
        }
    }

   
}