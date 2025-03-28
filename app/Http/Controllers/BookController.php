<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Traits\RedisCacheTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Author;

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
            'authors' => 'required|array',
            'authors.*' => 'required|string|max:100',
            'isbn' => 'required|string|max:20|unique:books,isbn',
            'description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:50',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            $book = new Book();
            $book->title = $request->title;
            $book->isbn = $request->isbn;
            $book->description = $request->description;
            $book->price = $request->price;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('books', $filename, 'public');
                $book->image = $filename;
            }

            $book->save();

            // Handle authors
            if ($request->has('authors') && is_array($request->authors)) {
                $this->syncAuthors($book, $request->authors);
            }

            // Handle categories
            if ($request->has('categories') && is_array($request->categories)) {
                $this->syncCategories($book, $request->categories);
            }

            $imageUrl = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

            $this->refreshBookCaches();

            return response()->json([
                'status' => 'success',
                'book' => $book->load(['categories', 'authors']),
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
            $books = $this->getCachedData('books:all', self::CACHE_DURATION, function() {
                return Book::with(['users', 'categories', 'authors'])->get();
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
            \Log::error('Error fetching books: ' . $e->getMessage());
            $books = Book::with(['users', 'categories', 'authors'])->get();
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
            return Book::with(['categories', 'authors'])->get();
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
            $books = $this->getCachedData("books:user:{$userId}:library", self::CACHE_DURATION, function() {
                return Auth::user()->books()->with(['users', 'categories', 'authors'])->get();
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
            \Log::error('Error fetching user library: ' . $e->getMessage());
            $books = Auth::user()->books()->with(['users', 'categories', 'authors'])->get();
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

    public function update(Request $request, Book $book)
    {
        if (Auth::user()->role !== 'teacher') {
            return response()->json(['error' => 'Unauthorized. Teachers only.'], Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validate([
            'title' => 'string|max:255',
            'authors' => 'nullable|array',
            'authors.*' => 'string|max:100',
            'isbn' => 'string|max:20|unique:books,isbn,' . $book->id,
            'description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:50',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        try {
            if ($request->filled('title')) {
                $book->title = $request->title;
            }
            
            if ($request->filled('isbn')) {
                $book->isbn = $request->isbn;
            }
            
            if ($request->filled('description')) {
                $book->description = $request->description;
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

            // Handle authors
            if ($request->has('authors')) {
                $this->syncAuthors($book, $request->authors);
            }

            // Handle categories
            if ($request->has('categories')) {
                $this->syncCategories($book, $request->categories);
            }

            $this->refreshBookCaches();

            $imageUrl = $book->image 
                ? url('storage/books/' . urlencode($book->image))
                : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";

            return response()->json([
                'status' => 'success',
                'book' => $book->load(['categories', 'authors']),
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
            $type = $request->input('type', 'name'); // Default to name if not provided
            
            if (empty($query)) {
                return response()->json([
                    'status' => 'success',
                    'books' => []
                ]);
            }

            $cacheKey = "books:search:{$type}:{$query}";
            
            $books = $this->getCachedData($cacheKey, self::CACHE_DURATION, function() use ($query, $type) {
                $bookQuery = Book::query()->with(['users', 'categories', 'authors']);
                
                // Apply different search criteria based on type
                switch($type) {
                    case 'author':
                        $bookQuery->whereHas('authors', function($q) use ($query) {
                            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%']);
                        });
                        break;
                    case 'isbn':
                        $bookQuery->whereRaw('LOWER(isbn) LIKE ?', ['%' . strtolower($query) . '%']);
                        break;
                    case 'name':
                    default:
                        // Enhanced search to find books by any word in the title
                        $lowercaseQuery = strtolower($query);
                        $bookQuery->where(function($q) use ($lowercaseQuery) {
                            $q->whereRaw('LOWER(title) LIKE ?', ['%' . $lowercaseQuery . '%'])
                              // Match word at beginning
                              ->orWhereRaw('LOWER(title) LIKE ?', [$lowercaseQuery . ' %'])
                              // Match word in the middle
                              ->orWhereRaw('LOWER(title) LIKE ?', ['% ' . $lowercaseQuery . ' %'])
                              // Match word at the end
                              ->orWhereRaw('LOWER(title) LIKE ?', ['% ' . $lowercaseQuery]);
                        });
                        break;
                }
                
                return $bookQuery->get();
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
            \Log::error('Error searching books: ' . $e->getMessage());
            
            $bookQuery = Book::query()->with(['users', 'categories', 'authors']);
            
            // Apply different search criteria based on type
            switch($type) {
                case 'author':
                    $bookQuery->whereHas('authors', function($q) use ($query) {
                        $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%']);
                    });
                    break;
                case 'isbn':
                    $bookQuery->whereRaw('LOWER(isbn) LIKE ?', ['%' . strtolower($query) . '%']);
                    break;
                case 'name':
                default:
                    // Enhanced search to find books by any word in the title
                    $lowercaseQuery = strtolower($query);
                    $bookQuery->where(function($q) use ($lowercaseQuery) {
                        $q->whereRaw('LOWER(title) LIKE ?', ['%' . $lowercaseQuery . '%'])
                          // Match word at beginning
                          ->orWhereRaw('LOWER(title) LIKE ?', [$lowercaseQuery . ' %'])
                          // Match word in the middle
                          ->orWhereRaw('LOWER(title) LIKE ?', ['% ' . $lowercaseQuery . ' %'])
                          // Match word at the end
                          ->orWhereRaw('LOWER(title) LIKE ?', ['% ' . $lowercaseQuery]);
                    });
                    break;
            }
            
            $books = $bookQuery->get();
            
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

    /**
     * Sync categories for a book
     */
    private function syncCategories(Book $book, array $categoryNames)
    {
        $categoryIds = [];
        
        foreach ($categoryNames as $name) {
            if (empty($name)) continue;
            
            // Find or create the category
            $category = Category::firstOrCreate(['name' => $name]);
            $categoryIds[] = $category->id;
        }
        
        // Sync the categories with the book
        $book->categories()->sync($categoryIds);
        
        // Clear category cache
        Cache::forget('categories:all');
    }

    /**
     * Sync authors for a book
     */
    private function syncAuthors(Book $book, array $authorNames)
    {
        $authorIds = [];
        
        foreach ($authorNames as $name) {
            if (empty($name)) continue;
            
            // Find or create the author
            $author = Author::firstOrCreate(['name' => $name]);
            $authorIds[] = $author->id;
        }
        
        // Sync the authors with the book
        $book->authors()->sync($authorIds);
    }

    protected function refreshBookCaches()
    {
        try {
            $this->clearBookCaches();

            $books = Book::with(['users', 'categories', 'authors'])->get();
            Cache::put('books:all', $books, self::CACHE_DURATION);

            Cache::put('books:library', $books, self::CACHE_DURATION);
        } catch (\Exception $e) {
            // Silently fail if Redis is unavailable
            \Log::error('Error refreshing book caches: ' . $e->getMessage());
        }
    }

    protected function refreshUserLibraryCache($userId)
    {
        try {
            $user = User::find($userId);
            if ($user) {
                $books = $user->books()->with(['users', 'categories', 'authors'])->get();
                Cache::put("books:user:{$userId}:library", $books, self::CACHE_DURATION);
            }
        } catch (\Exception $e) {
            // Silently fail if Redis is unavailable
            \Log::error('Error refreshing user library cache: ' . $e->getMessage());
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
            // Silently fail if Redis is unavailable
            \Log::error('Error clearing book caches: ' . $e->getMessage());
        }
    }
} 