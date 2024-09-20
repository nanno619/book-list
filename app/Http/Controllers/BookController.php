<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     * NOTE: This is to display the list of book
     */
    public function index(Request $request)
    {
        // NOTE: Optional title parameter. It will be used for filtering
        $title = $request->input('title');

        $filter = $request->input('filter', '');

        // NOTE: when() - if title is not null or not empty, it will run this function
        // ALT: Not using arrow function
        // $books = Book::when($title, function ($query, $title) {
        //     return $query->title($title);
        // })

        // ALT: using arrow function
        $books = Book::when(
            $title,
            fn($query, $title) => $query->title($title) // NOTE: $query->title(). This is from scopeTitle in Book model.
        );

        // NOTE: match expression
        $books = match ($filter) {
            'popular_last_month' => $books->popularLastMonth(),
            'popular_last_6months' => $books->popularLast6Months(),
            'highest_rated_last_month' => $books->highestRatedLastMonth(),
            'highest_rated_last_6months' => $books->highestRatedLast6Months(),
            default => $books->latest()
        };
        // $books = $books->get();

        // NOTE: how to cache data
        $cacheKey = 'books:' . $filter . ':' . $title;
        $books = cache()->remember($cacheKey, 3600, fn() => $books->get());

        // Return view
        return view('books.index', ['books' => $books]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     * NOTE: Book $book is route model binding
     */
    public function show(int $id)
    {
        $cacheKey = 'book:' . $id;

        $book = cache()->remember(
            $cacheKey,
            3600,
            fn() =>
            Book::with([ // NOTE: lazy eager loading
                'reviews' => fn($query) => $query->latest()
            ])->withAvgRating()->withReviewsCount()->findOrFail($id)
            /**
         *  NOTE: load() is instance method.It's a method on a created object
         *  So, we need to use a static method for a class instead
         *  use with() - that's the way to fetch relations together with the model at the same time
         * 
         * load() is useful if u are fetching relations for a model that is already loaded there
         */
        );

        return view('books.show', ['book' => $book]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
