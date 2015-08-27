# Comment Scraper
PHP script extracting comments from web pages. 
Supports concurrent requests and data chunking (saves RAM).

# Usage
Example code is provided inside `index.php` file. If you are using
UNIX-based box you can run it via command line:

```php
php index.php
```

Consult `CommentScraper\Source\Dummy` class on how to implement
your own source.
