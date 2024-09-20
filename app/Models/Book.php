<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scope a query to only find Book Title.
     */
    public function scopeTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', 'LIKE', '%' . $title . '%');
    }

    /**
     * Scope a query 
     */
    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withCount([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
            // NOTE: fn(Builder $q) => $this->dateRangeFilter($q, $from, $to) -> This is arrow function
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvg([
            'reviews' => fn(Builder $q) => $this->dateRangeFilter($q, $from, $to)
            // fn(Builder $q) => $this->dateRangeFilter($q, $from, $to) -> This is arrow function
        ], 'rating');
    }

    /**
     * Scope a query to include Popular books with Highest Total Reviews.
     * Sort the book by the amount of reviews.
     */
    public function scopePopular(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withReviewsCount()
            ->orderBy('reviews_count', 'desc');
    }

    /**
     * Scope a query to include Highest Average Rated book.
     * Sort the book by the reviews average rating
     */
    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder|QueryBuilder
    {
        return $query->withAvgRating()->orderBy('reviews_avg_rating', 'desc');
    }

    public function scopeMinReviews(Builder $query, int $minReviews): Builder|QueryBuilder
    {
        // When working with aggregate function(withCount,withAvg), we have to use having()
        return $query->having('reviews_count', '>=', $minReviews);
    }

    private function dateRangeFilter(Builder $query, $from = null, $to = null)
    {
        // If $from IS SET and $to is NOT
        if ($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif (!$from && $to) { //If $from is NOT SET and $to IS SET
            $query->where('created_at', '<=', $to);
        } elseif ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }

    // NOTE: scope for Popular Last Month
    public function scopePopularLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonth(), now())
            ->highestRated(now()->subMonth(), now())
            ->minReviews(2);
    }

    // NOTE: scope for Popular Last 6 Months
    public function scopePopularLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->popular(now()->subMonths(6), now())
            ->highestRated(now()->subMonths(6), now())
            ->minReviews(5);
    }

    // NOTE: scope for Highest Rated Last Month
    public function scopeHighestRatedLastMonth(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonth(), now())
            ->popular(now()->subMonth(), now())
            ->minReviews(2);
    }

    // NOTE: scope for Highest Rated Last 6 Months
    public function scopeHighestRatedLast6Months(Builder $query): Builder|QueryBuilder
    {
        return $query->highestRated(now()->subMonths(6), now())
            ->popular(now()->subMonths(6), now())
            ->minReviews(5);
    }

    protected static function booted(): void
    {
        static::updated(fn(Book $book) => cache()->forget('book:' . $book->book_id));
        static::deleted(fn(Book $book) => cache()->forget('book:' . $book->book_id));
    }
}
