<?php

namespace App\Core\Http;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;

abstract class BaseController extends Controller
{
    use ApiResponse;
}