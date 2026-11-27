<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'name_hi', 'name_mr', 'kind', 'region', 'starts_on', 'ends_on', 'weights', 'rule', 'photo_slot'])]
class Occasion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'weights' => 'array',
        ];
    }
}
