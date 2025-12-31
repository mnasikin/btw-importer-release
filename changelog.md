# BtW Importer

**BtW Importer** migrates your Blogger/Blogspot content to WordPress with a single click using your `.atom` file.

A powerful yet simple migration tool, BtW Importer helps you seamlessly transfer posts, images, and formatting from Blogger (Blogspot) to WordPress. Whether you're a casual blogger or managing a large archive, this plugin handles the complex parts so you don’t have to.


## Changelog
### 4.0.0
#### Added
- Multi-step import UI with visual step indicator (Upload → Extract → Import)
- Batch processing with 4 speed options
- Support for modern Blogger image URLs without file extensions
- Automatic image format detection using MIME type
- Image format preservation  
  PNG, GIF, WEBP, BMP keep their original format
- Import statistics displayed in card layout  
  Total Items, Posts, Pages
- Modern import controls  
  Start, Pause, Resume, Cancel
- Scrollable import log with auto-scroll
- Pause and resume timer  
  Elapsed time pauses when the import is paused
- Batch delay system to improve Nginx-based server performance
- Temporary `.atom` file storage at  
  `wp-content/uploads/btw-importer-temp/`  
  Automatically deleted after import is finished

#### Fixed
- Image URLs in posts are now correctly replaced with local WordPress URLs
- Images without file extensions can now be downloaded correctly
- Long filename handling  
  Filenames longer than 100 characters now use a short hash
- TIFF images are automatically converted to JPG for browser compatibility
- Improved error handling for expired Blogger image URLs

#### Improved
- Caching system to prevent re-downloading the same images
- Step pagination behavior during import
- Button states automatically enabled or disabled based on import status
- Default 50ms batch delay for more stable and faster imports
- Overall UI and styling improvements
- Import overlay completely removed

#### Notes
- Batch size `Fastest` is only recommended for VPS or dedicated servers

## 3.0.0 
- Fix HTML content on `pages` not imported
- Add styling on Importer and Redirect Log page
- Add legacy image URL (now support more image format and URL type)
- Add `wp_safe_redirect` in redirect for better security
- Security update based on WordPress 6.9 and PCP 1.7.0

### 2.3.0
- Fix post type: `page` redirect not working properly
- Auto hide overlay on fail, error, or stopped import
- Fix Undefined variable $total_items  on `Redirect Log` page

### 2.2.0 
- Remove comments from imported content. Previously, comments imported as posts 

### 2.1.0
- Fix draft and deleted content on .atom imported as published in WordPress

### 2.0.0
🔥 Major Update 🔥
- Add notice before you start importing (required)
- Add warning on leaving, reloading, or closing page during import to avoid accidentaly stop the process
- Add redirect log page to check list of redirection has beed made, also option to clear redirection logs
- Add 301 redirect from blogspot permalink to new wordpress URL to keep your SEO (only for post with `/YYYY/MM/slug.html` format). Only work if your previous blogspot using same Domain Name
- Posts or Pages date now sync as date in the .atom file (eg. your blogspot post published on 2022/02/02, then the post in wordpress also 2022/02/02)
- Categories added or use existing category based on .atom file
- Only blogspot/google images downloaded, others external (saving your hosting storage, especially if you use external CDN)
- Only download originial size images (avoid duplicated)

### 1.0.0
- Initial release  
- Replaced `parse_url()` with `wp_parse_url()`  
- Used `wp_delete_file()` instead of `unlink()`  
- Sanitized input using `wp_unslash()`  
- Sanitized content with `wp_kses_post()`