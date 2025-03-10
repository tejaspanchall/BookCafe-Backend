<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

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
        'author'
    ];

    /**
     * Get the users that have this book in their library.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_books');
    }
}