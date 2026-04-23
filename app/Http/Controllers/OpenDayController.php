<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpenDayController extends Controller
{
    public function index()
    {
        return view('open-day.quest');
    }
}
