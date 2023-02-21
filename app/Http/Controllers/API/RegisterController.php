<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Validator;
use Illuminate\Support\Facades\Log;

use App\Models\Register;

   
class RegisterController extends BaseController
{
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


    public function registerNewreferral(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            // 'referral_id' => 'required',
            'email' => 'required',

        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        /* step 1 check if the email is already existing at the database
           step 2 get the referrral id check if exist
        */

        $dynamoDbModel = new Register;


        $TableName = 'Users';
        $result = $dynamoDbModel->scan($TableName);

        $userData = $result -> get('Items');

        // for($x = 0; $x < count($userData); $x++){
        //  $email = $userData[$x]['email']['S'];
        //  if($email == $request->get('email')){
        //     return $this->sendError('User already exist'); 
        //  }
        // }


        //generate the referral id
        $uuid = Str::uuid()->toString(); 

        //save this new user
        $param =  array(
            'id'      => array('S' => $uuid),
            'email'    => array('S' => $request->get('email')),
            'referralCount'   => array('N' => 0),
            'referralLink' => array('S' => "https://localfyi.com/subscribe/?" . $uuid)
        );





        if(isset($request['referral_id'])) {
            

        }

       

    }
}