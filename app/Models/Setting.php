<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_editable',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function (Setting $setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get properly cast value based on the type attribute.
     */
    public function getValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'integer', 'int' => (int) $this->value,
            'float', 'double' => (float) $this->value,
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set value attribute with proper stringification based on type.
     */
    public function setValue(mixed $value): self
    {
        if ($value === null) {
            $this->value = null;
        } elseif (is_array($value) || is_object($value)) {
            $this->value = json_encode($value);
            $this->type = 'json';
        } elseif (is_bool($value)) {
            $this->value = $value ? '1' : '0';
            $this->type = 'boolean';
        } elseif (is_int($value)) {
            $this->value = (string) $value;
            $this->type = 'integer';
        } else {
            $this->value = (string) $value;
        }

        return $this;
    }

    /**
     * Get setting value by key statically with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->getValue() : $default;
        });
    }

    /**
     * Invalidate cache for a specific key.
     */
    public static function forgetCache(string $key): void
    {
        Cache::forget("setting.{$key}");
    }

    /**
     * Set setting value by key statically.
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $description = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);

        $setting->setValue($value);

        if ($group !== null) {
            $setting->group = $group;
        }
        if ($description !== null) {
            $setting->description = $description;
        }
        if (auth()->check()) {
            if (! $setting->exists) {
                $setting->created_by = auth()->id();
            }
            $setting->updated_by = auth()->id();
        }

        $setting->save();
        self::forgetCache($key);

        return $setting;
    }
}
