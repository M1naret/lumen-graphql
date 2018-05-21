<?php
/**
 * Created by PhpStorm.
 * User: I.Kapelyushny
 * Date: 21.05.2018
 * Time: 17:52
 */

namespace M1naret\GraphQL;

if (class_exists('Illuminate\Routing\Controller')) {
    class BaseController extends \Illuminate\Routing\Controller
    {

    }

} elseif (class_exists('Laravel\Lumen\Routing\Controller')) {
    class BaseController extends \Laravel\Lumen\Routing\Controller
    {

    }

} else {
    class BaseController
    {

    }
}