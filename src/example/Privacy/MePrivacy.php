<?php

use M1naret\GraphQL\Support\Privacy;
use Auth;

class MePrivacy extends Privacy {

    public function validate(array $args)
    {
        return $args['id'] == Auth::id();
    }

}