<?php

declare(strict_types=1);

namespace Koravik\Platform\Resilience;

final class FormErrors
{
    public static function required(array $input, array $fields): array
    {
        $errors=[];
        foreach($fields as $name=>$label) {
            if(trim((string)($input[$name]??''))==='') $errors[$name]=$label.' is required.';
        }
        return $errors;
    }

    public static function summary(array $errors): string
    {
        if($errors===[]) return '';
        $items='';
        foreach($errors as $field=>$message) {
            $items.='<li><a href="#'.self::e((string)$field).'">'.self::e((string)$message).'</a></li>';
        }
        return '<section class="notice error form-error-summary" role="alert" aria-labelledby="form-errors-title" tabindex="-1"><h2 id="form-errors-title">Please correct the following</h2><ul>'.$items.'</ul></section>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }
}
