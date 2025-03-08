<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{

    public function add(Request $request)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'isbn' => 'required|string|max:20|unique:books',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255'
        ]);

        $book = Book::create($validatedData);

        return response()->json([
            'status' => 'success',
            'book' => $book
        ], Response::HTTP_CREATED);
    }

    public function getBooks()
    {
        $books = Book::all();
        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    public function getLibrary()
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $books = Book::all();
        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    public function myLibrary()
    {
        $books = Auth::user()->books;
        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    public function addToLibrary(Book $book)
    {
        $user = Auth::user();
        
        if ($user->books()->where('book_id', $book->id)->exists()) {
            return response()->json([
                'error' => 'Book already in library'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->books()->attach($book->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Book added to library'
        ]);
    }

    public function removeFromLibrary(Book $book)
    {
        $user = Auth::user();
        
        if (!$user->books()->where('book_id', $book->id)->exists()) {
            return response()->json([
                'error' => 'Book not in library'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->books()->detach($book->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Book removed from library'
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->input('query');

        $books = Book::where('title', 'ILIKE', "%{$query}%")
            ->orWhere('author', 'ILIKE', "%{$query}%")
            ->orWhere('isbn', 'ILIKE', "%{$query}%")
            ->orWhere('description', 'ILIKE', "%{$query}%")
            ->get();

        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }
}
