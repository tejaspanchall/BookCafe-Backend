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
     * This approach uses consistent prefix matching with :* suffix for to_tsquery
     * and proper tokenization for multi-word searches.
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
                // Always add the :* operator for prefix matching
                $formattedTerms[] = $term . ':*';
            }
        }
        
        // Join with & operator for AND logic between terms
        return implode(' & ', $formattedTerms);
    }

    /**
     * Get the ILIKE conditions for fuzzy matching.
     * This complements the full-text search with more flexible matching.
     *
     * @param  string  $searchQuery
     * @param  string  $column
     * @return array
     */
    private function getIlikeConditions(string $searchQuery, string $column): array
    {
        // Clean and trim search query
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return [];
        }
        
        $conditions = [];
        
        // Exact match (highest priority)
        $conditions[] = ["LOWER($column) = ?", strtolower($searchQuery)];
        
        // Starts with (high priority)
        $conditions[] = ["LOWER($column) LIKE ?", strtolower($searchQuery) . '%'];
        
        // Contains (medium priority)
        $conditions[] = ["LOWER($column) LIKE ?", '%' . strtolower($searchQuery) . '%'];
        
        // If we have multiple words, create conditions for individual words
        $words = array_filter(explode(' ', $searchQuery));
        if (count($words) > 1) {
            foreach ($words as $word) {
                if (strlen($word) > 2) { // Only consider words longer than 2 chars
                    $conditions[] = ["LOWER($column) LIKE ?", '%' . strtolower($word) . '%'];
                }
            }
        }
        
        return $conditions;
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
        $searchQuery = trim($searchQuery);
        
        if (empty($searchQuery)) {
            return $query->whereRaw('1=0'); // Empty result if query is empty
        }
        
        // Prepare query for full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->where(function($q) use ($preparedQuery, $searchQuery) {
            // First try full-text search
            $q->whereRaw("search_vector @@ to_tsquery('english', ?)", [$preparedQuery]);
            
            // Then add ILIKE conditions for better fuzzy matching
            $ilikeConditions = $this->getIlikeConditions($searchQuery, 'title');
            foreach ($ilikeConditions as [$condition, $value]) {
                $q->orWhereRaw($condition, [$value]);
            }
        })->orderByRaw("
            ts_rank(search_vector, to_tsquery('english', ?)) DESC,
            CASE 
                WHEN LOWER(title) = ? THEN 1
                WHEN LOWER(title) LIKE ? THEN 2
                WHEN LOWER(title) LIKE ? THEN 3
                ELSE 4
            END
        ", [$preparedQuery, strtolower($searchQuery), strtolower($searchQuery) . '%', '%' . strtolower($searchQuery) . '%']);
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
        
        // Prepare query for full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->where(function($q) use ($preparedQuery, $searchQuery) {
            // Full-text search on title
            $q->whereRaw("to_tsvector('english', COALESCE(title, '')) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
            
            // Add ILIKE conditions for better fuzzy matching
            $ilikeConditions = $this->getIlikeConditions($searchQuery, 'title');
            foreach ($ilikeConditions as [$condition, $value]) {
                $q->orWhereRaw($condition, [$value]);
            }
        })->orderByRaw("
            ts_rank(to_tsvector('english', COALESCE(title, '')), to_tsquery('english', ?)) DESC,
            CASE 
                WHEN LOWER(title) = ? THEN 1
                WHEN LOWER(title) LIKE ? THEN 2
                WHEN LOWER(title) LIKE ? THEN 3
                ELSE 4
            END
        ", [$preparedQuery, strtolower($searchQuery), strtolower($searchQuery) . '%', '%' . strtolower($searchQuery) . '%']);
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
        
        // Prepare query for full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->whereHas('authors', function($q) use ($preparedQuery, $searchQuery) {
            // Full-text search on author name
            $q->whereRaw("to_tsvector('english', name) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
            
            // Add ILIKE conditions for better fuzzy matching
            $ilikeConditions = $this->getIlikeConditions($searchQuery, 'name');
            foreach ($ilikeConditions as [$condition, $value]) {
                $q->orWhereRaw($condition, [$value]);
            }
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
        
        // ISBNs are special - they work better with direct LIKE matching
        return $query->where(function($q) use ($searchQuery) {
            // Exact match
            $q->whereRaw('isbn = ?', [$searchQuery]);
            
            // Starts with (common for partial ISBNs)
            $q->orWhereRaw('isbn LIKE ?', [$searchQuery . '%']);
            
            // Contains (for wildcard matching)
            $q->orWhereRaw('isbn LIKE ?', ['%' . $searchQuery . '%']);
            
            // If it looks like a formatted ISBN, try to match without formatting
            if (str_contains($searchQuery, '-')) {
                $plainIsbn = str_replace('-', '', $searchQuery);
                $q->orWhereRaw('isbn = ?', [$plainIsbn]);
                $q->orWhereRaw('isbn LIKE ?', [$plainIsbn . '%']);
                $q->orWhereRaw('isbn LIKE ?', ['%' . $plainIsbn . '%']);
            }
        })->orderByRaw("
            CASE 
                WHEN isbn = ? THEN 1
                WHEN isbn LIKE ? THEN 2
                WHEN isbn LIKE ? THEN 3
                ELSE 4
            END
        ", [$searchQuery, $searchQuery . '%', '%' . $searchQuery . '%']);
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
        
        // Prepare query for full-text search
        $preparedQuery = $this->prepareSearchQuery($searchQuery);
        
        return $query->where(function($q) use ($preparedQuery, $searchQuery) {
            // FULL TEXT SEARCH
            
            // Search by title with full-text
            $q->whereRaw("to_tsvector('english', COALESCE(title, '')) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
            
            // Search by ISBN with full-text
            $q->orWhereRaw("to_tsvector('english', COALESCE(isbn, '')) @@ to_tsquery('english', ?)", [
                $preparedQuery
            ]);
            
            // Search by author name with full-text
            $q->orWhereHas('authors', function($authorQuery) use ($preparedQuery) {
                $authorQuery->whereRaw("to_tsvector('english', name) @@ to_tsquery('english', ?)", [
                    $preparedQuery
                ]);
            });
            
            // FUZZY MATCHING WITH ILIKE
            
            // Title ILIKE conditions
            $titleConditions = $this->getIlikeConditions($searchQuery, 'title');
            foreach ($titleConditions as [$condition, $value]) {
                $q->orWhereRaw($condition, [$value]);
            }
            
            // ISBN ILIKE conditions - simpler since it's more structured
            $q->orWhereRaw('isbn = ?', [$searchQuery]);
            $q->orWhereRaw('isbn LIKE ?', [$searchQuery . '%']);
            
            // Author ILIKE conditions
            $q->orWhereHas('authors', function($authorQuery) use ($searchQuery) {
                $nameConditions = $this->getIlikeConditions($searchQuery, 'name');
                $authorQuery->where(function($subQ) use ($nameConditions) {
                    foreach ($nameConditions as [$condition, $value]) {
                        $subQ->orWhereRaw($condition, [$value]);
                    }
                });
            });
        })->orderByRaw("
            ts_rank(to_tsvector('english', COALESCE(title, '')), to_tsquery('english', ?)) +
            ts_rank(to_tsvector('english', COALESCE(isbn, '')), to_tsquery('english', ?)) DESC,
            CASE 
                WHEN LOWER(title) = ? THEN 1
                WHEN LOWER(title) LIKE ? THEN 2
                WHEN LOWER(title) LIKE ? THEN 3
                ELSE 4
            END
        ", [$preparedQuery, $preparedQuery, strtolower($searchQuery), strtolower($searchQuery) . '%', '%' . strtolower($searchQuery) . '%']);
    }
}