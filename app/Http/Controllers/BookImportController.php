<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use App\Traits\RedisCacheTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;

class BookImportController extends Controller
{
    use RedisCacheTrait;
    
    /**
     * Cache duration in seconds (24 hours)
     */
    const CACHE_DURATION = 86400;

    /**
     * Upload Excel file
     */
    public function uploadExcel(Request $request)
    {
        try {
            // Validate the uploaded file
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240', // max 10MB
            ]);

            // Generate a unique file ID
            $fileId = Str::uuid()->toString();

            // Store the file with the UUID as the filename in a public directory
            $path = $request->file('excel_file')->storeAs('excel_imports', $fileId, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Excel file uploaded successfully',
                'file_id' => $fileId,
                'file_name' => $request->file('excel_file')->getClientOriginalName()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload Excel file',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all uploaded Excel files
     */
    public function getExcelFiles()
    {
        try {
            $files = [];
            $disk = Storage::disk('public');
            $storedFiles = $disk->files('excel_imports');

            foreach ($storedFiles as $file) {
                // Extract the file ID (UUID) from the path
                $fileId = basename($file);
                
                // Get file metadata
                $lastModified = $disk->lastModified($file);
                
                $files[] = [
                    'file_id' => $fileId,
                    'file_name' => $fileId, // Original name not stored
                    'size' => $disk->size($file),
                    'last_modified' => $lastModified,
                    'created_at' => date('Y-m-d H:i:s', $lastModified)
                ];
            }

            // Sort by last modified date (newest first)
            usort($files, function($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });

            return response()->json([
                'status' => 'success',
                'files' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Excel files',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete Excel file
     */
    public function deleteExcelFile($fileId)
    {
        try {
            $filePath = 'excel_imports/' . $fileId;
            
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found'
                ], Response::HTTP_NOT_FOUND);
            }

            Storage::disk('public')->delete($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Excel file deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Excel file',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Import books from Excel file
     */
    public function importBooks($fileId)
    {
        try {
            $filePath = 'excel_imports/' . $fileId;
            
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Full path to the file
            $fullPath = Storage::disk('public')->path($filePath);
            
            // Create a new Reader for the file type
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fullPath);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);
            
            // Load the spreadsheet
            $spreadsheet = $reader->load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get the highest row number
            $highestRow = $worksheet->getHighestRow();
            
            // Validate that the file has data
            if ($highestRow <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The Excel file is empty or contains only headers'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Track import results
            $results = [
                'total' => $highestRow - 1,
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            // Begin database transaction
            \DB::beginTransaction();
            
            // Process each row starting from row 2 (assuming row 1 is header)
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $title = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    $isbn = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                    $authorsRaw = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                    $categoriesRaw = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                    $description = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                    $price = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                    
                    // Validate required fields
                    if (empty($title) || empty($isbn)) {
                        throw new \Exception('Title and ISBN are required');
                    }
                    
                    // Validate ISBN is unique
                    if (Book::where('isbn', $isbn)->exists()) {
                        throw new \Exception("ISBN '$isbn' already exists");
                    }
                    
                    // Process authors (comma separated)
                    $authors = array_map('trim', explode(',', $authorsRaw ?? ''));
                    if (empty($authors[0])) {
                        throw new \Exception('At least one author is required');
                    }
                    
                    // Process categories (comma separated)
                    $categories = [];
                    if (!empty($categoriesRaw)) {
                        $categories = array_map('trim', explode(',', $categoriesRaw));
                    }
                    
                    // Create the book
                    $book = new Book();
                    $book->title = $title;
                    $book->isbn = $isbn;
                    $book->description = $description;
                    $book->price = is_numeric($price) ? $price : null;
                    $book->created_at = now();
                    $book->save();
                    
                    // Add authors and categories
                    $this->addBookAuthors($book, $authors);
                    if (!empty($categories[0])) {
                        $this->addBookCategories($book, $categories);
                    }
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $row,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Check if any books were successfully imported
            if ($results['success'] > 0) {
                \DB::commit();
                
                // Refresh caches
                $bookController = app(BookController::class);
                $refreshMethod = new \ReflectionMethod($bookController, 'refreshBookCaches');
                $refreshMethod->setAccessible(true);
                $refreshMethod->invoke($bookController);
                
                return response()->json([
                    'status' => 'success',
                    'message' => $results['success'] . ' books imported successfully',
                    'results' => $results
                ]);
            } else {
                \DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No books were imported',
                    'results' => $results
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import books',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download Excel template for book import
     */
    public function downloadTemplate()
    {
        try {
            // Create a new spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $sheet->setCellValue('A1', 'Title');
            $sheet->setCellValue('B1', 'ISBN');
            $sheet->setCellValue('C1', 'Authors (comma separated)');
            $sheet->setCellValue('D1', 'Categories (comma separated)');
            $sheet->setCellValue('E1', 'Description');
            $sheet->setCellValue('F1', 'Price');
            
            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9E9E9']
                ]
            ];
            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
            
            // Add some sample data
            $sheet->setCellValue('A2', 'Sample Book Title');
            $sheet->setCellValue('B2', '978-1234567890');
            $sheet->setCellValue('C2', 'Author Name, Second Author');
            $sheet->setCellValue('D2', 'Fiction, Fantasy');
            $sheet->setCellValue('E2', 'This is a sample book description.');
            $sheet->setCellValue('F2', '29.99');
            
            // Auto-size columns
            foreach (range('A', 'F') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Create a temporary file
            $templatePath = tempnam(sys_get_temp_dir(), 'book_import_template');
            $writer->save($templatePath);
            
            // Set file permissions
            try {
                chmod($templatePath, 0644);
                \Log::info('Set template file permissions to 0644');
            } catch (\Exception $e) {
                \Log::warning('Could not set permissions on template file: ' . $e->getMessage());
                // Continue execution, don't rethrow
            }
            
            // Return the file as a download with cache prevention headers
            \Log::info('Returning file download response');
            
            // Create the response first
            $response = response()->download($templatePath, 'book_import_template.xlsx');
            
            // Add headers to the response
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
            
            return $response;
                
        } catch (\Exception $e) {
            \Log::error('Template download failed: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            
            $errorMessage = $e->getMessage();
            // Provide a user-friendly message for permission errors
            if (strpos($errorMessage, 'Permission denied') !== false || 
                strpos($errorMessage, 'Operation not permitted') !== false) {
                $errorMessage = 'Server permission error. Please contact your administrator.';
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create template: ' . $errorMessage
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh book caches to make imported books visible in the catalog
     */
    protected function refreshBookCaches()
    {
        try {
            \Log::info('Starting cache refresh...');
            
            // Clear main book caches
            Cache::forget('books:all');
            Cache::forget('books:library');
            Cache::forget('books:popular');
            \Log::info('Cleared main book caches');
            
            // Clear search caches
            $this->invalidateBookCache('books:search:*');
            \Log::info('Cleared search caches');
            
            // Clear individual book caches
            $this->invalidateBookCache('book:*');
            \Log::info('Cleared individual book caches');
            
            // Clear category-related book caches
            $this->invalidateBookCache('category:*:books');
            \Log::info('Cleared category book caches');
            
            // Clear author-related book caches
            $this->invalidateBookCache('author:*:books');
            \Log::info('Cleared author book caches');
            
            // Clear specific cache keys that might be used in other controllers
            $cachesToClear = [
                'books.all',
                'books.recent',
                'books.featured',
                'categories.with.books',
                'authors.with.books'
            ];
            
            foreach ($cachesToClear as $cache) {
                Cache::forget($cache);
            }
            \Log::info('Cleared additional specific caches');
            
            \Log::info('Cache refresh completed successfully');
        } catch (\Exception $e) {
            // Log the error but don't fail the import process
            \Log::error('Error refreshing book caches after import: ' . $e->getMessage());
        }
    }

    /**
     * Check if the PostgreSQL database connection is working
     */
    protected function checkDatabaseConnection()
    {
        try {
            \Log::info('Checking database connection...');
            // Perform a simple query to check connection
            $result = DB::select('SELECT 1 as connected');
            $isConnected = !empty($result) && isset($result[0]->connected) && $result[0]->connected == 1;
            \Log::info('Database connection status: ' . ($isConnected ? 'Connected' : 'Failed'));
            return $isConnected;
        } catch (\Exception $e) {
            \Log::error('Database connection check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add authors to a book
     * 
     * @param Book $book
     * @param array $authors
     */
    private function addBookAuthors(Book $book, array $authors)
    {
        $authorIds = [];
        foreach ($authors as $authorName) {
            if (!empty($authorName)) {
                $author = Author::firstOrCreate(['name' => $authorName]);
                $authorIds[] = $author->id;
            }
        }

        if (!empty($authorIds)) {
            $book->authors()->sync($authorIds);
        }
    }

    /**
     * Add categories to a book
     * 
     * @param Book $book
     * @param array $categories
     */
    private function addBookCategories(Book $book, array $categories)
    {
        $categoryIds = [];
        foreach ($categories as $categoryName) {
            if (!empty($categoryName)) {
                $category = Category::firstOrCreate(['name' => $categoryName]);
                $categoryIds[] = $category->id;
            }
        }

        if (!empty($categoryIds)) {
            $book->categories()->sync($categoryIds);
        }
    }
} 