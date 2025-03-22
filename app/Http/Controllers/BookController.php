<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    private $cacheExpiration = 3600; // 1 hour
    private $searchCacheExpiration = 300; // 5 minutes

    private function getCacheKey($type, $identifier = null) {
        return 'books:' . $type . ($identifier ? ':' . $identifier : '');
    }

    private function clearBookCache() {
        Cache::forget($this->getCacheKey('all'));
        Cache::forget($this->getCacheKey('teacher_library'));
        
        // Clear search cache
        $searchPattern = 'books:search:*';
        $keys = Cache::get($searchPattern);
        if ($keys) {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
        
        // Clear user libraries cache
        $userLibraryPattern = 'books:user_library:*';
        $keys = Cache::get($userLibraryPattern);
        if ($keys) {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0'
        ]);

        $bookData = [
            'title' => $validatedData['title'],
            'author' => $validatedData['author'],
            'isbn' => $validatedData['isbn'],
            'description' => $validatedData['description'] ?? null,
            'category' => $validatedData['category'] ?? null,
            'price' => $validatedData['price'] ?? null,
            'image' => null
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('books', $imageName, 'public');
            $bookData['image'] = $imageName;
        }

        $book = Book::create($bookData);
        
        $this->clearBookCache();

        return response()->json([
            'status' => 'success',
            'book' => $book
        ], Response::HTTP_CREATED);
    }

    public function getBooks()
    {
        // Clear cache first to ensure we get fresh data
        Cache::forget($this->getCacheKey('all'));
        
        $books = Cache::remember($this->getCacheKey('all'), $this->cacheExpiration, function () {
            return Book::all();
        });

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

        $books = Cache::remember($this->getCacheKey('teacher_library'), $this->cacheExpiration, function () {
            return Book::all();
        });

        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    public function myLibrary()
    {
        $userId = Auth::id();
        $books = Cache::remember($this->getCacheKey('user_library', $userId), $this->cacheExpiration, function () {
            return Auth::user()->books;
        });

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
        
        Cache::forget($this->getCacheKey('user_library', $user->id));

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
        
        Cache::forget($this->getCacheKey('user_library', $user->id));

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
        
        $this->clearBookCache();

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

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'author' => 'sometimes|required|string|max:100',
                'isbn' => 'sometimes|required|string|max:20|unique:books,isbn,' . $book->id,
                'description' => 'sometimes|nullable|string',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'category' => 'sometimes|nullable|string|max:50',
                'price' => 'sometimes|nullable|numeric|min:0'
            ]);

            $updateData = collect($validatedData)->except('image')->toArray();

            if ($request->hasFile('image')) {
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
            
            $this->clearBookCache();

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

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->input('query');
        
        $cacheKey = $this->getCacheKey('search', md5($query));
        $books = Cache::remember($cacheKey, $this->searchCacheExpiration, function () use ($query) {
            return Book::where('title', 'ILIKE', "%{$query}%")
                ->orWhere('author', 'ILIKE', "%{$query}%")
                ->orWhere('isbn', 'ILIKE', "%{$query}%")
                ->orWhere('description', 'ILIKE', "%{$query}%")
                ->orWhere('category', 'ILIKE', "%{$query}%")
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'books' => $books
        ]);
    }

    public function delete(Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        try {
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