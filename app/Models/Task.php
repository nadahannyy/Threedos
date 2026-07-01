<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'description',
        'is_completed',
        'due_date',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'datetime',
    ];

    /**
     * Get the category that owns the task.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
