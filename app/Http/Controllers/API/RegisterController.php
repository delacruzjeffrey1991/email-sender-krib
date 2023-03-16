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
                $referralId = Str::uuid()->toString(); 

                for($x = 0; $x < count($userData); $x++){
                    $id = $userData[$x]['id']['S'];

                    if($id == $request->get('referral_id')){
                        $tableName = 'Referral';
                        $item =  array(
                            'referral_id' => $referralId,
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
            <!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
            <head>
            <!--[if gte mso 9]>
            <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
            </xml>
            <![endif]-->
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="x-apple-disable-message-reformatting">
            <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->
            <title></title>
            
                <style type="text/css">
                @media only screen and (min-width: 520px) {
            .u-row {
                width: 500px !important;
            }
            .u-row .u-col {
                vertical-align: top;
            }

            .u-row .u-col-100 {
                width: 500px !important;
            }

            }

            @media (max-width: 520px) {
            .u-row-container {
                max-width: 100% !important;
                padding-left: 0px !important;
                padding-right: 0px !important;
            }
            .u-row .u-col {
                min-width: 320px !important;
                max-width: 100% !important;
                display: block !important;
            }
            .u-row {
                width: 100% !important;
            }
            .u-col {
                width: 100% !important;
            }
            .u-col > div {
                margin: 0 auto;
            }
            }
            body {
            margin: 0;
            padding: 0;
            }

            table,
            tr,
            td {
            vertical-align: top;
            border-collapse: collapse;
            }

            p {
            margin: 0;
            }

            .ie-container table,
            .mso-container table {
            table-layout: fixed;
            }

            * {
            line-height: inherit;
            }

            a[x-apple-data-detectors='true'] {
            color: inherit !important;
            text-decoration: none !important;
            }

            table, td { color: #000000; } </style>

            </head>

            <body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #f0f0e4;color: #000000">
            <!--[if IE]><div class="ie-container"><![endif]-->
            <!--[if mso]><div class="mso-container"><![endif]-->
            <table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #f0f0e4;width:100%" cellpadding="0" cellspacing="0">
            <tbody>
            <tr style="vertical-align: top">
                <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #f0f0e4;"><![endif]-->
                

            <div class="u-row-container" style="padding: 0px;background-color: transparent">
            <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 500px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:500px;"><tr style="background-color: transparent;"><![endif]-->
                
            <!--[if (mso)|(IE)]><td align="center" width="500" style="width: 500px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
            <div class="u-col u-col-100" style="max-width: 320px;min-width: 500px;display: table-cell;vertical-align: top;">
            <div style="height: 100%;width: 100% !important;">
            <!--[if (!mso)&(!IE)]><!--><div style="box-sizing: border-box; height: 100%; padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->
            
            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                    
            <div>
                <style type="text/css">


            @font-face {
                font-family: GT Eesti;
                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Regular-2.woff');
            }

            @font-face {
                font-family: GT Eesti UltraBold;
                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Ultra-Bold.woff2');
            }

            @font-face {
                font-family: GT EestineLight;
                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Light-2.woff2');
            }

            @font-face {
                font-family: GT EestineMedium;
                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Medium-2.woff');
            }

            *{
            font-family: GT Eesti !important;
            }


            h1, h2, h3, h4, h5{
            font-family: GT EestineMedium !important;
            }

            </style>
            </div>

                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:50px 10px 10px;font-family:arial,helvetica,sans-serif;" align="left">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                
                <img align="center" border="0" src="data:image/png;base64,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" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 25%;max-width: 120px;" width="120"/>
                </td>
            </tr>
            </table>
                </td>
                </tr>
            </tbody>
            </table>
            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                    
            <div style="font-size: 46px; font-weight: 700; line-height: 120%; text-align: center; word-wrap: break-word;">
                <p style="line-height: 120%;">Hey <span style="color: #23d965; line-height: 55.2px;">{$email}</span>,<br />welcome to your<br />localfyi experience.</p>
            </div>

                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:30px 10px 10px;font-family:arial,helvetica,sans-serif;" align="left">
                    
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                
                <img align="center" border="0" src="https://localfyi-public.s3.amazonaws.com/11367870821.png" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 480px;" width="480"/>
                
                </td>
            </tr>
            </table>

                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                    
            <div style="font-size: 15px; font-weight: 400; line-height: 140%; text-align: center; word-wrap: break-word;">
                <p style="line-height: 140%;">Welcome to Localfyi, the go-to platform for discovering<br />exciting local events in your area.</p>
            </div>

                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:12px;font-family:arial,helvetica,sans-serif;" align="left">
                    
            <div style="font-size: 15px; font-weight: 400; line-height: 140%; text-align: center; word-wrap: break-word;">
                <p style="line-height: 140%;">We understand how important it is to stay up-to-date<br />on what's happening in your community, and that's<br />why we've created this platform to help you find and<br />attend events that matter most to you.</p>
            </div>

                </td>
                </tr>
            </tbody>
            </table>

            <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
            </div>
            </div>
            <!--[if (mso)|(IE)]></td><![endif]-->
                <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                </div>
            </div>
            </div>
            <div class="u-row-container" style="padding: 0px;background-color: transparent">
            <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 500px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:500px;"><tr style="background-color: transparent;"><![endif]-->
                
            <!--[if (mso)|(IE)]><td align="center" width="500" style="background-color: #000000;width: 500px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
            <div class="u-col u-col-100" style="max-width: 320px;min-width: 500px;display: table-cell;vertical-align: top;">
            <div style="background-color: #000000;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">
            <!--[if (!mso)&(!IE)]><!--><div style="box-sizing: border-box; height: 100%; padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:40px 30px 30px;font-family:arial,helvetica,sans-serif;" align="left">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                
                <img align="center" border="0" src="https://localfyi-public.s3.amazonaws.com/logo2.PNG" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 22%;max-width: 96.8px;" width="96.8"/>
                
                </td>
            </tr>
            </table>

                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
            <div>
                <div class="col-md-12" style="text-align: center;"> <svg width="150" height="24" viewBox="0 0 150 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_417_33)"><path d="M11.3147 8.87472H8.625V12.461H11.3147V23.2198H15.7976V12.461H19.0252L19.3839 8.87472H15.7976V7.35054C15.7976 6.54363 15.9769 6.185 16.7838 6.185H19.3839V1.70215H15.9769C12.7492 1.70215 11.3147 3.13666 11.3147 5.82638V8.87472Z" fill="white"></path></g><g clip-path="url(#clip1_417_33)"><path d="M50.3232 4.53758C52.9531 4.53758 53.2819 4.53758 54.3503 4.61977C57.0624 4.70195 58.2953 6.01692 58.3774 8.64686C58.4596 9.7153 58.4596 9.96184 58.4596 12.5918C58.4596 15.2217 58.4596 15.5505 58.3774 16.5367C58.2953 19.1666 56.9803 20.4816 54.3503 20.5638C53.2819 20.646 53.0354 20.646 50.3232 20.646C47.6933 20.646 47.3645 20.646 46.3783 20.5638C43.6662 20.4816 42.4334 19.1666 42.3512 16.5367C42.269 15.4683 42.269 15.2217 42.269 12.5918C42.269 9.96184 42.269 9.63306 42.3512 8.64686C42.4334 6.01692 43.7484 4.70195 46.3783 4.61977C47.3645 4.53758 47.6933 4.53758 50.3232 4.53758ZM50.3232 2.72949C47.6111 2.72949 47.2823 2.72949 46.2961 2.81168C42.6799 2.97605 40.7075 4.9485 40.5431 8.56467C40.4609 9.55091 40.4609 9.87961 40.4609 12.5918C40.4609 15.3039 40.4609 15.6326 40.5431 16.6189C40.7075 20.235 42.6799 22.2075 46.2961 22.3719C47.2823 22.454 47.6111 22.454 50.3232 22.454C53.0354 22.454 53.3641 22.454 54.3503 22.3719C57.9665 22.2075 59.9389 20.235 60.1033 16.6189C60.1855 15.6326 60.1855 15.3039 60.1855 12.5918C60.1855 9.87961 60.1855 9.55091 60.1033 8.56467C59.9389 4.9485 57.9665 2.97605 54.3503 2.81168C53.3641 2.72949 53.0354 2.72949 50.3232 2.72949ZM50.3232 7.49626C47.5289 7.49626 45.2277 9.79745 45.2277 12.5918C45.2277 15.3861 47.5289 17.6873 50.3232 17.6873C53.1175 17.6873 55.4188 15.3861 55.4188 12.5918C55.4188 9.79745 53.1175 7.49626 50.3232 7.49626ZM50.3232 15.8792C48.5151 15.8792 47.0358 14.3999 47.0358 12.5918C47.0358 10.7837 48.5151 9.30434 50.3232 9.30434C52.1313 9.30434 53.6106 10.7837 53.6106 12.5918C53.6106 14.3999 52.1313 15.8792 50.3232 15.8792ZM55.5831 6.18129C54.9256 6.18129 54.4325 6.6744 54.4325 7.33189C54.4325 7.98938 54.9256 8.48249 55.5831 8.48249C56.2406 8.48249 56.7337 7.98938 56.7337 7.33189C56.7337 6.6744 56.2406 6.18129 55.5831 6.18129Z" fill="white"></path></g><g clip-path="url(#clip2_417_33)"><path d="M85.1693 5.98407C85.1693 7.03007 84.3474 7.85192 83.3014 7.85192C82.2554 7.85192 81.4336 7.03007 81.4336 5.98407C81.4336 4.93807 82.2554 4.11621 83.3014 4.11621C84.3474 4.11621 85.1693 4.93807 85.1693 5.98407ZM85.1693 9.34621H81.4336V21.3005H85.1693V9.34621ZM91.1465 9.34621H87.4107V21.3005H91.1465V15.0245C91.1465 11.5129 95.6293 11.2141 95.6293 15.0245V21.3005H99.365V13.7544C99.365 7.85193 92.7155 8.07607 91.1465 10.9899V9.34621Z" fill="white"></path></g><g clip-path="url(#clip3_417_33)"><path d="M131.341 1.70215C125.385 1.70215 120.559 6.52878 120.559 12.4842C120.559 17.0539 123.402 20.9539 127.411 22.525C127.318 21.6699 127.23 20.3643 127.449 19.4335C127.647 18.5912 128.713 14.0762 128.713 14.0762C128.713 14.0762 128.388 13.4318 128.388 12.4758C128.388 10.9763 129.256 9.85608 130.338 9.85608C131.256 9.85608 131.703 10.5468 131.703 11.3765C131.703 12.303 131.113 13.6845 130.81 14.9649C130.557 16.0388 131.349 16.9149 132.406 16.9149C134.323 16.9149 135.797 14.8933 135.797 11.9788C135.797 9.39698 133.943 7.59014 131.294 7.59014C128.228 7.59014 126.426 9.88972 126.426 12.2694C126.426 13.1959 126.784 14.1899 127.23 14.729C127.318 14.8343 127.331 14.9312 127.306 15.0365C127.226 15.3776 127.04 16.1104 127.007 16.2579C126.96 16.4558 126.851 16.4979 126.645 16.4011C125.297 15.7735 124.454 13.8066 124.454 12.223C124.454 8.81996 126.927 5.69908 131.576 5.69908C135.316 5.69908 138.223 8.3651 138.223 11.9282C138.223 15.6429 135.881 18.6333 132.629 18.6333C131.539 18.6333 130.511 18.0647 130.157 17.395C130.157 17.395 129.618 19.4546 129.487 19.96C129.243 20.895 128.586 22.07 128.148 22.786C129.159 23.0977 130.229 23.2662 131.341 23.2662C137.296 23.2662 142.123 18.4396 142.123 12.4842C142.123 6.52878 137.296 1.70215 131.341 1.70215Z" fill="white"></path></g><defs><clipPath id="clip0_417_33"><rect width="10.782" height="21.564" fill="white" transform="translate(8.625 1.70215)"></rect></clipPath><clipPath id="clip1_417_33"><rect width="20.1264" height="20.1264" fill="white" transform="translate(40.1523 2.4209)"></rect></clipPath><clipPath id="clip2_417_33"><rect width="18.6888" height="17.97" fill="white" transform="translate(81.0234 3.5)"></rect></clipPath><clipPath id="clip3_417_33"><rect width="22.2828" height="21.564" fill="white" transform="translate(120.457 1.70215)"></rect></clipPath></defs></svg> </div>
            </div>
                </td>
                </tr>
            </tbody>
            </table>

            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>
                <tr>
                <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
            <div style="font-size: 12px; line-height: 140%; text-align: center; word-wrap: break-word;">
                <p style="line-height: 140%;"><span style="color: #ffffff; line-height: 19.6px;">© 2023 Localfyi | Terms of Service | Privacy Policy</span></p>
            </div>
                </td>
                </tr>
            </tbody>
            </table>
            <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
            </div>
            </div>
            <!--[if (mso)|(IE)]></td><![endif]-->
                <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                </div>
            </div>
            </div>
                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                </td>
            </tr>
            </tbody>
            </table>
            <!--[if mso]></div><![endif]-->
            <!--[if IE]></div><![endif]-->
            </body>
            </html>
            EOT;


            $this->sesV2Client()->sendEmail([
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
                            'Data' => 'Confirmation',
                        ],
                    ],
                ],
                'FromEmailAddress' => 'community@localfyi.com',
                'Destination' => [
                    'ToAddresses' => [$email],
                ]
            ]);

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
                            'Data' => <<<'EOT'
                            <!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                            <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
                            <head>
                            <!--[if gte mso 9]>
                            <xml>
                              <o:OfficeDocumentSettings>
                                <o:AllowPNG/>
                                <o:PixelsPerInch>96</o:PixelsPerInch>
                              </o:OfficeDocumentSettings>
                            </xml>
                            <![endif]-->
                              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                              <meta name="viewport" content="width=device-width, initial-scale=1.0">
                              <meta name="x-apple-disable-message-reformatting">
                              <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->
                              <title></title>
                              
                                <style type="text/css">
                                  @media only screen and (min-width: 520px) {
                              .u-row {
                                width: 500px !important;
                              }
                              .u-row .u-col {
                                vertical-align: top;
                              }
                            
                              .u-row .u-col-100 {
                                width: 500px !important;
                              }
                            
                            }
                            
                            @media (max-width: 520px) {
                              .u-row-container {
                                max-width: 100% !important;
                                padding-left: 0px !important;
                                padding-right: 0px !important;
                              }
                              .u-row .u-col {
                                min-width: 320px !important;
                                max-width: 100% !important;
                                display: block !important;
                              }
                              .u-row {
                                width: 100% !important;
                              }
                              .u-col {
                                width: 100% !important;
                              }
                              .u-col > div {
                                margin: 0 auto;
                              }
                            }
                            body {
                              margin: 0;
                              padding: 0;
                            }
                            
                            table,
                            tr,
                            td {
                              vertical-align: top;
                              border-collapse: collapse;
                            }
                            
                            p {
                              margin: 0;
                            }
                            
                            .ie-container table,
                            .mso-container table {
                              table-layout: fixed;
                            }
                            
                            * {
                              line-height: inherit;
                            }
                            
                            a[x-apple-data-detectors='true'] {
                              color: inherit !important;
                              text-decoration: none !important;
                            }
                            
                            table, td { color: #000000; } #u_body a { color: #0000ee; text-decoration: underline; }
                                </style>
                              
                              
                            
                            </head>
                            
                            <body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #f0f0e4;color: #000000">
                              <!--[if IE]><div class="ie-container"><![endif]-->
                              <!--[if mso]><div class="mso-container"><![endif]-->
                              <table id="u_body" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #f0f0e4;width:100%" cellpadding="0" cellspacing="0">
                              <tbody>
                              <tr style="vertical-align: top">
                                <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top">
                                <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #f0f0e4;"><![endif]-->
                                
                            
                            <div class="u-row-container" style="padding: 0px;background-color: transparent">
                              <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 500px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                                <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                                  <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:500px;"><tr style="background-color: transparent;"><![endif]-->
                                  
                            <!--[if (mso)|(IE)]><td align="center" width="500" style="width: 500px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
                            <div class="u-col u-col-100" style="max-width: 320px;min-width: 500px;display: table-cell;vertical-align: top;">
                              <div style="height: 100%;width: 100% !important;">
                              <!--[if (!mso)&(!IE)]><!--><div style="box-sizing: border-box; height: 100%; padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->
                              
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div>
                                <style type="text/css">
                            
                            
                            @font-face {
                                font-family: GT Eesti;
                                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Regular-2.woff');
                            }
                            
                            @font-face {
                                font-family: GT Eesti UltraBold;
                                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Ultra-Bold.woff2');
                            }
                            
                            @font-face {
                                font-family: GT EestineLight;
                                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Light-2.woff2');
                            }
                            
                            @font-face {
                                font-family: GT EestineMedium;
                                src: url('https://localfyi-public.s3.amazonaws.com/GT+Eesti/GT-Eesti-Display-Medium-2.woff');
                            }
                            
                            *{
                              font-family: GT Eesti !important;
                            }
                            
                            
                            h1, h2, h3, h4, h5{
                              font-family: GT EestineMedium !important;
                            }
                            
                            </style>
                            
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                              <tr>
                                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                                  
                                  <img align="center" border="0" src="data:image/png;base64,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" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 19%;max-width: 91.2px;" width="91.2"/>
                                  
                                </td>
                              </tr>
                            </table>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <h1 style="margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-size: 22px; ">Confirm Your Email Address</h1>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div style="line-height: 140%; text-align: center; word-wrap: break-word;">
                                <p style="line-height: 140%;">Tap the button below to confirm your email address. If you didn't create an account with Localfyi, you can safely delete this email.</p>
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:13px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <!--[if mso]><style>.v-button {background: transparent !important;}</style><![endif]-->
                            <div align="center">
                              <!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="http:localhost//test" style="height:37px; v-text-anchor:middle; width:190px;" arcsize="46%"  stroke="f" fillcolor="#23d965"><w:anchorlock/><center style="color:#FFFFFF;font-family:arial,helvetica,sans-serif;"><![endif]-->  
                                <a href='${$uri}' target="_blank" class="v-button" style="box-sizing: border-box;display: inline-block;font-family:arial,helvetica,sans-serif;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #FFFFFF; background-color: #23d965; border-radius: 17px;-webkit-border-radius: 17px; -moz-border-radius: 17px; width:40%; max-width:100%; overflow-wrap: break-word; word-break: break-word; word-wrap:break-word; mso-border-alt: none;font-size: 14px;">
                                  <span style="display:block;padding:10px 20px;line-height:120%;">Confirm</span>
                                </a>
                              <!--[if mso]></center></v:roundrect><![endif]-->
                            </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div style="line-height: 140%; text-align: center; word-wrap: break-word;">
                                <p style="line-height: 140%;">If that doesn't work, copy and paste the following link in your browser:</p>
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div>
                                <div style='text-align:center'>
                              <a href='${$uri}'>${$uri}</a>
                            </div>
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:16px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px solid #BBBBBB;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                                <tbody>
                                  <tr style="vertical-align: top">
                                    <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                                      <span>&#160;</span>
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                              <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                              </div>
                            </div>
                            <!--[if (mso)|(IE)]></td><![endif]-->
                                  <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                                </div>
                              </div>
                            </div>
                            
                            
                            
                            <div class="u-row-container" style="padding: 0px;background-color: transparent">
                              <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 500px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                                <div style="border-collapse: collapse;display: table;width: 100%;height: 100%;background-color: transparent;">
                                  <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:500px;"><tr style="background-color: transparent;"><![endif]-->
                                  
                            <!--[if (mso)|(IE)]><td align="center" width="500" style="background-color: #000000;width: 500px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                            <div class="u-col u-col-100" style="max-width: 320px;min-width: 500px;display: table-cell;vertical-align: top;">
                              <div style="background-color: #000000;height: 100%;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;">
                              <!--[if (!mso)&(!IE)]><!--><div style="box-sizing: border-box; height: 100%; padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->
                              
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:34px 23px 13px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                              <tr>
                                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                                  
                                  <img align="center" border="0" src="https://localfyi-public.s3.amazonaws.com/logo2.PNG" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 21%;max-width: 95.34px;" width="95.34"/>
                                  
                                </td>
                              </tr>
                            </table>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:6px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div>
                                <div class="col-md-12" style="text-align: center;"> <svg width="150" height="24" viewBox="0 0 150 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_417_33)"><path d="M11.3147 8.87472H8.625V12.461H11.3147V23.2198H15.7976V12.461H19.0252L19.3839 8.87472H15.7976V7.35054C15.7976 6.54363 15.9769 6.185 16.7838 6.185H19.3839V1.70215H15.9769C12.7492 1.70215 11.3147 3.13666 11.3147 5.82638V8.87472Z" fill="white"></path></g><g clip-path="url(#clip1_417_33)"><path d="M50.3232 4.53758C52.9531 4.53758 53.2819 4.53758 54.3503 4.61977C57.0624 4.70195 58.2953 6.01692 58.3774 8.64686C58.4596 9.7153 58.4596 9.96184 58.4596 12.5918C58.4596 15.2217 58.4596 15.5505 58.3774 16.5367C58.2953 19.1666 56.9803 20.4816 54.3503 20.5638C53.2819 20.646 53.0354 20.646 50.3232 20.646C47.6933 20.646 47.3645 20.646 46.3783 20.5638C43.6662 20.4816 42.4334 19.1666 42.3512 16.5367C42.269 15.4683 42.269 15.2217 42.269 12.5918C42.269 9.96184 42.269 9.63306 42.3512 8.64686C42.4334 6.01692 43.7484 4.70195 46.3783 4.61977C47.3645 4.53758 47.6933 4.53758 50.3232 4.53758ZM50.3232 2.72949C47.6111 2.72949 47.2823 2.72949 46.2961 2.81168C42.6799 2.97605 40.7075 4.9485 40.5431 8.56467C40.4609 9.55091 40.4609 9.87961 40.4609 12.5918C40.4609 15.3039 40.4609 15.6326 40.5431 16.6189C40.7075 20.235 42.6799 22.2075 46.2961 22.3719C47.2823 22.454 47.6111 22.454 50.3232 22.454C53.0354 22.454 53.3641 22.454 54.3503 22.3719C57.9665 22.2075 59.9389 20.235 60.1033 16.6189C60.1855 15.6326 60.1855 15.3039 60.1855 12.5918C60.1855 9.87961 60.1855 9.55091 60.1033 8.56467C59.9389 4.9485 57.9665 2.97605 54.3503 2.81168C53.3641 2.72949 53.0354 2.72949 50.3232 2.72949ZM50.3232 7.49626C47.5289 7.49626 45.2277 9.79745 45.2277 12.5918C45.2277 15.3861 47.5289 17.6873 50.3232 17.6873C53.1175 17.6873 55.4188 15.3861 55.4188 12.5918C55.4188 9.79745 53.1175 7.49626 50.3232 7.49626ZM50.3232 15.8792C48.5151 15.8792 47.0358 14.3999 47.0358 12.5918C47.0358 10.7837 48.5151 9.30434 50.3232 9.30434C52.1313 9.30434 53.6106 10.7837 53.6106 12.5918C53.6106 14.3999 52.1313 15.8792 50.3232 15.8792ZM55.5831 6.18129C54.9256 6.18129 54.4325 6.6744 54.4325 7.33189C54.4325 7.98938 54.9256 8.48249 55.5831 8.48249C56.2406 8.48249 56.7337 7.98938 56.7337 7.33189C56.7337 6.6744 56.2406 6.18129 55.5831 6.18129Z" fill="white"></path></g><g clip-path="url(#clip2_417_33)"><path d="M85.1693 5.98407C85.1693 7.03007 84.3474 7.85192 83.3014 7.85192C82.2554 7.85192 81.4336 7.03007 81.4336 5.98407C81.4336 4.93807 82.2554 4.11621 83.3014 4.11621C84.3474 4.11621 85.1693 4.93807 85.1693 5.98407ZM85.1693 9.34621H81.4336V21.3005H85.1693V9.34621ZM91.1465 9.34621H87.4107V21.3005H91.1465V15.0245C91.1465 11.5129 95.6293 11.2141 95.6293 15.0245V21.3005H99.365V13.7544C99.365 7.85193 92.7155 8.07607 91.1465 10.9899V9.34621Z" fill="white"></path></g><g clip-path="url(#clip3_417_33)"><path d="M131.341 1.70215C125.385 1.70215 120.559 6.52878 120.559 12.4842C120.559 17.0539 123.402 20.9539 127.411 22.525C127.318 21.6699 127.23 20.3643 127.449 19.4335C127.647 18.5912 128.713 14.0762 128.713 14.0762C128.713 14.0762 128.388 13.4318 128.388 12.4758C128.388 10.9763 129.256 9.85608 130.338 9.85608C131.256 9.85608 131.703 10.5468 131.703 11.3765C131.703 12.303 131.113 13.6845 130.81 14.9649C130.557 16.0388 131.349 16.9149 132.406 16.9149C134.323 16.9149 135.797 14.8933 135.797 11.9788C135.797 9.39698 133.943 7.59014 131.294 7.59014C128.228 7.59014 126.426 9.88972 126.426 12.2694C126.426 13.1959 126.784 14.1899 127.23 14.729C127.318 14.8343 127.331 14.9312 127.306 15.0365C127.226 15.3776 127.04 16.1104 127.007 16.2579C126.96 16.4558 126.851 16.4979 126.645 16.4011C125.297 15.7735 124.454 13.8066 124.454 12.223C124.454 8.81996 126.927 5.69908 131.576 5.69908C135.316 5.69908 138.223 8.3651 138.223 11.9282C138.223 15.6429 135.881 18.6333 132.629 18.6333C131.539 18.6333 130.511 18.0647 130.157 17.395C130.157 17.395 129.618 19.4546 129.487 19.96C129.243 20.895 128.586 22.07 128.148 22.786C129.159 23.0977 130.229 23.2662 131.341 23.2662C137.296 23.2662 142.123 18.4396 142.123 12.4842C142.123 6.52878 137.296 1.70215 131.341 1.70215Z" fill="white"></path></g><defs><clipPath id="clip0_417_33"><rect width="10.782" height="21.564" fill="white" transform="translate(8.625 1.70215)"></rect></clipPath><clipPath id="clip1_417_33"><rect width="20.1264" height="20.1264" fill="white" transform="translate(40.1523 2.4209)"></rect></clipPath><clipPath id="clip2_417_33"><rect width="18.6888" height="17.97" fill="white" transform="translate(81.0234 3.5)"></rect></clipPath><clipPath id="clip3_417_33"><rect width="22.2828" height="21.564" fill="white" transform="translate(120.457 1.70215)"></rect></clipPath></defs></svg> </div>
                            
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                              <tbody>
                                <tr>
                                  <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;" align="left">
                                    
                              <div style="font-size: 12px; line-height: 140%; text-align: center; word-wrap: break-word;">
                                <p style="line-height: 140%;"><span style="color: #ffffff; line-height: 14px;">© 2023 Localfyi | Terms of Service | Privacy Policy</span></p>
                              </div>
                            
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                            
                              <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                              </div>
                            </div>
                            <!--[if (mso)|(IE)]></td><![endif]-->
                                  <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                                </div>
                              </div>
                            </div>
                            
                            
                                <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
                                </td>
                              </tr>
                              </tbody>
                              </table>
                              <!--[if mso]></div><![endif]-->
                              <!--[if IE]></div><![endif]-->
                            </body>
                            
                            </html>
                            EOT,
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