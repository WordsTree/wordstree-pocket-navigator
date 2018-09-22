<?php

/**
 * Class WT_Pocket_Nav_Settings_Page
 *
 * This class controls all the cycle at the settings page.
 */

class WT_Pocket_Nav_Settings_Page {

    public function __construct() {

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'init_settings'  ) );

    }

    /**
     * Add Admin Settings Menu
     */
    public function add_admin_menu() {

        add_options_page(
            esc_html__( 'WordsTree Pocket Nav', 'wt_pocket_nav' ),
            esc_html__( 'WT Pocket Nav', 'wt_pocket_nav' ),
            'manage_options',
            'wt-pocket-nav',
            array( $this, 'wt_page_layout' )
        );

    }

    public function init_settings() {

        register_setting(
            'wt_settings_group',
            'wt_pocket_nav_settings'
        );

        add_settings_section(
            'wt_pocket_nav_settings_section',
            '',
            false,
            'wt_pocket_nav_settings'
        );

        add_settings_field(
            'wt_pocket_nav_client_key',
            __( 'Consumer Key', 'wt_pocket_nav' ),
            array( $this, 'render_wt_pocket_nav_client_key_field' ),
            'wt_pocket_nav_settings',
            'wt_pocket_nav_settings_section'
        );

    }

    public function wt_page_layout() {

        // Check required user capability
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wt_pocket_nav' ) );
        }

        // Admin Page Layout
        echo '<div class="wrap">';
        echo '	<h1>' . get_admin_page_title() . '</h1>';
        echo '	<form action="options.php" method="post">';

        settings_fields( 'wt_settings_group' );
        do_settings_sections( 'wt_pocket_nav_settings' );
        submit_button();

        echo '	</form>';
        echo '</div>';

    }

    function render_wt_pocket_nav_client_key_field() {

        // Retrieve data from the database.
        $options = get_option( 'wt_pocket_nav_settings' );

        // Set default value.
        $value = isset( $options['wt_pocket_nav_consumer_key'] ) ? $options['wt_pocket_nav_consumer_key'] : '';

        // Field output.
        echo '<input type="text" name="wt_pocket_nav_settings[wt_pocket_nav_consumer_key]" class="regular-text wt_pocket_nav_consumer_key_field" placeholder="' . esc_attr__( 'Paste your Consumer Key here!', 'wt_pocket_nav' ) . '" value="' . esc_attr( $value ) . '">';
        echo '<p class="description">' . __( 'This is the Consumer Key that you can get at https://getpocket.com/developer.', 'wt_pocket_nav' ) . '</p>';

    }

}

// new WT_Pocket_Nav_Settings_Page;