<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFullTextSearchToBooks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add tsvector column for full-text search using raw SQL
        DB::statement('ALTER TABLE books ADD COLUMN search_vector TSVECTOR');

        // Create indexes for the tsvector column
        DB::statement('CREATE INDEX books_search_idx ON books USING GIN(search_vector)');

        // Create a trigger to automatically update the search_vector when a book is updated
        // Include only title and ISBN, excluding description
        DB::statement('
            CREATE TRIGGER books_search_vector_update
            BEFORE INSERT OR UPDATE ON books
            FOR EACH ROW
            EXECUTE FUNCTION tsvector_update_trigger(
                search_vector, 
                \'pg_catalog.english\', 
                title, 
                isbn
            )
        ');

        // Update existing books with only title and ISBN in search vector
        DB::statement('
            UPDATE books 
            SET search_vector = to_tsvector(\'english\', 
                COALESCE(title, \'\') || \' \' || 
                COALESCE(isbn, \'\')
            )
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the trigger
        DB::statement('DROP TRIGGER IF EXISTS books_search_vector_update ON books');
        
        // Drop the indexes
        DB::statement('DROP INDEX IF EXISTS books_search_idx');
        
        // Drop the column
        DB::statement('ALTER TABLE books DROP COLUMN IF EXISTS search_vector');
    }
} 