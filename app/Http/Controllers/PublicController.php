<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function index()
    {
        return view('home');
    }
}
