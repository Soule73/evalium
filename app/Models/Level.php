<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Événements du modèle pour gérer le cache
     */
    protected static function booted(): void
    {
        // Invalider le cache des groupes quand un niveau change
        static::created(fn() => Cache::forget('groups_active_with_levels'));
        static::updated(fn() => Cache::forget('groups_active_with_levels'));
        static::deleted(fn() => Cache::forget('groups_active_with_levels'));
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function activeGroups(): HasMany
    {
        return $this->groups()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public static function options(): array
    {
        return self::active()->ordered()->pluck('name', 'id')->toArray();
    }
}
