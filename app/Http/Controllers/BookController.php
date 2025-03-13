<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    // Add a new book (teachers only)
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $bookData = [
            'title' => $validatedData['title'],
            'author' => $validatedData['author'],
            'isbn' => $validatedData['isbn'],
            'description' => $validatedData['description'] ?? null,
            'image' => null
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('books', $imageName, 'public');
            $bookData['image'] = $imageName;
        }

        $book = Book::create($bookData);

        return response()->json([
            'status' => 'success',
            'book' => $book
        ], Response::HTTP_CREATED);
    }

    // Get all books
    public function getBooks()
    {
        $books = Book::all();
        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    // Get books in library (for teachers)
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

    // Get user's library
    public function myLibrary()
    {
        $books = Auth::user()->books;
        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    // Add book to user's library
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

    // Remove book from user's library
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

    // Delete book (teachers only)
    public function deleteBook(Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $book->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Book deleted successfully'
        ]);
    }

    // Update book (teachers only)
    public function updateBook(Request $request, Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'author' => 'sometimes|required|string|max:100',
                'isbn' => 'sometimes|required|string|max:20|unique:books,isbn,' . $book->id,
                'description' => 'sometimes|nullable|string',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $updateData = collect($validatedData)->except('image')->toArray();

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($book->image) {
                    Storage::disk('public')->delete('books/' . $book->image);
                }
                
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('books', $imageName, 'public');
                $updateData['image'] = $imageName;
            }

            $book->update($updateData);
            $book->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Book updated successfully',
                'book' => $book
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update book',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    // Search books
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

    // Delete a book
    public function delete(Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Delete the image file if it exists
            if ($book->image) {
                Storage::delete('public/books/' . $book->image);
            }

            $book->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Book deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete book',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
} 