<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{

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