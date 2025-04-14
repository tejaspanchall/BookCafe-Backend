<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        
        // Get the active sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Book Import Template');

        // Define headers
        $headers = ['Title', 'ISBN', 'Authors', 'Description', 'Categories', 'Price', 'Image_URL'];
        $sheet->fromArray($headers, null, 'A1');

        // Add some sample data
        $sampleData = [
            ['The Great Gatsby', '9780743273565', 'F. Scott Fitzgerald', 'A novel about the American Dream', 'Fiction, Classics', '12.99', 'https://example.com/book-cover-1.jpg'],
            ['To Kill a Mockingbird', '9780061120084', 'Harper Lee', 'A novel about justice and racial inequality', 'Fiction, Classics', '14.99', 'https://example.com/book-cover-2.jpg'],
            ['', '', '', '', '', '', '']
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Format headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30); // Title
        $sheet->getColumnDimension('B')->setWidth(20); // ISBN
        $sheet->getColumnDimension('C')->setWidth(30); // Authors
        $sheet->getColumnDimension('D')->setWidth(50); // Description
        $sheet->getColumnDimension('E')->setWidth(30); // Categories
        $sheet->getColumnDimension('F')->setWidth(15); // Price
        $sheet->getColumnDimension('G')->setWidth(40); // Image URL

        // Add data validation for required fields
        $requiredStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
            ],
        ];
        
        // Add notes about required fields and formatting
        $sheet->setCellValue('A6', 'Note:');
        $sheet->setCellValue('A7', '* Title, ISBN, and Authors are required fields.');
        $sheet->setCellValue('A8', '* Multiple authors or categories should be separated by commas.');
        $sheet->setCellValue('A9', '* Price should be a number (e.g., 12.99).');
        $sheet->setCellValue('A10', '* Image_URL should be a valid web URL to an image (e.g., https://example.com/image.jpg).');
        $sheet->setCellValue('A11', '* First two rows contain sample data. Please remove or replace them.');
        
        $sheet->getStyle('A7')->applyFromArray($requiredStyle);
        $sheet->mergeCells('A7:G7');
        $sheet->mergeCells('A8:G8');
        $sheet->mergeCells('A9:G9');
        $sheet->mergeCells('A10:G10');
        $sheet->mergeCells('A11:G11');
        
        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filePath = storage_path('app/templates/book_import_template.xlsx');

        // Ensure the directory exists
        $templateDir = storage_path('app/templates');
        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0755, true);
        }

        $writer->save($filePath);

        $this->info('Template created successfully: ' . $filePath);
        
        return Command::SUCCESS;
    }
} 