# Theme Admin Page

This project contains a class for creating a simple to use Admin Page for use in a WordPress theme. It is part of an overarching Theme Framework project.

The class creates a page that occupies a single menu item location, found under appearance, but is capable of handling multiple pages of content by way of using a 'tabbed navigation' between each set of page contents.

If more than a single 'tab' of content is used then a navigation block will appear automatically for moving between the pieces of content.

## Using the class

The class has predefined defaults to make it quick to get started. Just include the class file, create an object and call the method to kickstart the page add.

```php
include_once /path/to/theme/directory/admin/class-admin-page.php;
$prefix_admin_page = new \PattonWebz\Framework\Admin_Page();
// if setting of any items is to happen you should do it here before calling
// the method below which is where the page is bootstrapped.
$prefix_admin_page->page_hooks();
```

## Adding or modifying pages.

Additional page tabs are added by passing in as an array containing a slug, tab title and a valid render callback.

### Filters

Almost every default part of the page is accessible or overloadable by a filter or a hook. The content outputting methods can be silenced by filtering in your own html string to use in their place. Extra pages of content can be added by adding an item to the $tabs array with a slug, title and valid callback.

### Prefix
Prefixing of various handles placed into a global scope is done by prepending a predefined string from the $prefix property to some of them. Prefix has a default value of 'pattonwebz' but can be passed in at creation time or updated dynamically with the provided setter method. Do this before you call the method to kickstart the adding of actions or hooks.

For example if your theme prefix was `extheme` then you canpass it in for use with the page like so:

```php
$extheme_admin_page = new \PattonWebz\Framework\Admin_Page( 'extheme' );
```
The class will then use the new prefix when registering hooks, actions or filters.

### Tabs array.

The tabs array is an array of arrays and it holds the page render callbacks and their slug/title. An example of some valid items would be this.
```php
	$tabs = array(
		array(
			// a callback inside the current object.
			'slug'  => 'example-page',
			'title' => 'Example Page',
			'callback' => array( $this, 'callback_output_generator_function' ),
		),
		array(
			// a callback from another instantiated object.
			'slug'  => 'example-page',
			'title' => 'Example Page',
			'callback' => array( An_Object_Instance, 'callback_output_generator_function' ),
		),
		array(
			// a callback which is a loose or global function.
			'slug'  => 'example-page',
			'title' => 'Example Page',
			'callback' => 'a_global_function',
		),
	);
```
