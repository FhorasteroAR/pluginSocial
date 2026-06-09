<?php
/**
 * Settings: admin menu, options page and the Settings API registration.
 *
 * All configuration lives in a single option array (`social_feed_settings`),
 * sanitised on save. The WordPress Settings API supplies the nonce and the
 * options.php handling, so no manual nonce code is required here.
 *
 * A small static accessor (::get) is the single read point used across the
 * plugin, including by the network handlers for credentials.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;

class Social_Feed_Settings {

	/**
	 * Option name holding the settings array.
	 */
	const OPTION = 'social_feed_settings';

	/**
	 * Settings group / page slug.
	 */
	const SLUG = 'social-feed-settings';

	/**
	 * Cached settings for the current request.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'network'                => 'instagram',
			'count'                  => 9,
			'columns'                => 3,
			'title'                  => '',
			'auto_update'            => 1,
			'cron_interval'          => 'hourly',
			'instagram_access_token' => '',
			'linkedin_access_token'  => '',
			'linkedin_org_urn'       => '',
		);
	}

	/**
	 * Read one setting (or the whole array when $key is null).
	 *
	 * @param string|null $key     Setting key.
	 * @param mixed       $default Fallback when the key is missing/empty.
	 *
	 * @return mixed
	 */
	public static function get( $key = null, $default = '' ) {
		if ( null === self::$cache ) {
			self::$cache = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		}

		if ( null === $key ) {
			return self::$cache;
		}

		return ( isset( self::$cache[ $key ] ) && '' !== self::$cache[ $key ] ) ? self::$cache[ $key ] : $default;
	}

	/**
	 * Clear the in-request cache (used after a save).
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$cache = null;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the options page under Settings.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			__( 'Social Feed', 'social-feed' ),
			__( 'Social Feed', 'social-feed' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option, sections and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::SLUG,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		// --- Display section ---------------------------------------------------
		add_settings_section( 'social_feed_display', __( 'Display', 'social-feed' ), '__return_false', self::SLUG );

		$this->add_field( 'network', __( 'Default network', 'social-feed' ), 'field_network', 'social_feed_display' );
		$this->add_field( 'title', __( 'Default title', 'social-feed' ), 'field_text', 'social_feed_display', array( 'key' => 'title' ) );
		$this->add_field( 'count', __( 'Number of posts', 'social-feed' ), 'field_number', 'social_feed_display', array( 'key' => 'count', 'min' => 1, 'max' => 30 ) );
		$this->add_field( 'columns', __( 'Columns', 'social-feed' ), 'field_number', 'social_feed_display', array( 'key' => 'columns', 'min' => 1, 'max' => 6 ) );

		// --- Automation section ------------------------------------------------
		add_settings_section( 'social_feed_automation', __( 'Automatic updates', 'social-feed' ), array( $this, 'automation_intro' ), self::SLUG );

		$this->add_field( 'auto_update', __( 'Enable background updates', 'social-feed' ), 'field_checkbox', 'social_feed_automation', array( 'key' => 'auto_update', 'label' => __( 'Periodically refresh the feed via WP-Cron', 'social-feed' ) ) );
		$this->add_field( 'cron_interval', __( 'Update frequency', 'social-feed' ), 'field_interval', 'social_feed_automation' );

		// --- Credentials section ----------------------------------------------
		add_settings_section( 'social_feed_credentials', __( 'API credentials', 'social-feed' ), array( $this, 'credentials_intro' ), self::SLUG );

		$this->add_field( 'instagram_access_token', __( 'Instagram access token', 'social-feed' ), 'field_token', 'social_feed_credentials', array( 'key' => 'instagram_access_token', 'constant' => 'SOCIAL_FEED_INSTAGRAM_ACCESS_TOKEN' ) );
		$this->add_field( 'linkedin_access_token', __( 'LinkedIn access token', 'social-feed' ), 'field_token', 'social_feed_credentials', array( 'key' => 'linkedin_access_token', 'constant' => 'SOCIAL_FEED_LINKEDIN_ACCESS_TOKEN' ) );
		$this->add_field( 'linkedin_org_urn', __( 'LinkedIn organization URN', 'social-feed' ), 'field_token', 'social_feed_credentials', array( 'key' => 'linkedin_org_urn', 'constant' => 'SOCIAL_FEED_LINKEDIN_ORG_URN' ) );
	}

	/**
	 * Helper to register a field with a shared callback.
	 *
	 * @param string $id       Field id.
	 * @param string $label    Field label.
	 * @param string $callback Method name on this class.
	 * @param string $section  Section id.
	 * @param array  $args     Extra args passed to the callback.
	 *
	 * @return void
	 */
	private function add_field( $id, $label, $callback, $section, array $args = array() ) {
		add_settings_field( $id, $label, array( $this, $callback ), self::SLUG, $section, $args );
	}

	/* ---------------------------------------------------------------------- *
	 * Section intros
	 * ---------------------------------------------------------------------- */

	public function automation_intro() {
		echo '<p>' . esc_html__( 'Pull fresh posts in the background on a schedule so the feed is never stale and updates do not depend on visitor page loads.', 'social-feed' ) . '</p>';

		$next = wp_next_scheduled( Social_Feed_Cron::HOOK );
		if ( $next ) {
			echo '<p><em>' . sprintf(
				/* translators: %s: human-readable time difference. */
				esc_html__( 'Next scheduled update: in %s.', 'social-feed' ),
				esc_html( human_time_diff( time(), $next ) )
			) . '</em></p>';
		}
	}

	public function credentials_intro() {
		echo '<p>' . esc_html__( 'Leave blank to render sample posts. For production, defining these as constants in wp-config.php is more secure than storing them here.', 'social-feed' ) . '</p>';
	}

	/* ---------------------------------------------------------------------- *
	 * Field renderers
	 * ---------------------------------------------------------------------- */

	public function field_network() {
		$value    = self::get( 'network', 'instagram' );
		$networks = array_keys( Social_Feed_API_Handler::handlers() );
		echo '<select name="' . esc_attr( self::OPTION ) . '[network]">';
		foreach ( $networks as $network ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $network ),
				selected( $value, $network, false ),
				esc_html( ucfirst( $network ) )
			);
		}
		echo '</select>';
	}

	public function field_text( $args ) {
		$key = $args['key'];
		printf(
			'<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION ),
			esc_attr( $key ),
			esc_attr( self::get( $key ) )
		);
	}

	public function field_number( $args ) {
		$key = $args['key'];
		printf(
			'<input type="number" min="%4$d" max="%5$d" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION ),
			esc_attr( $key ),
			esc_attr( self::get( $key ) ),
			(int) $args['min'],
			(int) $args['max']
		);
	}

	public function field_checkbox( $args ) {
		$key = $args['key'];
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( self::OPTION ),
			esc_attr( $key ),
			checked( 1, (int) self::get( $key, 0 ), false ),
			esc_html( $args['label'] )
		);
	}

	public function field_interval() {
		$value     = self::get( 'cron_interval', 'hourly' );
		$schedules = Social_Feed_Cron::interval_choices();
		echo '<select name="' . esc_attr( self::OPTION ) . '[cron_interval]">';
		foreach ( $schedules as $name => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $name ),
				selected( $value, $name, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function field_token( $args ) {
		$key = $args['key'];

		if ( ! empty( $args['constant'] ) && defined( $args['constant'] ) && '' !== constant( $args['constant'] ) ) {
			echo '<input type="text" class="regular-text" value="" placeholder="' . esc_attr__( 'Defined in wp-config.php', 'social-feed' ) . '" disabled />';
			echo '<p class="description">' . esc_html__( 'This value is set via a constant and takes precedence.', 'social-feed' ) . '</p>';
			return;
		}

		printf(
			'<input type="password" autocomplete="off" class="regular-text" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION ),
			esc_attr( $key ),
			esc_attr( self::get( $key ) )
		);
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Sanitise the whole settings array on save and (re)schedule cron to match.
	 *
	 * @param array $input Raw submitted values.
	 *
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$out['network'] = isset( $input['network'] ) ? sanitize_key( $input['network'] ) : $out['network'];
		$out['title']   = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
		$out['count']   = isset( $input['count'] ) ? max( 1, min( 30, absint( $input['count'] ) ) ) : $out['count'];
		$out['columns'] = isset( $input['columns'] ) ? max( 1, min( 6, absint( $input['columns'] ) ) ) : $out['columns'];

		$out['auto_update']   = empty( $input['auto_update'] ) ? 0 : 1;
		$interval             = isset( $input['cron_interval'] ) ? sanitize_key( $input['cron_interval'] ) : 'hourly';
		$out['cron_interval'] = array_key_exists( $interval, Social_Feed_Cron::interval_choices() ) ? $interval : 'hourly';

		$out['instagram_access_token'] = isset( $input['instagram_access_token'] ) ? sanitize_text_field( $input['instagram_access_token'] ) : '';
		$out['linkedin_access_token']  = isset( $input['linkedin_access_token'] ) ? sanitize_text_field( $input['linkedin_access_token'] ) : '';
		$out['linkedin_org_urn']       = isset( $input['linkedin_org_urn'] ) ? sanitize_text_field( $input['linkedin_org_urn'] ) : '';

		// Reflect the new automation settings in WP-Cron immediately.
		Social_Feed_Cron::apply_schedule( $out['auto_update'], $out['cron_interval'] );

		// Invalidate caches so the next render/cron run uses the new config.
		self::$cache = null;
		Social_Feed_API_Handler::flush_all_caches();

		return $out;
	}

	/**
	 * Render the settings page wrapper.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SLUG );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
