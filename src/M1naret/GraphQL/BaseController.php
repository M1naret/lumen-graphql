<?php
/**
 * Created by PhpStorm.
 * User: I.Kapelyushny
 * Date: 21.05.2018
 * Time: 17:52
 */

namespace M1naret\GraphQL;

use Illuminate\Routing\Controller as LaravelController;
use \Laravel\Lumen\Routing\Controller as LumenController;

if (class_exists(LaravelController::class)) {
    class BaseController extends LaravelController
    {

    }

} elseif (class_exists(LumenController::class)) {
    class BaseController extends LumenController
    {

    }

} else {
    class BaseController
    {

    }
}