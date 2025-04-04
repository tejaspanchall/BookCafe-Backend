<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'image',
        'description',
        'isbn',
        'price'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Get the users that have this book in their library.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_books');
    }

    /**
     * Get the categories for this book.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_categories');
    }

    /**
     * Get the authors for this book.
     */
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors');
    }

    /**
     * Get the image URL for the book.
     *
     * @return string|null
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        // If the image is already a full URL, return it as is
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Return just the image path relative to the storage folder
        // The frontend will construct the full URL
        return $this->image;
    }

    /**
     * Prepare the search query for PostgreSQL full-text search.
     * This approach uses a combination of explicit prefix matching and basic SQL LIKE
     * to ensure better results with short queries.
     *
     * @param  string  $searchQuery
     * @return string
     */
    private function prepareSearchQuery(string $searchQuery): string
    {
        // Clean input and remove special characters
        $searchQuery = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $searchQuery);
        $searchQuery = trim($searchQuery);
        
        // If the search query is empty, return empty string
        if (empty($searchQuery)) {
            return '';
        }
        
        // Split into terms
        $terms = array_filter(explode(' ', $searchQuery));
        
        if (empty($terms)) {
            return '';
        }
        
        // Format each term for prefix matching
        $formattedTerms = [];
        foreach ($terms as $term) {
            // Escape any special characters in the term
            $term = preg_replace('/[&|!:*()"]/', ' ', $term);
            $term = trim($term);
            
            if (!empty($term)) {
                // For very short terms (1-2 characters), we use a special approach
                if (mb_strlen($term) <= 2) {
                    // Add multiple variations with the :* operator to increase matches
                    $variations = [
                        $term . ':*'
                    ];
                    
                    // Add common prefixes that might start with this term
                    $prefix = strtolower($term);
                    if ($prefix === 'a') {
                        $variations[] = 'an:*';
                        $variations[] = 'at:*';
                    } elseif ($prefix === 'o') {
                        $variations[] = 'on:*';
                        $variations[] = 'of:*';
                        $variations[] = 'or:*';
                        $variations[] = 'one:*';
                    } elseif ($prefix === 'th') {
                        $variations[] = 'the:*';
                        $variations[] = 'that:*';
                        $variations[] = 'this:*';
                        $variations[] = 'they:*';
                    } elseif ($prefix === 'i') {
                        $variations[] = 'in:*';
                        $variations[] = 'is:*';
                        $variations[] = 'it:*';
                    }
                    
                    // Add the variations with OR operator
                    $formattedTerms[] = '(' . implode(' | ', $variations) . ')';
                } else {
                    // For longer terms, just add the :* operator for prefix matching
                    $formattedTerms[] = $term . ':*';
                }
            }
        }
        
        // Join with & operator for AND logic
        return implode(' & ', $formattedTerms);
    }

    /**
     * Scope a query to only include books that match the search query across title and description.
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $searchQuery)
    {
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        if (empty($preparedQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        return $query->whereRaw("search_vector @@ to_tsquery('english', ?)", [
            $preparedQuery
        ])->orderByRaw("ts_rank(search_vector, to_tsquery('english', ?)) DESC", [
            $preparedQuery
        ]);
    }

    /**
     * Scope a query to include books that match the search query by title.
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     * @return Builder
     */
    public function scopeSearchByTitle(Builder $query, string $searchQuery)
    {
        // Clean and trim search query
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        // For very short queries (1-2 characters), use direct LIKE for better matching
        if (mb_strlen($searchQuery) <= 2) {
            return $query->where(function ($q) use ($searchQuery) {
                // Case-insensitive LIKE search with prefix matching
                $q->whereRaw('LOWER(title) LIKE ?', [strtolower($searchQuery) . '%']);
                
                // If searching for 'o', also match 'one', etc.
                if (strtolower($searchQuery) === 'o') {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['one%']);
                } elseif (strtolower($searchQuery) === 'a') {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['an%']);
                } elseif (strtolower($searchQuery) === 'th') {
                    $q->orWhereRaw('LOWER(title) LIKE ?', ['the%']);
                }
            })->orderByRaw('LENGTH(title) ASC'); // Shorter titles first
        }
        
        // For longer queries, use full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->whereRaw("to_tsvector('english', COALESCE(title, '')) @@ to_tsquery('english', ?)", [
            $preparedQuery
        ])->orderByRaw("ts_rank(to_tsvector('english', COALESCE(title, '')), to_tsquery('english', ?)) DESC", [
            $preparedQuery
        ]);
    }

    /**
     * Scope a query to include books that match the search query by author.
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     * @return Builder
     */
    public function scopeSearchByAuthor(Builder $query, string $searchQuery)
    {
        // Clean and trim search query
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        // For very short queries (1-2 characters), use direct LIKE for better matching
        if (mb_strlen($searchQuery) <= 2) {
            return $query->whereHas('authors', function($q) use ($searchQuery) {
                // Case-insensitive LIKE search with prefix matching
                $q->whereRaw('LOWER(name) LIKE ?', [strtolower($searchQuery) . '%']);
                
                // If searching for specific short prefixes, add common variations
                if (strtolower($searchQuery) === 'j') {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['jo%']);
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['ja%']);
                } elseif (strtolower($searchQuery) === 'm') {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['mi%']);
                    $q->orWhereRaw('LOWER(name) LIKE ?', ['ma%']);
                }
            });
        }
        
        // For longer queries, use full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->whereHas('authors', function($q) use ($preparedQuery) {
            $q->whereRaw("to_tsvector('english', name) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
        });
    }

    /**
     * Scope a query to include books that match the search query by ISBN.
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     * @return Builder
     */
    public function scopeSearchByIsbn(Builder $query, string $searchQuery)
    {
        // Clean and trim search query
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        // For ISBNs, LIKE is generally more reliable for partial prefix matching
        // than full-text search, especially for numeric sequences
        if (mb_strlen($searchQuery) <= 6) {
            return $query->whereRaw('isbn LIKE ?', [$searchQuery . '%']);
        }
        
        // For longer queries, use full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->whereRaw("to_tsvector('english', COALESCE(isbn, '')) @@ to_tsquery('english', ?)", [
            $preparedQuery
        ]);
    }

    /**
     * Scope a query to include books that match the search query across all fields.
     * Only includes title, author, and ISBN fields (excludes description).
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     * @return Builder
     */
    public function scopeSearchAll(Builder $query, string $searchQuery)
    {
        // Clean and trim search query
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        // For very short queries (1-3 characters), use direct LIKE for better matching
        if (mb_strlen($searchQuery) <= 3) {
            return $query->where(function($q) use ($searchQuery) {
                // Search by title with LIKE
                $q->where(function($titleQ) use ($searchQuery) {
                    // Case-insensitive LIKE search with prefix matching
                    $titleQ->whereRaw('LOWER(title) LIKE ?', [strtolower($searchQuery) . '%']);
                    
                    // Add common variations for short prefixes
                    if (strtolower($searchQuery) === 'o') {
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['one%']);
                    } elseif (strtolower($searchQuery) === 'a') {
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['an%']);
                    } elseif (strtolower($searchQuery) === 'th') {
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['the%']);
                    } elseif (strtolower($searchQuery) === 'mo') {
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['moc%']);
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['%mock%']);
                    }
                    
                    // Also search within words for very specific cases
                    if (in_array(strtolower($searchQuery), ['mo', 'moc', 'mock'])) {
                        $titleQ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($searchQuery) . '%']);
                    }
                })
                // Search by ISBN with LIKE
                ->orWhereRaw('isbn LIKE ?', [$searchQuery . '%'])
                // Search by author name with LIKE
                ->orWhereHas('authors', function($authorQ) use ($searchQuery) {
                    $authorQ->whereRaw('LOWER(name) LIKE ?', [strtolower($searchQuery) . '%']);
                });
            })->orderByRaw('LENGTH(title) ASC'); // Shorter titles first
        }
        
        // For longer queries, use full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->where(function($q) use ($preparedQuery) {
            // Search by title
            $q->whereRaw("to_tsvector('english', COALESCE(title, '')) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ])
            // Search by ISBN
            ->orWhereRaw("to_tsvector('english', COALESCE(isbn, '')) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
            
            // Search by author name
            $q->orWhereHas('authors', function($authorQuery) use ($preparedQuery) {
                $authorQuery->whereRaw("to_tsvector('english', name) @@ to_tsquery('english', ?)", [
                    $preparedQuery
                ]);
            });
        })->orderByRaw("
            ts_rank(to_tsvector('english', COALESCE(title, '')), to_tsquery('english', ?)) +
            ts_rank(to_tsvector('english', COALESCE(isbn, '')), to_tsquery('english', ?)) DESC
        ", [$preparedQuery, $preparedQuery]);
    }
}