<?php
/**
 * Adds a fully extendable page admin info page. Primarily intended for themes.
 *
 * All class defiend content is overridable via filters. New tabs and tab
 * contents can be added simply by adding a new array item to the tabs filter
 * that has a 'slug', a 'title' and a valid callback for the content.
 *
 * @package    PattonWebz_Framework
 * @subpackage Admin Page
 * @version    1.0.0
 * @author     William Patton <will@pattonwebz.com>
 * @copyright  Copyright (c) 2018, William Patton
 * @link       https://github.com/pattonwebz/customizer-framework/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

namespace PattonWebz\Framework;

/**
 * Class for creating the themes admin page. NOTE: .org themes are allowed to
 * create only a single admin page.
 */
class Admin_Page implements Theme_Admin_Page {

	/**
	 *
	 * The framework that is the parent of this project has the prefix defined
	 * as a trait for use.
	 *
	 * @see: \PattonWebz\Framework\Common\Prefix;
	 */

	/**
	 * The prefix to be used when putting anything into global scope.
	 *
	 * Default value is framework prefix - 'pattonwebz'. Use set_prefix() to
	 * update it.
	 *
	 * @access private
	 * @var string
	 */
	private $prefix = 'pattonwebz';

	/**
	 * Getter for the prefix property.
	 *
	 * @access public
	 * @return string
	 */
	public function get_prefix() {
		return (string) $this->prefix;
	}

	/**
	 * Setter for the prefix property.
	 *
	 * @access public
	 * @param  string $prefix The prefix string to be used throughout the class
	 * when placing items into a global scope of any kind.
	 */
	public function set_prefix( string $prefix = null ) {
		if ( null !== $prefix ) {
			$this->prefix = $prefix;
		}
	}

	/**
	 * Should hold an instance of a WP_Theme object.
	 *
	 * This object will hold information about the theme which can be used at
	 * various points throughout the page markup generation.
	 *
	 * @see: https://codex.wordpress.org/Class_Reference/WP_Theme
	 *
	 * @access private
	 * @var object
	 */
	private $theme_info = null;

	/**
	 * Holds the slug passed at page creation.
	 *
	 * @access protected
	 * @var string
	 */
	protected $page_slug = '';

	/**
	 * Should be set to hold an array of arrays for all the page tabs and their
	 * callbacks to output their contents.
	 *
	 * There are 3 keys required to be defined, 'slug', 'title', 'callback'.
	 * The callback should be a valid callable.
	 *
	 * @access protected
	 * @var array
	 */
	protected $page_tabs = array();

	/**
	 * Holds the active tab slug early as is feasable to get it.
	 *
	 * @access protected
	 * @var string
	 */
	protected $active_tab_slug = '';

	/**
	 * At construct setup the prefix if passed and get the theme_info for use
	 * through the class.
	 *
	 * @method __construct
	 * @param  string $prefix a string for use as prefix in handles.
	 */
	public function __construct( $prefix = null ) {
		// if we have a prefix redefine it.
		if ( null !== $prefix ) {
			// cast the prefix to a string incase of incorrect item passed.
			$this->prefix = (string) $prefix;
		}
		// sets the theme info to be a WP_Theme object of current theme.
		$this->theme_info = wp_get_theme();

	}

	/**
	 * Hooks in the page adding callback.
	 *
	 * @method page_hooks
	 * @return void
	 */
	public function page_hooks() {
		add_action( 'admin_menu', array( $this, 'hook_pages_callback' ) );
	}

	/**
	 * Callback to add the main theme admin page from the framework.
	 *
	 * @method hook_pages_callback
	 * @return void
	 */
	public function hook_pages_callback() {
		$title           = $this->theme_info->name;
		$this->page_slug = sanitize_title_with_dashes( $this->theme_info->name );
		add_submenu_page( 'themes.php', $title, $title, 'manage_options', $this->page_slug, array( $this, 'page_render' ) );
	}

	/**
	 * The render method for the admin. Echos instead of returnign.
	 *
	 * @method page_render
	 * @return void
	 */
	public function page_render() {
		// get the pagetabs.
		$this->page_tabs = $this->page_tabs();
		/**
		 * The usage of the $_GET and trusting it's valid without nonce here is
		 * fine because the value is not trusted without first being validated
		 * against a list of defined valid items.
		 *
		 * phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification, WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		 *
		 * @var string
		 */
		$active_tab = isset( $_GET['tab'] ) ? sanitize_title_with_dashes( wp_unslash( $_GET['tab'] ) ) : $this->page_tabs[0]['slug'];
		foreach ( $this->page_tabs as $tab ) {
			if ( $tab['slug'] === $active_tab ) {
				$this->active_tab_slug = $active_tab;
				/**
				 * Validated that the $_GET is safe and sane.
				 *
				 * phpcs:enable
				 */
			}
		}

		// get the page contents.
		$html = $this->get_page_contents();
		echo wp_kses_post( $html );
	}

	/**
	 * An array of tabs that will be output on the admin page if a callback is
	 * defined for them.
	 *
	 * NOTE: tests if the callback is valid is valid before returning the tabs
	 * so is more expansive than a simple helper return.
	 *
	 * @method page_tabs
	 * @return array
	 */
	public function page_tabs() {
		/**
		 * The tabs should be an array of arrays. All 3 keys are required to be
		 * defined, limited sanity checking is done on these values, make sure
		 * callback is a valid callable.
		 *
		 * @var array
		 */
		$tabs = array(
			array(
				'slug'     => 'main_tab',
				'title'    => __( 'Main Tab', 'pattonwebz' ),
				'callback' => array( $this, 'output_page_contents_tab_main' ),
			),
		);
		// filter the array to so that other tabs can be added easily.
		$tabs = apply_filters( $this->prefix . '_filter_admin_page_tabs', $tabs );
		if ( is_array( $tabs ) ) {
			// loop through tabs to test validity.
			foreach ( $tabs as $key => $tab ) {
				// assume false and be proved otherwise.
				$tab_valid = false;
				if ( is_array( $tab ) ) {
					// check this array has all the valuses we need.
					if ( array_key_exists( 'slug', $tab ) && array_key_exists( 'title', $tab ) && array_key_exists( 'callback', $tab ) ) {
						if ( is_callable( $tab['callback'] ) ) {
							// by this point we have all keys needed and callback looks valid.
							$tab_valid = true;
						}
					}
				}
				// if this is not a valid tab then unset it from array.
				if ( ! $tab_valid ) {
					unset( $tabs[ $key ] );
				}
			}
		}
		// return the validated tabs array.
		return $tabs;
	}

	/**
	 * Generates markup for a list of tabs to output on the admin page.
	 *
	 * @method output_page_tabs_selector
	 * @return string
	 */
	public function get_page_tabs_selector() {
		$tabs = $this->page_tabs;
		// confirm tab count hasn't fallen below 1.
		if ( is_array( $tabs ) && count( $tabs ) <= 1 ) {
			echo '<hr>';
			return;
		}
		// if we made it this far we have at least 2 tabs, generate output.
		ob_start();
		?>
		<!-- TABS -->
		<h2 class="nav-tab-wrapper wp-clearfix">
			<?php
			foreach ( $tabs as $tab ) {
				?>
				<a href="<?php echo esc_url( '?page=' . $this->page_slug . '&tab=' . $tab['slug'] ); ?>" class="nav-tab<?php echo $this->active_tab_slug === $tab['slug'] ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab['title'], 'pattonwebz' ); ?>
				</a>
				<?php
			}
			?>
		</h2>
		<!-- END TABS -->
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Returns a filtered array of various pieces of html text used in wrappers
	 * around different parts ot the page contents.
	 *
	 * @method page_wrappers
	 * @return array of html string
	 */
	private function page_wrappers() {
		$containers = array(
			'page_wrap_open'  => '<div class="wrap about-wrap full-width-layout">',
			'page_wrap_close' => '</div>',
		);
		return apply_filters( $this->prefix . '_filter_admin_page_content_wrappers', $containers );
	}
	/**
	 * Generated the markup for the admin page in the theme.
	 *
	 * @method get_page_contents
	 * @return string
	 */
	private function get_page_contents() {
		$wrappers = $this->page_wrappers();
		// this content is buffered, it is run through wp_kses_post before
		// output and has various internal escapes.
		// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		ob_start();
		echo $wrappers['page_wrap_open'];

		if ( is_array( $this->page_tabs ) && is_array( $this->page_tabs[0] ) && array_key_exists( 'slug', $this->page_tabs[0] ) ) {
			$slug = $this->page_tabs[0]['slug'];
		} else {
			$slug = '';
		}

		// Get the intro and upper sections followed maybe by tab links.
		echo $this->get_page_contents_intro();
		echo $this->get_page_contents_upper();
		echo $this->get_page_tabs_selector();

		// output the contents for the current tab.
		foreach ( $this->page_tabs as $tab ) {
			if ( $this->active_tab_slug === $tab['slug'] ) {
				// test if the cb exists as a method of this class or as an existing
				// function.
				if ( is_callable( $tab['callback'] ) ) {
					$tab_content = call_user_func_array( $tab['callback'], array( $this->active_tab_slug ) );
					echo $tab_content;
				}
			}
		}

		echo $wrappers['page_wrap_close'];
		$html = ob_get_clean();
		// after this point content should be late escaped as per the norm.
		// phpcs:enable
		return $html;
	}

	/**
	 * Generates the admin page intro section at page open.
	 *
	 * @method output_page_contents_intro
	 * @return string of html
	 */
	private function get_page_contents_intro() {
		$html = '';
		$html = apply_filters( $this->prefix . '_filter_admin_page_intro', $html, $this->active_tab_slug, $this->theme_info );
		if ( '' === $html ) {
			ob_start();
			$this->output_page_contents_intro();
			$html = ob_get_clean();
			return $html;
		}

	}

	/**
	 * Markup for the page intro section.
	 *
	 * @method get_page_contents_intro
	 * @return void
	 */
	private function output_page_contents_intro() {
		?>
		<h1><?php echo esc_html( $this->theme_info->title ); ?></h1>
		<p class="about-text"><?php echo esc_html( $this->theme_info->description ); ?></p>
		<?php
	}

	/**
	 * Creates html for use in an admin page.
	 *
	 * @method get_page_contents_upper
	 * @return string of html content
	 */
	private function get_page_contents_upper() {
		$html = '';
		$html = apply_filters( $this->prefix . '_filter_admin_page_upper', $html, $this->active_tab_slug, $this->theme_info );
		if ( '' === $html ) {
			ob_start();
			$this->output_page_contents_upper();
			$html = ob_get_clean();
		}
		return $html;
	}

	/**
	 * Markup for the upper section (feature section) of the page.
	 *
	 * @method get_page_contents_upper
	 * @return void
	 */
	private function output_page_contents_upper() {
		?>
		<div class="feature-section one-col">
			<div class="col">
				<?php
				// translators: 1 - theme title, 2 - an emojie heart image, 3 - html break tag.
				$header_text = sprintf( esc_html__( '%1$1s Is Built With %2$2s Using The %3$3s PattonWebz Theme Framework', 'pattonwebz' ),
					esc_html( $this->theme_info->name, 'pattonwebz' ),
					'<img draggable="false" class="emoji" alt="❤" src="https://s.w.org/images/core/emoji/2.4/svg/2764.svg">',
					'<br>'
				);
				?>
				<h2><?php echo $header_text; // wpcs: XSS ok. ?></h2>
				<p><?php esc_html_e( 'The framework is intended to provide setup actions and basic defaults for a theme. It does this through extendable classes, interfaces and traits that can be utulised in a child theme - or a parent theme including the framework directly.', 'pattonwebz' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns the page content for use in the main page tab.
	 *
	 * @method output_page_contents_tab_main
	 * @return string of html
	 */
	private function get_page_contents_tab_main() {
		$html = '';
		$html = apply_filters( $this->prefix . '_filter_admin_tab_main', $html, $this->active_tab_slug, $this->theme_info );
		if ( '' === $html ) {
			ob_start();
			$this->output_page_contents_tab_main();
			$html = ob_get_clean();
		}
		return $html;
	}

	/**
	 * Markup for the page's main tab content.
	 *
	 * @method get_page_contents_tab_main
	 * @return void
	 */
	private function output_page_contents_tab_main() {
		?>
		<div class="info-cols">
			<h2><?php esc_html_e( 'Framework Info', 'pattonwebz' ); ?> <img draggable="false" class="emoji" alt="🔧" src="https://s.w.org/images/core/emoji/2.4/svg/1f527.svg"></h2>
			<div class="two-col">
				<div class="col">
					<h3><?php esc_html_e( 'Theme Info:', 'pattonwebz' ); ?></h3>
					<ul>
						<li><?php echo esc_html( __( 'Theme Name: ', 'pattonwebz' ) . $this->theme_info->name ); ?></li>
						<li><?php echo esc_html( __( 'Theme Version: ', 'pattonwebz' ) . $this->theme_info->version ); ?></li>
					</ul>
				</div>
				<div class="col">
					<h3><?php esc_html_e( 'Help Support:', 'pattonwebz' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Support for this theme is likely provided by the theme author:', 'pattonwebz' ); ?> <?php echo wp_kses_post( $this->theme_info->author ); ?></li>
						<li><?php esc_html_e( 'Framework or development support can be found at the github repo:', 'pattonwebz' ); ?> <a href="https://github.com/pattonwebz/theme-framework/"><?php esc_html_e( 'PattonWebz Framework', 'pattonwebz' ); ?></a>.</li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
