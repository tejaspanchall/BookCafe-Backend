<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing books
        Book::truncate();
        
        // Top 100 most popular books of all time with predefined high-quality images
        $topBooks = [
            [
                'title' => 'To Kill a Mockingbird',
                'author' => 'Harper Lee',
                'category' => 'Fiction',
                'description' => 'A gripping, heart-wrenching, and wholly remarkable tale of coming-of-age in a South poisoned by virulent prejudice.',
                'image' => 'https://m.media-amazon.com/images/I/71FxgtFKcQL._AC_UF1000,1000_QL80_.jpg',
                'price' => 299
            ],
            [
                'title' => '1984',
                'author' => 'George Orwell',
                'category' => 'Dystopian',
                'description' => 'Among the seminal texts of the 20th century, Nineteen Eighty-Four is a rare work that grows more haunting as its futuristic purgatory becomes more real.',
                'image' => 'https://m.media-amazon.com/images/I/71kxa1-0mfL._AC_UF1000,1000_QL80_.jpg',
                'price' => 279
            ],
            [
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'category' => 'Fiction',
                'description' => 'The story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan.',
                'image' => 'https://m.media-amazon.com/images/I/71FTb9X6wsL._AC_UF1000,1000_QL80_.jpg',
                'price' => 249
            ],
            [
                'title' => 'Harry Potter and the Sorcerer\'s Stone',
                'author' => 'J.K. Rowling',
                'category' => 'Fantasy',
                'description' => 'Harry Potter has never been the star of a Quidditch team, scoring points while riding a broom far above the ground.',
                'image' => 'https://m.media-amazon.com/images/I/81iqZ2HHD-L._AC_UF1000,1000_QL80_.jpg',
                'price' => 319
            ],
            [
                'title' => 'The Lord of the Rings',
                'author' => 'J.R.R. Tolkien',
                'category' => 'Fantasy',
                'description' => 'One Ring to rule them all, One Ring to find them, One Ring to bring them all and in the darkness bind them.',
                'image' => 'https://m.media-amazon.com/images/I/71jLBXtWJWL._AC_UF1000,1000_QL80_.jpg',
                'price' => 349
            ],
            [
                'title' => 'Pride and Prejudice',
                'author' => 'Jane Austen',
                'category' => 'Romance',
                'description' => 'The story follows the main character, Elizabeth Bennet, as she deals with issues of manners, upbringing, morality, education, and marriage.',
                'image' => 'https://m.media-amazon.com/images/I/71Q1tPupKjL._AC_UF1000,1000_QL80_.jpg',
                'price' => 239
            ],
            [
                'title' => 'The Hobbit',
                'author' => 'J.R.R. Tolkien',
                'category' => 'Fantasy',
                'description' => 'Bilbo Baggins is a hobbit who enjoys a comfortable, unambitious life, rarely traveling any farther than his pantry or cellar.',
                'image' => 'https://m.media-amazon.com/images/I/710+HcoP38L._AC_UF1000,1000_QL80_.jpg',
                'price' => 279
            ],
            [
                'title' => 'The Catcher in the Rye',
                'author' => 'J.D. Salinger',
                'category' => 'Fiction',
                'description' => 'The hero-narrator of The Catcher in the Rye is an ancient child of sixteen, a native New Yorker named Holden Caulfield.',
                'image' => 'https://m.media-amazon.com/images/I/8125BDk3l9L.jpg',
                'price' => 259
            ],
            [
                'title' => 'Animal Farm',
                'author' => 'George Orwell',
                'category' => 'Fiction',
                'description' => 'A farm is taken over by its overworked, mistreated animals. With flaming idealism and stirring slogans, they set out to create a paradise of progress, justice, and equality.',
                'image' => 'https://m.media-amazon.com/images/I/91LUbAcpACL._AC_UF1000,1000_QL80_.jpg',
                'price' => 249
            ],
            [
                'title' => 'The Da Vinci Code',
                'author' => 'Dan Brown',
                'category' => 'Mystery',
                'description' => 'While in Paris, Harvard symbologist Robert Langdon is awakened by a phone call in the dead of the night.',
                'image' => 'https://m.media-amazon.com/images/I/91Q5dCjc2KL._AC_UF1000,1000_QL80_.jpg',
                'price' => 289
            ],
            [
                'title' => 'The Alchemist',
                'author' => 'Paulo Coelho',
                'category' => 'Fiction',
                'description' => 'The Alchemist follows the journey of an Andalusian shepherd boy named Santiago.',
                'image' => 'https://m.media-amazon.com/images/I/81FPzmB5fgL.jpg',
                'price' => 259
            ],
            [
                'title' => 'Harry Potter and the Chamber of Secrets',
                'author' => 'J.K. Rowling',
                'category' => 'Fantasy',
                'description' => 'The second installment of the Harry Potter series finds young wizard Harry Potter and his friends facing new challenges.',
                'image' => 'https://m.media-amazon.com/images/I/91OINeHnJGL._AC_UF1000,1000_QL80_.jpg',
                'price' => 319
            ],
            [
                'title' => 'The Hunger Games',
                'author' => 'Suzanne Collins',
                'category' => 'Young Adult',
                'description' => 'In the ruins of a place once known as North America lies the nation of Panem, a shining Capitol surrounded by twelve outlying districts.',
                'image' => 'https://m.media-amazon.com/images/I/71WSzS6zvCL._AC_UF1000,1000_QL80_.jpg',
                'price' => 299
            ],
            [
                'title' => 'Romeo and Juliet',
                'author' => 'William Shakespeare',
                'category' => 'Tragedy',
                'description' => 'A tragedy written early in the career of William Shakespeare about two young star-crossed lovers whose deaths ultimately reconcile their feuding families.',
                'image' => 'https://m.media-amazon.com/images/I/61LQf6GWT4L.jpg',
                'price' => 229
            ],
            [
                'title' => 'The Chronicles of Narnia',
                'author' => 'C.S. Lewis',
                'category' => 'Fantasy',
                'description' => 'C. S. Lewis\'s The Chronicles of Narnia has captivated readers of all ages for over sixty years.',
                'image' => 'https://m.media-amazon.com/images/I/81IsNyKSOmL.jpg',
                'price' => 349
            ],
            [
                'title' => 'Gone with the Wind',
                'author' => 'Margaret Mitchell',
                'category' => 'Historical Fiction',
                'description' => 'Set against the dramatic backdrop of the American Civil War, Margaret Mitchell\'s epic novel of love and war won the Pulitzer Prize.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/4/4a/Gone_with_the_Wind_%281936%2C_first_edition_cover%29.jpg',
                'price' => 339
            ],
            [
                'title' => 'The Fault in Our Stars',
                'author' => 'John Green',
                'category' => 'Young Adult',
                'description' => 'Despite the tumor-shrinking medical miracle that has bought her a few years, Hazel has never been anything but terminal.',
                'image' => 'https://m.media-amazon.com/images/I/817tHNcyAgL._AC_UF1000,1000_QL80_.jpg',
                'price' => 269
            ],
            [
                'title' => 'The Kite Runner',
                'author' => 'Khaled Hosseini',
                'category' => 'Fiction',
                'description' => 'The unforgettable, heartbreaking story of the unlikely friendship between a wealthy boy and the son of his father\'s servant.',
                'image' => 'https://m.media-amazon.com/images/I/81IzbD2IiIL._AC_UF1000,1000_QL80_.jpg',
                'price' => 289
            ],
            [
                'title' => 'Harry Potter and the Prisoner of Azkaban',
                'author' => 'J.K. Rowling',
                'category' => 'Fantasy',
                'description' => 'For twelve long years, the dread fortress of Azkaban held an infamous prisoner named Sirius Black.',
                'image' => 'https://m.media-amazon.com/images/I/81lAPl9Fl0L._AC_UF1000,1000_QL80_.jpg',
                'price' => 319
            ],
            [
                'title' => 'The Giver',
                'author' => 'Lois Lowry',
                'category' => 'Dystopian',
                'description' => 'The Giver, the 1994 Newbery Medal winner, has become one of the most influential novels of our time.',
                'image' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1342493368i/3636.jpg',
                'price' => 249
            ],
            [
                'title' => 'Alice\'s Adventures in Wonderland',
                'author' => 'Lewis Carroll',
                'category' => 'Fantasy',
                'description' => 'A renowned Fantasy masterpiece by Lewis Carroll, \'Alice\'s Adventures in Wonderland\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/71Uj9TYDQXL._UF1000,1000_QL80_.jpg',
                'price' => 265
            ],
            
            [
                'title' => 'The Grapes of Wrath',
                'author' => 'John Steinbeck',
                'category' => 'Fiction',
                'description' => 'A renowned Fiction masterpiece by John Steinbeck, \'The Grapes of Wrath\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/The_Grapes_of_Wrath_%281939_1st_ed_cover%29.jpg',
                'price' => 264
            ],
            
            [
                'title' => 'The Divine Comedy',
                'author' => 'Dante Alighieri',
                'category' => 'Epic Poetry',
                'description' => 'A renowned Epic Poetry masterpiece by Dante Alighieri, \'The Divine Comedy\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/51i-9SGWr-L._AC_UF1000,1000_QL80_.jpg',
                'price' => 289
            ],
            
            [
                'title' => 'The Call of the Wild',
                'author' => 'Jack London',
                'category' => 'Adventure',
                'description' => 'A renowned Adventure masterpiece by Jack London, \'The Call of the Wild\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/91IGZKkFKEL._AC_UF1000,1000_QL80_.jpg',
                'price' => 259
            ],
            
            [
                'title' => 'The Wind in the Willows',
                'author' => 'Kenneth Grahame',
                'category' => 'Children\'s Literature',
                'description' => 'A renowned Children\'s Literature masterpiece by Kenneth Grahame, \'The Wind in the Willows\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/716xrzpGQkL._AC_UF1000,1000_QL80_.jpg',
                'price' => 249
            ],
            
            [
                'title' => 'Heart of Darkness',
                'author' => 'Joseph Conrad',
                'category' => 'Novella',
                'description' => 'A renowned Novella masterpiece by Joseph Conrad, \'Heart of Darkness\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/71MFRCo1OpL._AC_UF1000,1000_QL80_.jpg',
                'price' => 239
            ],
            
            [
                'title' => 'The Road',
                'author' => 'Cormac McCarthy',
                'category' => 'Post-Apocalyptic',
                'description' => 'A renowned Post-Apocalyptic masterpiece by Cormac McCarthy, \'The Road\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/81ChFcmhXDL._AC_UF1000,1000_QL80_.jpg',
                'price' => 279
            ],
            
            [
                'title' => 'The Nightingale',
                'author' => 'Kristin Hannah',
                'category' => 'Historical Fiction',
                'description' => 'A renowned Historical Fiction masterpiece by Kristin Hannah, \'The Nightingale\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/914dNZ+lLjL._AC_UF1000,1000_QL80_.jpg',
                'price' => 299
            ],
            
            [
                'title' => 'The Testaments',
                'author' => 'Margaret Atwood',
                'category' => 'Dystopian',
                'description' => 'A renowned Dystopian masterpiece by Margaret Atwood, \'The Testaments\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://mms.businesswire.com/media/20190905005170/en/741761/5/Testaments.1.jpg',
                'price' => 319
            ],
            
            [
                'title' => 'A Thousand Splendid Suns',
                'author' => 'Khaled Hosseini',
                'category' => 'Historical Fiction',
                'description' => 'A renowned Historical Fiction masterpiece by Khaled Hosseini, \'A Thousand Splendid Suns\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/81xIPfJ6iUL.jpg',
                'price' => 289
            ],
            
            [
                'title' => 'A Man Called Ove',
                'author' => 'Fredrik Backman',
                'category' => 'Fiction',
                'description' => 'A renowned Fiction masterpiece by Fredrik Backman, \'A Man Called Ove\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/81g2oEdeGTL.jpg',
                'price' => 269
            ],
            
            [
                'title' => 'Big Little Lies',
                'author' => 'Liane Moriarty',
                'category' => 'Mystery',
                'description' => 'A renowned Mystery masterpiece by Liane Moriarty, \'Big Little Lies\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1594851437i/19486412.jpg',
                'price' => 279
            ],
            
            [
                'title' => 'Everything I Never Told You',
                'author' => 'Celeste Ng',
                'category' => 'Literary Fiction',
                'description' => 'A renowned Literary Fiction masterpiece by Celeste Ng, \'Everything I Never Told You\' has captivated readers for generations with its engaging narrative and profound themes.',
                'image' => 'https://m.media-amazon.com/images/I/816SaHQ8k9L.jpg',
                'price' => 269
            ]
        ];
        
        
        $additionalPopularBooks = [
            'Brave New World' => ['Aldous Huxley', 'Science Fiction'],
            'Hamlet' => ['William Shakespeare', 'Tragedy'],
            'The Little Prince' => ['Antoine de Saint-Exupéry', 'Children\'s Literature'],
            'The Handmaid\'s Tale' => ['Margaret Atwood', 'Dystopian'],
            'One Hundred Years of Solitude' => ['Gabriel García Márquez', 'Magical Realism'],
            'A Tale of Two Cities' => ['Charles Dickens', 'Historical Fiction'],
            'The Book Thief' => ['Markus Zusak', 'Historical Fiction'],
            'Jane Eyre' => ['Charlotte Brontë', 'Gothic Fiction'],
            'Fahrenheit 451' => ['Ray Bradbury', 'Dystopian'],
            'Lord of the Flies' => ['William Golding', 'Fiction'],
            'Wuthering Heights' => ['Emily Brontë', 'Gothic Fiction'],
            'Crime and Punishment' => ['Fyodor Dostoevsky', 'Psychological Fiction'],
            'Great Expectations' => ['Charles Dickens', 'Bildungsroman'],
            'The Odyssey' => ['Homer', 'Epic Poetry'],
            'Frankenstein' => ['Mary Shelley', 'Gothic Fiction'],
            'Dracula' => ['Bram Stoker', 'Gothic Fiction'],
            'Moby-Dick' => ['Herman Melville', 'Adventure'],
            'Don Quixote' => ['Miguel de Cervantes', 'Novel'],
            'The Picture of Dorian Gray' => ['Oscar Wilde', 'Gothic Fiction'],
            'The Adventures of Huckleberry Finn' => ['Mark Twain', 'Fiction'],
            'The Scarlet Letter' => ['Nathaniel Hawthorne', 'Romantic'],
            'War and Peace' => ['Leo Tolstoy', 'Historical Fiction'],
            'Anna Karenina' => ['Leo Tolstoy', 'Fiction'],
            'Catch-22' => ['Joseph Heller', 'Satire'],
            'The Count of Monte Cristo' => ['Alexandre Dumas', 'Adventure'],
            'Les Misérables' => ['Victor Hugo', 'Historical Fiction'],
            'Of Mice and Men' => ['John Steinbeck', 'Fiction'],
            'The Old Man and the Sea' => ['Ernest Hemingway', 'Fiction'],
            'The Brothers Karamazov' => ['Fyodor Dostoevsky', 'Philosophical Novel'],
            'Slaughterhouse-Five' => ['Kurt Vonnegut', 'Science Fiction'],
            'David Copperfield' => ['Charles Dickens', 'Bildungsroman'],
            'A Clockwork Orange' => ['Anthony Burgess', 'Dystopian'],
            'The Prince' => ['Niccolò Machiavelli', 'Political Philosophy'],
            'The Iliad' => ['Homer', 'Epic Poetry'],
            'Ulysses' => ['James Joyce', 'Modernist Novel'],
            'A Game of Thrones' => ['George R.R. Martin', 'Fantasy'],
            'The Girl with the Dragon Tattoo' => ['Stieg Larsson', 'Mystery'],
            'Life of Pi' => ['Yann Martel', 'Adventure'],
            'The Shining' => ['Stephen King', 'Horror'],
            'The Help' => ['Kathryn Stockett', 'Historical Fiction'],
            'The Girl on the Train' => ['Paula Hawkins', 'Thriller'],
            'Gone Girl' => ['Gillian Flynn', 'Thriller'],
            'Fifty Shades of Grey' => ['E.L. James', 'Romance'],
            'The Martian' => ['Andy Weir', 'Science Fiction'],
            'Ready Player One' => ['Ernest Cline', 'Science Fiction'],
            'Where the Crawdads Sing' => ['Delia Owens', 'Fiction'],
            'All the Light We Cannot See' => ['Anthony Doerr', 'Historical Fiction'],
            'Becoming' => ['Michelle Obama', 'Memoir'],
            'Educated' => ['Tara Westover', 'Memoir'],
            'The Silent Patient' => ['Alex Michaelides', 'Thriller'],
            'The Goldfinch' => ['Donna Tartt', 'Literary Fiction'],
            'Sapiens: A Brief History of Humankind' => ['Yuval Noah Harari', 'Non-fiction'],
            'Circe' => ['Madeline Miller', 'Fantasy'],
            'Dune' => ['Frank Herbert', 'Science Fiction'],
            'It' => ['Stephen King', 'Horror'],
            'The Night Circus' => ['Erin Morgenstern', 'Fantasy'],
            'The Underground Railroad' => ['Colson Whitehead', 'Historical Fiction'],
            'Eleanor Oliphant Is Completely Fine' => ['Gail Honeyman', 'Fiction'],
            'Normal People' => ['Sally Rooney', 'Fiction'],
            'American Gods' => ['Neil Gaiman', 'Fantasy'],
            'Outlander' => ['Diana Gabaldon', 'Historical Fiction'],
            'Little Fires Everywhere' => ['Celeste Ng', 'Fiction'],
            'The Name of the Wind' => ['Patrick Rothfuss', 'Fantasy'],
            'The Immortal Life of Henrietta Lacks' => ['Rebecca Skloot', 'Non-fiction'],
            'The Perks of Being a Wallflower' => ['Stephen Chbosky', 'Coming-of-Age'],
            'Cloud Atlas' => ['David Mitchell', 'Science Fiction'],
            'Station Eleven' => ['Emily St. John Mandel', 'Science Fiction']
        ];
        
        $books = $topBooks;
        $usedIsbns = [];
        $usedTitles = array_column($topBooks, 'title');
        $index = count($books) + 1;
        
        // Add additional books to reach 100 total
        foreach ($additionalPopularBooks as $title => $details) {
            // Skip if we already have 100 books
            if (count($books) >= 100) break;
            
            // Skip duplicate titles
            if (in_array(strtolower($title), array_map('strtolower', $usedTitles))) {
                continue;
            }
            
            $author = $details[0];
            $category = $details[1];
            
            // Generate a unique ISBN
            $isbn = $this->generateUniqueIsbn($usedIsbns);
            
            // Create a default description
            $description = "A renowned {$category} masterpiece by {$author}, '{$title}' has captivated readers for generations with its engaging narrative and profound themes.";
            
            // Generate a random price between 199 and 399 ($1.99 to $3.99)
            $price = rand(199, 399);
            
            // Use a high-quality book cover for the most popular books
            $imageUrl = $this->getHighQualityImageUrl($title);
            
            $books[] = [
                'title' => $title,
                'author' => $author,
                'category' => $category,
                'description' => $description,
                'image' => $imageUrl,
                'price' => $price,
                'isbn' => $isbn
            ];
            
            $usedIsbns[] = $isbn;
            $usedTitles[] = $title;
            $index++;
        }
        
        // Create books in the database
        foreach ($books as $book) {
            try {
                Book::create([
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'category' => $book['category'],
                    'description' => $book['description'],
                    'image' => $book['image'],
                    'price' => $book['price'],
                    'isbn' => $this->generateUniqueIsbn($usedIsbns)
                ]);
            } catch (\Exception $e) {
                echo "Error creating book '{$book['title']}': " . $e->getMessage() . "\n";
            }
        }
        
        // Check how many books we've created
        $createdCount = Book::count();
        echo "Created {$createdCount} books in the database.\n";
    }
    
    /**
     * Generate a unique ISBN
     */
    private function generateUniqueIsbn(&$usedIsbns)
    {
        $isbn = '978' . Str::random(10);
        while (in_array($isbn, $usedIsbns)) {
            $isbn = '978' . Str::random(10);
        }
        return $isbn;
    }
    
    /**
     * Get a high-quality image URL for a book
     */
    private function getHighQualityImageUrl($title)
    {
        // Replace special characters for URL encoding
        $formattedTitle = urlencode($title);
        
        // For popular books, use verified working image URLs
        $popularBooks = [
            // Main top books
            'to kill a mockingbird' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1553383690i/2657.jpg',
            '1984' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1657781256i/61439040.jpg',
            'the great gatsby' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1490528560i/4671.jpg',
            'harry potter and the sorcerer\'s stone' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1474154022i/3.jpg',
            'the lord of the rings' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1566425108i/33.jpg',
            'pride and prejudice' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1320399351i/1885.jpg',
            'the hobbit' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1546071216i/5907.jpg',
            'the catcher in the rye' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1398034300i/5107.jpg',
            'animal farm' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1424037542i/7613.jpg',
            'the da vinci code' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1579621267i/968.jpg',
            'the alchemist' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1654371463i/18144590.jpg',
            'harry potter and the chamber of secrets' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1474169725i/15881.jpg',
            'the hunger games' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1586722975i/2767052.jpg',
            'romeo and juliet' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1629680008i/18135.jpg',
            'the chronicles of narnia' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1449868701i/11127.jpg',
            'gone with the wind' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1551144577i/18405.jpg',
            'the fault in our stars' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1660273739i/11870085.jpg',
            'the kite runner' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1579036753i/77203.jpg',
            'harry potter and the prisoner of azkaban' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1630547330i/5.jpg',
            'the giver' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1342493368i/3636.jpg',
            
            // Additional popular books
            'brave new world' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1575509280i/5129.jpg',
            'hamlet' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1351051208i/1420.jpg',
            'the little prince' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1367545443i/157993.jpg',
            'the handmaid\'s tale' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1578028274i/38447.jpg',
            'one hundred years of solitude' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327881361i/320.jpg',
            'a tale of two cities' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1344922523i/1953.jpg',
            'the book thief' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1522157426i/19063.jpg',
            'jane eyre' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1557343311i/10210.jpg',
            'fahrenheit 451' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1383718290i/13079982.jpg',
            'lord of the flies' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327869409i/7624.jpg',
            'wuthering heights' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1388212715i/6185.jpg',
            'crime and punishment' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1382846449i/7144.jpg',
            'great expectations' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327920219i/2623.jpg',
            'the odyssey' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1390173285i/1381.jpg',
            'frankenstein' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1631088473i/35031085.jpg',
            'dracula' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1387151694i/17245.jpg',
            'moby-dick' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327940656i/153747.jpg',
            'don quixote' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1546112331i/3836.jpg',
            'the picture of dorian gray' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1424596966i/5297.jpg',
            'the adventures of huckleberry finn' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1546096879i/2956.jpg',
            'the scarlet letter' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1404810944i/12296.jpg',
            'war and peace' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1413215930i/656.jpg',
            'anna karenina' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1601352433i/15823480.jpg',
            'catch-22' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1463157317i/168668.jpg',
            'the count of monte cristo' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1611834134i/7126.jpg',
            'les misérables' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1411852091i/24280.jpg',
            'of mice and men' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1511302904i/890.jpg',
            'the old man and the sea' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1329189714i/2165.jpg',
            'the brothers karamazov' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1427728126i/4934.jpg',
            'slaughterhouse-five' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1440319389i/4981.jpg',
            'david copperfield' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1461452762i/58696.jpg',
            'a clockwork orange' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1348339306i/227463.jpg',
            'the prince' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1390055828i/28862.jpg',
            'the iliad' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1388188509i/1371.jpg',
            'ulysses' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1428891345i/338798.jpg',
            'a game of thrones' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1562726234i/13496.jpg',
            'the girl with the dragon tattoo' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327868566i/2429135.jpg',
            'life of pi' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1631251689i/4214.jpg',
            'the shining' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1353277730i/11588.jpg',
            'the help' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1622355533i/4667024.jpg',
            'the girl on the train' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1574805682i/22557272.jpg',
            'gone girl' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1554086139i/19288043.jpg',
            'fifty shades of grey' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1385207843i/10818853.jpg',
            'the martian' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1413706054i/18007564.jpg',
            'ready player one' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1500930947i/9969571.jpg',
            'where the crawdads sing' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1582135294i/36809135.jpg',
            'all the light we cannot see' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1451445646i/18143977.jpg',
            'becoming' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1528206996i/38746485.jpg',
            'educated' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1506026635i/35133922.jpg',
            'the silent patient' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1668782119i/40097951.jpg',
            'the goldfinch' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1378710146i/17333223.jpg',
            'sapiens: a brief history of humankind' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1595674533i/23692271.jpg',
            'circe' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1565909496i/35959740.jpg',
            'dune' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1555447414i/44767458.jpg',
            'it' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1334416842i/830502.jpg',
            'the night circus' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1387124618i/9361589.jpg',
            'the underground railroad' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1493178362i/30555488.jpg',
            'eleanor oliphant is completely fine' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1493724347i/31434883.jpg',
            'normal people' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1571423190i/41057294.jpg',
            'american gods' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1462924585i/30165203.jpg',
            'outlander' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1529065012i/10964.jpg',
            'little fires everywhere' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1522684533i/34273236.jpg',
            'the name of the wind' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1270352123i/186074.jpg',
            'the immortal life of henrietta lacks' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1327878144i/6493208.jpg',
            'the perks of being a wallflower' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1520093244i/22628.jpg',
            'cloud atlas' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1406383769i/49628.jpg',
            'station eleven' => 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1451446835i/20170404.jpg'
        ];
        
        $lowerTitle = strtolower($title);
        if (isset($popularBooks[$lowerTitle])) {
            return $popularBooks[$lowerTitle];
        }
        
        // Reliable fallback for any other book
        return "https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1388212715i/6185.jpg";
    }
    
    /**
     * Sanitize text to ensure it's valid UTF-8
     */
    private function sanitizeText($text)
    {
        // Remove any invalid UTF-8 sequences
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        
        // Replace any potentially problematic characters
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
        
        // Return clean text or a default value if cleaning fails
        return $text ?: 'Text unavailable';
    }
} 