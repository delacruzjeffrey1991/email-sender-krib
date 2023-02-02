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

class TopicController extends Controller
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
        return view('backend.pages.topics.index');
    }
}
