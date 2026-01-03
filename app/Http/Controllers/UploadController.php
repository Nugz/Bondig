<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class UploadController extends Controller
{
    public function index(): View
    {
        return view('upload.index');
    }
}
