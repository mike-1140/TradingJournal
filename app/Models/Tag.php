<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $connection = 'mysql'; // Zentrale Laravel-DB
    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope für aktive Tags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope nach Kategorie
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Berechne Kontrast-Farbe (weiß oder schwarz) für Text auf Badge
     */
    public function getContrastColor()
    {
        $hex = str_replace('#', '', $this->color);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

    /**
     * Accessor für einfacheren Zugriff in Views
     */
    public function getContrastColorAttribute()
    {
        return $this->getContrastColor();
    }
}
