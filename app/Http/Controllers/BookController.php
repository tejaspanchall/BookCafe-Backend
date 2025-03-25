<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Traits\RedisCacheTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class BookController extends Controller
{
    use RedisCacheTrait;
    const CACHE_DURATION = 3600;

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
                $path = $image->storeAs('books', $filename, 'public');
                $book->image = $filename;
            }

            $book->save();

            $imageUrl = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

            $this->refreshBookCaches();

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
        try {
            $books = Cache::remember('books:all', self::CACHE_DURATION, function() {
                return Book::with('users')->get();
            });
            
            $booksWithUrls = $books->map(function($book) {
                $book->image_url = $book->image 
                    ? url('storage/books/' . urlencode($book->image))
                    : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
                return $book;
            });

            return response()->json([
                'status' => 'success',
                'books' => $booksWithUrls
            ]);
        } catch (\Exception $e) {
            $books = Book::with('users')->get();
            $booksWithUrls = $books->map(function($book) {
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
    }

    public function getLibrary()
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $cacheKey = 'books:library';
        
        $books = $this->getCachedData($cacheKey, self::CACHE_DURATION, function() {
            return Book::all();
        });
        
        $booksWithUrls = $books->map(function($book) {
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
        try {
            $userId = Auth::id();
            $books = Cache::remember("books:user:{$userId}:library", self::CACHE_DURATION, function() {
                return Auth::user()->books()->with('users')->get();
            });
            
            $booksWithUrls = $books->map(function($book) {
                $book->image_url = $book->image 
                    ? url('storage/books/' . urlencode($book->image))
                    : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
                return $book;
            });

            return response()->json([
                'status' => 'success',
                'books' => $booksWithUrls
            ]);
        } catch (\Exception $e) {
            $books = Auth::user()->books()->with('users')->get();
            $booksWithUrls = $books->map(function($book) {
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
    }

    public function addToLibrary(Book $book)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }
            
            if ($user->books()->where('book_id', $book->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Book already in library'
                ], Response::HTTP_BAD_REQUEST);
            }

            \DB::beginTransaction();

            try {
                $user->books()->attach($book->id);

                $this->refreshUserLibraryCache($user->id);

                $this->refreshBookCaches();

                \DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Book added to library',
                    'book' => $book
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add book to library',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeFromLibrary(Book $book)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }
            
            if (!$user->books()->where('book_id', $book->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Book not in library'
                ], Response::HTTP_BAD_REQUEST);
            }

            \DB::beginTransaction();

            try {
                $user->books()->detach($book->id);

                $this->refreshUserLibraryCache($user->id);

                $this->refreshBookCaches();

                \DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Book removed from library'
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove book from library',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteBook(Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        try {
            if ($book->image) {
                Storage::disk('public')->delete('books/' . $book->image);
            }

            $users = $book->users()->get();
            
            $book->delete();

            $this->refreshBookCaches();

            foreach ($users as $user) {
                $this->refreshUserLibraryCache($user->id);
            }

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
                if ($book->image) {
                    Storage::disk('public')->delete('books/' . $book->image);
                }
                
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('books', $filename, 'public');
                $book->image = $filename;
            }

            $book->save();

            $this->refreshBookCaches();

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
        try {
            $query = $request->input('query', '');
            if (empty($query)) {
                return response()->json([
                    'status' => 'success',
                    'books' => []
                ]);
            }

            $books = Cache::remember("books:search:{$query}", self::CACHE_DURATION, function() use ($query) {
                return Book::where('title', 'like', "%{$query}%")
                    ->orWhere('author', 'like', "%{$query}%")
                    ->orWhere('isbn', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%")
                    ->with('users')
                    ->get();
            });
            
            $booksWithUrls = $books->map(function($book) {
                $book->image_url = $book->image 
                    ? url('storage/books/' . urlencode($book->image))
                    : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
                return $book;
            });

            return response()->json([
                'status' => 'success',
                'books' => $booksWithUrls
            ]);
        } catch (\Exception $e) {
            $books = Book::where('title', 'like', "%{$query}%")
                ->orWhere('author', 'like', "%{$query}%")
                ->orWhere('isbn', 'like', "%{$query}%")
                ->orWhere('category', 'like', "%{$query}%")
                ->with('users')
                ->get();
            
            $booksWithUrls = $books->map(function($book) {
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
    }

    protected function refreshBookCaches()
    {
        try {
            $this->clearBookCaches();

            $books = Book::with('users')->get();
            Cache::put('books:all', $books, self::CACHE_DURATION);

            Cache::put('books:library', $books, self::CACHE_DURATION);
        } catch (\Exception $e) {
        }
    }

    protected function refreshUserLibraryCache($userId)
    {
        try {
            $user = User::find($userId);
            if ($user) {
                $books = $user->books()->with('users')->get();
                Cache::put("books:user:{$userId}:library", $books, self::CACHE_DURATION);
            }
        } catch (\Exception $e) {
        }
    }

    protected function clearBookCaches()
    {
        try {
            Cache::forget('books:all');
            Cache::forget('books:library');

            $users = User::all();
            foreach ($users as $user) {
                Cache::forget("books:user:{$user->id}:library");
            }
        } catch (\Exception $e) {
        }
    }
} 