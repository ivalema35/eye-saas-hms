<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Platform\TimezoneService;
use Illuminate\View\View;

class TimezoneMasterController extends Controller
{
    public function index(): View
    {
        return view('superadmin.timezones.index', [
            'groupedTimezones' => TimezoneService::groupedTimezones(),
            'totalCount'       => count(timezone_identifiers_list()),
        ]);
    }
}
