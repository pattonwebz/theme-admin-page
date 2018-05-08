<?php
/**
 * [namespace description]
 *
 * @package    PattonWebz_Framework
 *
 * @author     William Patton <will@pattonwebz.com>
 * @copyright  Copyright (c) 2018, William Patton
 * @link       https://github.com/pattonwebz/customizer-framework/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace PattonWebz\Framework;

interface Theme_Admin_Page {

	/**
	 * Construtor for the class. Should accept a prefix string and update a
	 * propery based on it's uses.
	 *
	 * @method __construct
	 * @param  string|null $prefix a prefix to use when hooking in actions or defining something in global scope.
	 * @return object
	 */
	public function __construct( $prefix = null );

	/**
	 * Called to hook in the page and render function to WP or it's location in another page.
	 *
	 * @method hook_pages
	 * @return null
	 */
	public function page_hooks();

	/**
	 * The page render method, should directly echo content already sanitized.
	 *
	 * @method page_render
	 * @return null
	 */
	public function page_render();
}
