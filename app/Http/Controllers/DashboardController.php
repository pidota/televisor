<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();
        if (! in_array($filter, ['online', 'offline', 'alerts', ''], true)) {
            $filter = '';
        }

        $autoRefresh = $request->string('refresh')->toString() !== '0';

        return view('dashboard.index', [
            'summary' => $this->dashboard->summary(),
            'screens' => $this->dashboard->monitoredScreens($filter !== '' ? $filter : null),
            'alertFeed' => $this->dashboard->alertFeed(),
            'filters' => [
                'filter' => $filter !== '' ? $filter : 'all',
            ],
            'autoRefresh' => $autoRefresh,
        ]);
    }
}
