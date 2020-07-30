<?php

namespace App\Http\Controllers;

use App\SingleImageExample;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function welcome()
    {
        $singleImageExamples = SingleImageExample::all();

        return view('welcome', compact('singleImageExamples'));
    }
}
