<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;

class DashboardController extends Controller
{
    public function index()
    {
        $campaignCount = Campaign::count();
        // Here we could add more statistics to pass to the dashboard
        return view('admin.dashboard', compact('campaignCount'));
    }
}
