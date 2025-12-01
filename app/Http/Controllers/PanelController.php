<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Display the admin panel.
     */
    public function index(): View
    {
        return view('panel.index');
    }
}
