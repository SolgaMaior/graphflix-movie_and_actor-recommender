<?php

namespace App\Http\Controllers;

final class LandingController extends Controller
{
    public function index()
    {
        return view('landing');
    }
}
