<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function add(Request $request)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            $book = new Book();
            $book->title = $request->title;
            $book->author = $request->author;
            $book->isbn = $request->isbn;
            $book->description = $request->description;
            $book->category = $request->category;
            $book->price = $request->price;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                // Store in public disk
                $path = $image->storeAs('books', $filename, 'public');
                $book->image = $filename;
            }

            $book->save();

            // Create full URL for the image
            $imageUrl = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

            return response()->json([
                'status' => 'success',
                'book' => $book,
                'image_url' => $imageUrl
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add book',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getBooks()
    {
        $books = Book::all();
        
        // Add image URLs for each book
        $booksWithUrls = $books->map(function($book) {
            // If no image, provide a transparent pixel data URL
            $book->image_url = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
            return $book;
        });

        return response()->json([
            'status' => 'success',
            'books' => $booksWithUrls
        ]);
    }

    public function getLibrary()
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $books = Book::all();
        
        // Add image URLs for each book
        $booksWithUrls = $books->map(function($book) {
            // If no image, provide a transparent pixel data URL
            $book->image_url = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
            return $book;
        });

        return response()->json([
            'status' => 'success',
            'books' => $booksWithUrls
        ]);
    }

    public function myLibrary()
    {
        $userId = Auth::id();
        $books = Auth::user()->books;
        
        // Add image URLs for each book
        $booksWithUrls = $books->map(function($book) {
            // If no image, provide a transparent pixel data URL
            $book->image_url = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
            return $book;
        });

        return response()->json([
            'status' => 'success',
            'books' => $booksWithUrls
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

    public function updateBook(Request $request, Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validate([
            'title' => 'string|max:255',
            'author' => 'string|max:100',
            'isbn' => 'string|max:20|unique:books,isbn,' . $book->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            if ($request->filled('title')) {
                $book->title = $request->title;
            }
            
            if ($request->filled('author')) {
                $book->author = $request->author;
            }
            
            if ($request->filled('isbn')) {
                $book->isbn = $request->isbn;
            }
            
            if ($request->filled('description')) {
                $book->description = $request->description;
            }
            
            if ($request->filled('category')) {
                $book->category = $request->category;
            }
            
            if ($request->filled('price')) {
                $book->price = $request->price;
            }

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($book->image) {
                    Storage::disk('public')->delete('books/' . $book->image);
                }
                
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('books', $filename, 'public');
                $book->image = $filename;
            }

            $book->save();

            // Create full URL for the image
            $imageUrl = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

            return response()->json([
                'status' => 'success',
                'book' => $book,
                'image_url' => $imageUrl,
                'message' => 'Book updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update book',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
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
            ->orWhere('category', 'ILIKE', "%{$query}%")
            ->get();
            
        // Add image URLs for each book
        $booksWithUrls = $books->map(function($book) {
            // If no image, provide a transparent pixel data URL
            $book->image_url = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
            return $book;
        });

        return response()->json([
            'status' => 'success',
            'books' => $booksWithUrls
        ]);
    }

    public function delete(Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        try {
            if ($book->image) {
                Storage::disk('public')->delete('books/' . $book->image);
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