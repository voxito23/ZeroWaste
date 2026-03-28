<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;

class DashboardController extends Controller
{
    public function index()
    {
        $campaignCount = Campaign::count();
        // Se pueden agregar más estadísticas para el panel
        return view('admin.dashboard', compact('campaignCount'));
    }
}
