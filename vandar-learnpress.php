<?php
/*
Plugin Name: Vandar Payment for LearnPress
Description: توسط این افزونه میتوانید پرداخت آنلاین برای دوره ها و کلاس های خود داشته باشید.
Author: وندار
Version: 1.0.0
Author URI: https://vandar.io
*/
defined( 'ABSPATH' ) || exit;
define( 'LP_ADDON_VANDAR_PAYMENT_FILE', __FILE__ );
define( 'LP_ADDON_VANDAR_PAYMENT_VER', '1.0.0' );
define( 'LP_ADDON_VANDAR_PAYMENT_REQUIRE_VER', '1.0.0' );
class LP_Addon_Vandar_Payment_Preload {
	public function __construct() {
		add_action( 'learn-press/ready', array( $this, 'load' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	public function load() {
		LP_Addon::load( 'LP_Addon_Vandar_Payment', 'inc/load.php', __FILE__ );
		remove_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	public function admin_notices() {
		?>
        <div class="error">
            <p><?php echo wp_kses(
					sprintf(
						__( '<strong>%s</strong> addon version %s requires %s version %s or higher is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-ٰvandar' ),
						__( 'LearnPress ٰVandar Payment', 'learnpress-ٰvandar' ),
						LP_ADDON_VANDAR_PAYMENT_VER,
						sprintf( '<a href="%s" target="_blank"><strong>%s</strong></a>', admin_url( 'plugin-install.php?tab=search&type=term&s=learnpress' ), __( 'LearnPress', 'learnpress-ٰvandar' ) ),
						LP_ADDON_VANDAR_PAYMENT_REQUIRE_VER
					),
					array(
						'a'      => array(
							'href'  => array(),
							'blank' => array()
						),
						'strong' => array()
					)
				); ?>
            </p>
        </div>
		<?php
	}
}
new LP_Addon_Vandar_Payment_Preload();
