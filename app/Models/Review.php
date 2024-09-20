<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['review', 'rating'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // EVENTS: Using closure
    // To invalidate cache
    /**
     * 
     *
     * 1.Using mass assignment with update method on the model won't trigger this event handler.
     * Why? Because the update method on the model does not first fetch the model, it just runs the query directly
     * 
     * 2.Using raw SQL query also won't trigger this event handler.
     * 
     * 3.Using database transaction also won't trigger this event handler
     */
    protected static function booted(): void
    {
        static::updated(fn(Review $review) => cache()->forget('book:' . $review->book_id));
        static::deleted(fn(Review $review) => cache()->forget('book:' . $review->book_id));
    }
}
