<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\EmailApproval;
use App\Models\Admin;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use App\Settings\SocialSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class EmailApprovalsController extends Controller
{
    public $user;

    public GeneralSettings $general;
    public ContactSettings $contact;
    public SocialSettings $social;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
    }

    public function index()
    {
        if (is_null($this->user) || !$this->user->can('settings.edit')) {
            return abort(403, 'You are not allowed to access this page.');
        }

         $emails = DB::table('email_approvals')->get();
        return view('backend.pages.email-approval.index', ['emails' => $emails]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (is_null($this->user) || !$this->user->can('blog.edit')) {
            $message = 'You are not allowed to access this page !';
            return view('errors.403', compact('message'));
        }
        $email = EmailApproval::find($id);
        return view('backend.pages.email-approval.edit', ['data' => $email]);
    }

    public function validatePassword(Request $request)
    {
        $input = $request->all();
         if(Auth::attempt(['email' => $input['email'], 'password' => $input['password']])){ 
          
            return true;
        } else{ 
            return false;
        } 
    }
}
