<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Models\ContactMessage;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'offersCount' => JobOffer::count(),
            'appsUnread'  => JobApplication::where('is_read', false)->count(),
            'msgsUnread'  => ContactMessage::where('is_read', false)->count(),
        ]);
    }
}
