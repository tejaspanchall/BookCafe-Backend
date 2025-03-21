<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'author',
        'category',
        'price'
    ];

    /**
     * Get the users that have this book in their library.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_books');
    }

    /**
     * Get the image URL for the book.
     *
     * @return string|null
     */
    protected function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return 'books/' . $value;
    }
}