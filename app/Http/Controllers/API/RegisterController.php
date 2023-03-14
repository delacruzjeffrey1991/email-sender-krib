<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Response;


use Validator;
use Illuminate\Support\Facades\Log;

use App\Models\Register;

   
class RegisterController extends BaseController
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


    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'username' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        Log::info($input);
        $user = User::create([
            'first_name' => $input['name'],
            'last_name' => $input['name'],
            'username' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
        $success['token'] =  $user->createToken('MyApp')->accessToken;
        $success['name'] =  $user->name;


   
        return $this->sendResponse($success, 'User register successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            $success['name'] =  $user->name;
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }


    public function unsubscribe(Request $request){

        $input = $request->all();
        $validator = Validator::make($input, [
            'contactList' => 'required',
            'email' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        try{
            $result = $this->sesV2Client()->deleteContact([
                'ContactListName' => urldecode($request->contactList), // REQUIRED
                'EmailAddress' => urldecode($request->email) // REQUIRED
            ]);

            $html = '<p>succesfully unsubscribe!</p>';
            return response($html, 200)->header('Content-Type', 'text/html');
        } catch (\Aws\SesV2\Exception\SesV2Exception $e) {
             return $this->sendError('Creating ContactList Failed.' , $e->getMessage());
        }
    }


    public function saveNewreferral(Request $request){


        $email = urldecode($request->email);
        $uuid = urldecode($request->uuid);
        $referral_id = urldecode($request->referral_id);
        $referred = urldecode($request->referred); 
        $city = urldecode($request->city);

        try{

            $tableName = 'Users';
            $item =  array(
                'id'      =>strval($uuid),
                'email'    => strval($email),
                'referralLink' => strval("https://localfyi.com/subscribe/" . $uuid), 
                'city' => strval($city),
            );

            //save this new user
            $dynamoDbModel = new Register;
            $result = $dynamoDbModel->putItem($tableName, $item);

            if($referred) {

                $dynamoDbModel = new Register;
                $TableName = 'Users';
                $result = $dynamoDbModel->scan($TableName);
                $userData = $result -> get('Items');

                for($x = 0; $x < count($userData); $x++){
                    $id = $userData[$x]['id']['S'];

                    if($id == $request->get('referral_id')){
                        $tableName = 'Referral';
                        $item =  array(
                            'referrer'      =>strval($referral_id),
                            'referee'   =>strval($uuid),
                            'created_at'    => strval(date("Y-m-d H:i:s")),
                            'updated_at' => strval(date("Y-m-d H:i:s"))
                        );
                        $dynamoDbModel = new Register;
                        $result = $dynamoDbModel->putItem($tableName, $item);
                    }
                }
            }
            $html =  <<<'EOT'
            <!doctype html>
            <html lang="en">
              <head>
                <!-- Required meta tags -->
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <!-- Bootstrap CSS -->
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
                <title>LocalFYI</title>
              </head>
              <body>
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-center mt-5"> 
                        <div class="w-50 shadow p-5 mt-5"> 
                            <div class="col-md-12 d-flex justify-content-center">
                                <i class="bi bi-check-circle-fill text-success " style="font-size: 80px;"></i> 
                            </div>
                            <div class="col-md-12 d-flex justify-content-center flex-column align-items-center">
                                <h6 class="fw-bold">Verified</h6> 
                                <p>Welcome to <a href="https://localfyi.com">LocalFYI.com</a> </p>
                                <p class="text-center">Your email address is succesfully verified. Go to dashboard to discover more.</p>
                                <a class="btn btn-primary" href="#">Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Optional JavaScript; choose one of the two! -->
                <!-- Option 1: Bootstrap Bundle with Popper -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
                <!-- Option 2: Separate Popper and Bootstrap JS -->
                <!--
                <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
                -->
              </body>
            </html>
            EOT;

            return response($html, 200)->header('Content-Type', 'text/html');
        }
        catch(\Exception $e){
            var_dump($e);
            $html = "<h1> Something went wrong</h1>";
            return response($html, 500)->header('Content-Type', 'text/html');
            
        }
    }


    //also send an confiramation email
    public function registerNewreferral(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'email' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        // check if the user already exists
        $dynamoDbModel = new Register;
        $TableName = 'Users';
        $result = $dynamoDbModel->scan($TableName);
        $userData = $result -> get('Items');
        for($x = 0; $x < count($userData); $x++){
            $email = $userData[$x]['email']['S'];
            if($email == $request->get('email')){
                return $this->sendError('User already exist'); 
            }
        }

        $uuid = Str::uuid()->toString(); 
        $email = $request->get('email');
        $path = env('APP_URL') . "register-confirmation";
        $city = $request->get('city');
        $referral_id = $request->referral_id;

        if($referral_id){
            $params = array(
                'email' => urlencode($email), 
                'uuid' => urlencode($uuid),
                'referral_id' => urlencode($referral_id),
                'city' => urlencode($city), 
                'referred' => true,
            );
        }
        else{
            $params = array(
                'email' => urlencode($email), 
                'uuid' => urlencode($uuid),
                'city' => urlencode($city), 
                'referred' => false,
            );
        }


        $param = http_build_query($params);
        $uri = $path . "?" . $param;

        
        $this->sesV2Client()->sendEmail([
            'Content' => [
                'Simple' => [
                    'Body' => [
                        'Text' => [
                            'Charset' => 'UTF-8',
                            'Data' => 'Welcome at localFYI.com please confirm your subscription by clicking this link ' .$uri,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => 'Confirmation',
                    ],
                ],
            ],
            'FromEmailAddress' => 'community@localfyi.com',
            'Destination' => [
                'ToAddresses' => [$email],
            ]
        ]);

        return $this->sendResponse($email, 'Confirmation email sent');
    }

}