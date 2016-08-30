#Stick Post To Category
Contributors: bambattajb
Tags: categories, post
Requires at least: 4.4.2
Tested up to: 4.4.2
Stable tag: 0.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

##Description
This adds simple category-specific sticky posts functionality
I created this plugin because I wanted to give the content manager flexibility to be able to set sticky posts to any category or taxonomy term.

It is more of a developer tool. It will not just work 'out of the box'. You need to add code in the theme template files to get it to work.

This is mostly an adaptation of @tommcfarlin plugin `Category Sticky Post` https://en-gb.wordpress.org/plugins/category-sticky-post/. Tom, I borrowed some of your code as well; I hope you don't mind. Cheers mate :)

Tom's plugin actually does just work 'out of the box' if you're looking for something simple to display sticky's on a per-category basis.

##Usage
When the plugin is installed, a meta box is created in the edit-post screen labeled `Category Sticky`. 
When the content manager assigns categories or taxonomy terms to the post, this will populate with all the possibilities for where they can stick this post.

To render this on the front end, we use the following static method

```php
$sticky_query = StickyPost::query([taxonomy=string], [term=integer], [posts_per_page=integer]);
```

This returns the following

```php
stdClass Object
    [query]     => WP_Query Object ## Use as the query for the loop
    [post_ids]  => Array ## The list of post ids that have been queried
```

##Examples

### Use in Category Template

Because the category template might will probably just have a basic loop, we need to add args on the fly

```php
$sticky_query = StickyPost::query('category', get_queried_object()->term_id, 2);
global $wp_query;
$args = array_merge( $wp_query->query_vars, array( 'post__not_in' => $sticky_query->post_ids ) );
query_posts($args);
```

### Use in Taxonomy Template with new WP_Query

Say we wanted to return 2 stickies in the current term and wanted to remove those returned posts from another custom `WP_Query`

```php
$sticky_query = StickyPost::query('topic', get_queried_object()->term_id, 2);

if ( $sticky_query->query->have_posts() ) {
    while ( $sticky_query->query->have_posts() ) {
    $sticky_query->query->the_post();

        // LoopdeLoop
                            
    }
}

$main_query = new WP_Query(array(
    // ...args galore
    'post__not_in' => $sticky_query->post_ids
));

```

### Version History
0.0.2
Added sticky filter in posts table
Added options page to turn on/off taxonomies for post types

##TODOS
- Add options panel 
- [option] select enabled post types (at the moment it's all post types)
- [option] select enabled taxonomies
- Create code-free default functionality