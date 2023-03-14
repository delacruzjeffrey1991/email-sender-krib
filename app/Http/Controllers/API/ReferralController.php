<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Response;


use Validator;
use Illuminate\Support\Facades\Log;


use App\Models\Register;


class ReferralController extends BaseController
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

    public function getReferralData(Request $request){

        var_dump($request);

        $user_id = urldecode($request->user_id);


        try{
            $dynamoDbModel = new Register;
            $TableName = 'Users';
            $result = $dynamoDbModel->scan($TableName);

            $TableName = 'Referral';
            $resultReferrals = $dynamoDbModel->scan($TableName);
        

            $userData = $result -> get('Items');
            $referralData = $resultReferrals -> get('Items');


            $referrals = [];
            for($x = 0; $x < count($userData); $x++){
                $id = $userData[$x]['id']['S'];
                if($id == $user_id){
                    $data = $userData[$x];
                }
            }

            for($b =0; $b <count($referralData); $b++){
                if($user_id == $referralData[$b]['referrer']['S']){
                    array_push($referrals, $referralData[$b]['referrer']['S']);
                }
            }

            $data['referrals'] = $referrals;
            $data['referralsCount'] = count($referrals);

            return $this->sendResponse($data, ' success');
        }
        catch (\Exception $e) {
            return $this->sendError('Something went wrong' , $e);
        }

    }
    



   
}