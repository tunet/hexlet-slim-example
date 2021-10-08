<?php

namespace App;

class Validator
{
    public function validate(array $user): array
    {
        $errors = [];

        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
        }

        if (empty($user['nickname'])) {
            $errors['nickname'] = "Can't be blank";
        }

        return $errors;
    }
}
