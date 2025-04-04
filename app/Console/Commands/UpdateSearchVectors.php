<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Book;

class UpdateSearchVectors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:update-vectors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the full-text search vectors for all books';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating search vectors for all books...');

        // First update books with title and ISBN only
        DB::statement('
            UPDATE books 
            SET search_vector = to_tsvector(\'english\', 
                COALESCE(title, \'\') || \' \' || 
                COALESCE(isbn, \'\')
            )
        ');

        // Get all books with their authors to update the search vectors with author names
        $books = Book::with('authors')->get();
        
        $counter = 0;
        $total = count($books);
        
        $this->output->progressStart($total);
        
        foreach ($books as $book) {
            // Get author names as a string
            $authorNames = $book->authors->pluck('name')->implode(' ');
            
            if (!empty($authorNames)) {
                // Update search vector to include author names
                DB::statement("
                    UPDATE books 
                    SET search_vector = search_vector || to_tsvector('english', ?)
                    WHERE id = ?
                ", [$authorNames, $book->id]);
            }
            
            $counter++;
            $this->output->progressAdvance();
        }
        
        $this->output->progressFinish();
        
        $this->info('Search vectors updated successfully for ' . $counter . ' books!');
    }
} 