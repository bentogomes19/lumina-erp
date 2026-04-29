<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    use HasFactory;

    /**
     * Preenche a coluna UUID informada quando ela ainda não possui valor.
     *
     * @param  string  $column  Nome da coluna que armazena o UUID.
     */
    protected function fillUuidIfMissing(string $column = 'uuid'): void
    {
        if (empty($this->{$column})) {
            $this->{$column} = (string) Str::uuid();
        }
    }
}
