<?php

namespace App\Models;

use App\Enums\SystemParameterType;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SystemParameter extends BaseModel
{
    protected $fillable = [
        'key', 'name', 'category', 'type', 'value', 'default_value',
        'options', 'description', 'is_active', 'is_system',
    ];

    protected $casts = [
        'type' => SystemParameterType::class,
        'options' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $parameter): void {
            if (!$parameter->is_system) {
                return;
            }

            foreach (['key', 'name', 'category', 'type', 'default_value', 'is_system'] as $immutable) {
                if ($parameter->isDirty($immutable)) {
                    $parameter->setAttribute($immutable, $parameter->getOriginal($immutable));
                }
            }
        });

        static::updated(function (self $parameter): void {
            if (!$parameter->wasChanged(['value', 'is_active'])) {
                return;
            }

            SystemParameterHistory::create([
                'system_parameter_id' => $parameter->id,
                'user_id' => Auth::id(),
                'previous_value' => $parameter->getOriginal('value'),
                'new_value' => $parameter->value,
                'previous_active' => (bool) $parameter->getOriginal('is_active'),
                'new_active' => $parameter->is_active,
            ]);
        });
    }

    public function history()
    {
        return $this->hasMany(SystemParameterHistory::class)->latest();
    }

    public function typedValue(): mixed
    {
        if ($this->type === SystemParameterType::BOOLEAN) {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->type === SystemParameterType::INTEGER) {
            return (int) $this->value;
        }

        if ($this->type === SystemParameterType::DECIMAL) {
            return (float) $this->value;
        }

        return $this->value;
    }

    public function explanation(): string
    {
        return 'Ao ativar este parâmetro, o sistema irá aplicar a regra **'.$this->name.'** nas rotinas correspondentes. '
            .$this->description.' Alterações feitas aqui ficam registradas e não modificam dados históricos já consolidados.';
    }

    public static function read(string $key, mixed $fallback = null): mixed
    {
        try {
            $parameter = static::query()->where('key', $key)->where('is_active', true)->first();
        } catch (Throwable) {
            return $fallback;
        }

        return $parameter?->typedValue() ?? $fallback;
    }
}
