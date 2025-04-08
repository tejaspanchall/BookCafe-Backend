<?php
require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Book;
use Illuminate\Support\Facades\DB;

// Enable SQL query logging
DB::enableQueryLog();

// Testing search functionality
echo "TESTING WORD-BEGINNING SEARCH FUNCTIONALITY\n";
echo str_repeat('=', 80) . "\n\n";

// Function to display search results with helpful diagnostics
function testSearch($query, $type = 'title') {
    echo "Searching for '$query' (type: $type):\n";
    echo str_repeat('-', 50) . "\n";
    
    // Reset the query log
    DB::flushQueryLog();
    
    // Perform search
    $startTime = microtime(true);
    $results = null;
    
    switch($type) {
        case 'title':
            $results = Book::searchByTitle($query)->get();
            break;
        case 'author':
            $results = Book::searchByAuthor($query)->get();
            break;
        case 'isbn':
            $results = Book::searchByIsbn($query)->get();
            break;
        case 'all':
            $results = Book::searchAll($query)->get();
            break;
    }
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    // Display results count
    echo "Found " . count($results) . " results in {$executionTime}ms\n\n";
    
    // Display each result
    if (count($results) > 0) {
        echo "Results:\n";
        $i = 1;
        foreach ($results as $book) {
            $authorNames = $book->authors->pluck('name')->join(', ');
            echo "{$i}. {$book->title} (ISBN: {$book->isbn})" . 
                 ($authorNames ? " by {$authorNames}" : "") . "\n";
            $i++;
            
            // Limit to 10 results to avoid overload
            if ($i > 10) {
                echo "... and " . (count($results) - 10) . " more\n";
                break;
            }
        }
        echo "\n";
    } else {
        echo "No results found.\n\n";
    }
    
    // Show the executed SQL queries
    $queries = DB::getQueryLog();
    if (!empty($queries)) {
        echo "SQL Queries:\n";
        foreach ($queries as $index => $query) {
            // Format the binding values for display
            $formattedSQL = $query['query'];
            if (!empty($query['bindings'])) {
                $boundSql = str_replace(['?'], array_map(function($binding) {
                    if (is_string($binding)) {
                        return "'" . addslashes($binding) . "'";
                    } else if (is_null($binding)) {
                        return 'NULL';
                    } else if (is_bool($binding)) {
                        return $binding ? 'TRUE' : 'FALSE';
                    }
                    return $binding;
                }, $query['bindings']), $formattedSQL);
                echo ($index + 1) . ". " . $boundSql . "\n";
            } else {
                echo ($index + 1) . ". " . $formattedSQL . "\n";
            }
        }
        echo "\n";
    }
    
    echo str_repeat('-', 50) . "\n\n";
    return $results;
}

// Test a multi-word book title like "Cloud Atlas"
echo "Testing with 'Cloud Atlas':\n";

// 1. Full title test
testSearch('Cloud Atlas', 'title');

// 2. Beginning of first word
testSearch('Clo', 'title');

// 3. Beginning of second word
testSearch('At', 'title');

// 4. Middle of first word (should not match)
testSearch('lou', 'title');

// 5. Middle of second word (should not match)
testSearch('tla', 'title');

// Test another multi-word title
echo "Testing with 'The Great Gatsby':\n";

// 1. Full title test
testSearch('The Great Gatsby', 'title');

// 2. Beginning of words
testSearch('Gre', 'title');
testSearch('Gat', 'title');

// 3. Middle parts (should not match)
testSearch('eat', 'title');
testSearch('tsb', 'title');

// Show sample of books in database
echo "Sample books in database:\n";
$sampleBooks = Book::limit(8)->get();
foreach ($sampleBooks as $book) {
    echo "- {$book->title} (ISBN: {$book->isbn})\n";
}

echo "\nSearch testing complete.\n"; 