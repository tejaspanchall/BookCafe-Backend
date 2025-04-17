<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class CreateBookImportTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:create-import-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an Excel template for importing books';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating book import template...');

        try {
            // Increase memory limit temporarily
            $currentMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '256M');
            
            // Clean up any existing file to avoid issues
            $filePath = storage_path('app/templates/book_import_template.xlsx');
            if (file_exists($filePath)) {
                @unlink($filePath);
                $this->info("Removed existing template file");
            }
            
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            
            // Set up first sheet (Book Import Template)
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Book Import Template');

            // Define headers
            $headers = ['Title', 'ISBN', 'Authors', 'Description', 'Categories', 'Price', 'Image_URL'];
            $sheet->fromArray($headers, null, 'A1');

            // Format headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2C3E50'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'BDC3C7'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(40); // Title
            $sheet->getColumnDimension('B')->setWidth(20); // ISBN
            $sheet->getColumnDimension('C')->setWidth(40); // Authors
            $sheet->getColumnDimension('D')->setWidth(50); // Description
            $sheet->getColumnDimension('E')->setWidth(30); // Categories
            $sheet->getColumnDimension('F')->setWidth(15); // Price
            $sheet->getColumnDimension('G')->setWidth(40); // Image URL
            
            // Create second sheet for instructions and sample data
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions & Sample Data');
            
            // Add sample data title first
            $instructionSheet->setCellValue('A1', 'SAMPLE DATA');
            
            // Format the section headers
            $sectionHeaderStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEEEEE'],
                ],
            ];
            $instructionSheet->getStyle('A1')->applyFromArray($sectionHeaderStyle);
            $instructionSheet->getStyle('A1:G1')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            
            // Add sample data headers
            $instructionSheet->fromArray($headers, null, 'A3');
            $instructionSheet->getStyle('A3:G3')->applyFromArray($headerStyle);
            
            // Add sample data rows
            $sampleData = [
                ['The Great Gatsby', '9780743273565', 'F. Scott Fitzgerald', 'A novel about the mysterious millionaire Jay Gatsby', 'Fiction, Classics', '14.99', 'https://example.com/great-gatsby.jpg'],
                ['To Kill a Mockingbird', '0061120081', 'Harper Lee', 'The story of racial injustice in the American South', 'Fiction, Classics, Drama', '12.99', 'https://example.com/mockingbird.jpg'],
                ['1984', '9780451524935', 'George Orwell', 'A dystopian novel set in a totalitarian regime', 'Fiction, Dystopian, Classics', '11.50', 'https://example.com/1984.jpg'],
            ];
            
            $row = 4;
            foreach ($sampleData as $data) {
                $instructionSheet->fromArray($data, null, 'A' . $row);
                $row++;
            }
            
            // Format sample data
            $instructionSheet->getStyle('A4:G6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            // Add instructions below sample data
            $instructionSheet->setCellValue('A8', 'INSTRUCTIONS');
            $instructionSheet->getStyle('A8')->applyFromArray($sectionHeaderStyle);
            $instructionSheet->getStyle('A8:G8')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            
            $instructionSheet->setCellValue('A9', 'Please follow these guidelines when filling out the template:');
            $instructionSheet->setCellValue('A11', '1. Only data from the "Book Import Template" sheet will be imported.');
            $instructionSheet->setCellValue('A12', '2. Required fields: Title, ISBN, and Authors.');
            $instructionSheet->setCellValue('A13', '3. ISBN must be 10 or 13 digits, numbers only (no dashes or spaces).');
            $instructionSheet->setCellValue('A14', '4. Authors should be separated by commas (e.g., "John Doe, Jane Smith").');
            $instructionSheet->setCellValue('A15', '5. Categories should be separated by commas (e.g., "Fiction, Fantasy, Adventure").');
            $instructionSheet->setCellValue('A16', '6. Price should be a numeric value (e.g., 19.99).');
            $instructionSheet->setCellValue('A17', '7. Image_URL should be a valid URL to an image (optional).');
            
            // Set column widths for instruction sheet
            $instructionSheet->getColumnDimension('A')->setWidth(60);
            
            // Auto-size columns for better readability
            foreach (range('A', 'G') as $column) {
                $instructionSheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Set the first sheet as active
            $spreadsheet->setActiveSheetIndex(0);

            // Create the Excel file
            $writer = new Xlsx($spreadsheet);

            // Ensure the directory exists with proper permissions
            $templateDir = storage_path('app/templates');
            if (!file_exists($templateDir)) {
                if (!mkdir($templateDir, 0777, true)) {
                    throw new \Exception("Failed to create directory: {$templateDir}");
                }
                chmod($templateDir, 0777); // Ensure directory is writable
                $this->info("Created directory: {$templateDir}");
            }

            // Check if directory is writable
            if (!is_writable($templateDir)) {
                chmod($templateDir, 0777);
                if (!is_writable($templateDir)) {
                    throw new \Exception("Directory is not writable: {$templateDir}");
                }
                $this->info("Updated permissions for directory: {$templateDir}");
            }

            // Save the file with options to optimize memory use
            $writer->setPreCalculateFormulas(false);
            $writer->save($filePath);

            // Check if the file was created successfully
            if (!file_exists($filePath)) {
                throw new \Exception("Failed to create template file at: {$filePath}");
            }
            
            // Ensure file permissions allow reading
            chmod($filePath, 0666);

            // Restore original memory limit
            ini_set('memory_limit', $currentMemoryLimit);

            $this->info('Template created successfully: ' . $filePath);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create template: ' . $e->getMessage());
            \Log::error('Template creation failed: ' . $e->getMessage());
            
            // Clean up any partial file
            if (isset($filePath) && file_exists($filePath)) {
                @unlink($filePath);
                $this->info("Cleaned up partial template file");
            }
            
            // Restore original memory limit if set
            if (isset($currentMemoryLimit)) {
                ini_set('memory_limit', $currentMemoryLimit);
            }
            
            return Command::FAILURE;
        }
    }
} 