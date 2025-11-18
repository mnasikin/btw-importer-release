# BtW Importer

**BtW Importer** migrates your Blogger/Blogspot content to WordPress with a single click using your `.atom` file.

A powerful yet simple migration tool, BtW Importer helps you seamlessly transfer posts, images, and formatting from Blogger (Blogspot) to WordPress. Whether you're a casual blogger or managing a large archive, this plugin handles the complex parts so you don’t have to.


## 🧾 Changelog

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
