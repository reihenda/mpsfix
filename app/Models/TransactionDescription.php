<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'is_active',
        'category'
    ];

    /**
     * Scope a query to only include active descriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope a query to only include descriptions in a specific category.
     */
    public function scopeInCategory($query, $category)
    {
        if ($category == 'both') {
            return $query->whereIn('category', ['kas', 'bank', 'both']);
        }
        
        return $query->whereIn('category', [$category, 'both']);
    }
}
