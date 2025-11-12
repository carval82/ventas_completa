<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Mostrar la página "Acerca de"
     */
    public function index()
    {
        return view('about.index');
    }
}
