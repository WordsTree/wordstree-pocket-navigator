<?php

/**
 * Defined how the meta box behaves.
 *
 * @package    WT_Pocket_Nav_Meta_Box
 * @subpackage WTPN_Pocket_Nav/includes
 * @author     Savio <savio@savioresende.com.br>
 */
class WT_Pocket_Nav_Meta_Box {

    /**
     * WT_Pocket_Nav_Meta_Box constructor.
     */
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
            add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
        }
    }

    /**
     *
     */
    public function init_metabox() {
        add_action( 'add_meta_boxes',        array( $this, 'add_metabox' )         );
        add_action( 'save_post',             array( $this, 'save_metabox' ), 10, 2 );
    }

    /**
     *
     */
    public function get_option_wt_user_key() {
        return 'wt_pocket_nav_user_' . get_current_user_id() . '_' . get_current_blog_id();
    }

    /**
     *
     */
    public function add_metabox() {
        add_meta_box(
            'wt-pocket-box-navigator',
            '<i class="fa fa-get-pocket" aria-hidden="true"></i>
&nbsp;&nbsp;' . __( 'Pocket Navigator', 'wt_pocket_nav_domain' ),
            array( $this, 'render_wt_pocket_nav_metabox' ),
            array('document', 'post', 'page'),
            'side',
            'default'
        );

    }

    /**
     * Main html
     *
     * @param $post
     */
    public function render_wt_pocket_nav_metabox( $post ) {
        ?><div id="wt-pocket-nav-container">
            <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i>
            <span class="sr-only">Loading...</span>
        </div><?php
    }

    /**
     *
     */
    public function wt_pocket_nav_ajax() { ?>
        <script type="text/javascript" >
            function reload_wt_pocket_nav_action(open_popup){
                jQuery.post(ajaxurl, {'action': 'wt_pocket_nav_action'}, function(response) {
                    if (open_popup !== true) {
                        jQuery('#wt-pocket-nav-container').html(response);
                    }

                    // TODO: check the validity of the request
                    var parsed_response = JSON.parse(response);

                    jQuery('#wt-pocket-nav-container').html('<div><a class="btn" onclick="reload_wt_pocket_nav_action(true)">Refresh</a></div>');

                    if (typeof parsed_response['redirect'] !== 'undefined') {
                        window.open(parsed_response['redirect']);
                    }
                });
            }
            jQuery(document).ready(function($) {
                reload_wt_pocket_nav_action();
            });

            // functions to navigate
            function wt_pocket_nav_to_items(tag) {
                jQuery('#wt-pocket-tag-container').hide();
                jQuery('#wt-pocket-items-container').show();
                jQuery('.wt-pocket-tag-' + tag).show();
            }
            function wt_pocket_nav_to_tags() {
                jQuery('#wt-pocket-tag-container').show();
                jQuery('#wt-pocket-items-container').hide();
                jQuery('.wt-pocket-item-list').hide();
            }
        </script> <?php
    }

    /**
     *
     */
    public function wt_pocket_nav_action_data($user = false) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        $message = "";

        $pocket = $this->getPocketInstance();

        if (isset($_GET['authorized']) || $user) {
            // Convert the requestToken into an accessToken
            // Note that a requestToken can only be covnerted once
            // Thus refreshing this page will generate an auth error

            $user = get_option($this->get_option_wt_user_key());
            if (!$user) {
                $user = $pocket->convertToken($_GET['authorized']);
                update_option(
                    $this->get_option_wt_user_key(),
                    $user
                );
                wp_die("Pocket Successfully Authorized! Now go to your document and refresh it.");
            }

            // Set the user's access token to be used for all subsequent calls to the Pocket API
            $pocket->setAccessToken($user['access_token']);

            // Retrieve the user's list of unread items (limit 5)
            // http://getpocket.com/developer/docs/v3/retrieve for a list of params
            $params = array(
                'state' => 'all',
                'sort' => 'newest',
                'detailType' => 'complete', // 'simple'
//                'count' => 5
            );
            $items = $pocket->retrieve($params, $user['access_token']);

            // empty list
            if (count($items) < 1) {
                wp_die('<div>Empty list.</div>');
            }

            $tag_items = array_map(function($item){
                if (!isset($item['tags'])) {
                    return false;
                }
                return current($item['tags'])['tag'];
            }, $items['list']);
            $tag_items = array_unique(array_filter($tag_items));

            // Tags list
            $message .= $this->build_tags_section($tag_items);

            // Items list
            $message .= $this->build_items_section($items);
        } else {
            $message = "Required data not present.";
        }

        wp_die($message);
    }

    /**
     * @throws PocketException
     */
    public function wt_pocket_nav_action() {
//        delete_option('wt_pocket_nav_user_' . get_current_user_id() . '_' . get_current_blog_id());
        $user = get_option('wt_pocket_nav_user_' . get_current_user_id() . '_' . get_current_blog_id());
        if ($user) {
            return $this->wt_pocket_nav_action_data($user);
        }

        $pocket = $this->getPocketInstance();

        // Attempt to detect the url of the current page to redirect back to
        // Normally you wouldn't do this
        $redirect = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://'  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?action=wt_pocket_nav_action_data&authorized=';

        // Request a token from Pocket
        $result = $pocket->requestToken($redirect);

        $result['redirect_uri'] = str_replace(
            urlencode('&authorized='),
            urlencode('&authorized=' . $result['request_token']),
            $result['redirect_uri']
        );

        wp_die(
            json_encode(['redirect' => $result['redirect_uri']])
        );
    }

    /**
     * @param $post_id
     * @param $post
     */
    public function build_tags_section($tag_items) {
        $message = '<div id="wt-pocket-tag-container">';
        $message .= '<h3>Tags</h3>';
        $message .= '<ul id="wt-pocket-tags-list">';
        foreach ($tag_items as $item) {
            $message .= '<li><a onclick="wt_pocket_nav_to_items(\'' . $item . '\')">' . $item . '</a></li>';
        }
        $message .= '</ul>';
        $message .= '</div>';

        return $message;
    }

    /**
     * @param $items
     * @return string
     */
    public function build_items_section($items) {
        $message = '<div id="wt-pocket-items-container">';
        $message .= '<h3><a onclick="wt_pocket_nav_to_tags()"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>&nbsp;&nbsp;&nbsp;Items List</h3>';
        $message .= '<ul id="wt-pocket-items-list">';
        foreach ($items['list'] as $item) {
            $classes = 'wt-pocket-item-list ';

            // used for tags to be hidden or shown
            if (isset($item['tags'])) {
                foreach ($item['tags'] as $tag) {
                    $classes .= 'wt-pocket-tag-' . $tag['tag'] . ' ';
                }
            }

            $message .= '<li class="' . $classes . '"><a target="_blank" href="' . $item['resolved_url'] . '">' . ($item['resolved_title'] ? $item['resolved_title'] : 'No title') . '</a></li>';
        }
        $message .= '';
        $message .= '</ul>';
        $message .= '</div>';

        return $message;
    }

    /**
     * Get Pocket Instance
     *
     * @return Pocket
     */
    public function getPocketInstance() {
        $params = array(
            // TODO: put this in the settings page
            'consumerKey' => '80531-839593e7dab50e8db88190ef'
        );

        if (empty($params['consumerKey'])) {
            wp_die('Please fill in your Pocket App Consumer Key');
        }

        return (new Pocket($params));
    }

    public function save_metabox( $post_id, $post ) {

    }

}