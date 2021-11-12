<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


/**
 * Class Disciple_Tools_Magic_Links_Magic_User_App
 */
class Disciple_Tools_Magic_Links_Magic_User_App extends DT_Magic_Url_Base {

    public $page_title = 'User Contact Updates';
    public $page_description = 'An update summary of assigned contacts.';
    public $root = "smart_links"; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'user_contacts_updates'; // @todo define the type
    public $post_type = 'user';
    private $meta_key = '';

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * user_app and module section
         */
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( ! $this->check_parts_match() ) {
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );

    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
        return $allowed_css;
    }

    public function dt_settings_apps_list( $apps_list ) {
        $field_settings = DT_Posts::get_post_field_settings( 'contacts' );

        $apps_list[ $this->meta_key ] = [
            'key'         => $this->meta_key,
            'url_base'    => $this->root . '/' . $this->type,
            'label'       => $this->page_title,
            'description' => $this->page_description,
            'meta'        => [
                'app_type'  => 'magic_link',
                'post_type' => $this->post_type,
                'fields'    => [
                    [
                        'id'    => 'name',
                        'label' => $field_settings['name']['name']
                    ],
                    [
                        'id'    => 'milestones',
                        'label' => $field_settings['milestones']['name']
                    ],
                    [
                        'id'    => 'overall_status',
                        'label' => $field_settings['overall_status']['name']
                    ],
                    [
                        'id'    => 'faith_status',
                        'label' => $field_settings['faith_status']['name']
                    ],
                    [
                        'id'    => 'contact_phone',
                        'label' => $field_settings['contact_phone']['name']
                    ],
                    [
                        'id'    => 'comments',
                        'label' => __( 'Comments', 'disciple_tools' ) // Special Case!
                    ]
                ]
            ]
        ];

        return $apps_list;
    }

    /**
     * Writes custom styles to header
     *
     * @see DT_Magic_Url_Base()->header_style() for default state
     * @todo remove if not needed
     */
    public function header_style() {
        ?>
        <style>
            body {
                background-color: white;
                padding: 1em;
            }

            .api-content-div-style {
                height: 300px;
                overflow-x: hidden;
                overflow-y: scroll;
                text-align: left;
            }

            .api-content-table tbody {
                border: none;
            }

            .api-content-table tr {
                cursor: pointer;
                background: #ffffff;
                padding: 0px;
            }

            .api-content-table tr:hover {
                background-color: #f5f5f5;
            }
        </style>
        <?php
    }

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     * @todo remove if not needed
     */
    public function header_javascript() {
        ?>
        <script></script>
        <?php
    }

    /**
     * Extract incoming link specific parameters; E.g. link object id...
     */
    public function fetch_incoming_link_param( $param ) {
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            parse_str( parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_QUERY ), $link_params );

            return $link_params[ $param ] ?? '';
        }
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     * @todo remove if not needed
     */
    public function footer_javascript() {
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'map_key'        => DT_Mapbox_API::get_key(),
                'root'           => esc_url_raw( rest_url() ),
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'parts'          => $this->parts,
                'milestones'     => DT_Posts::get_post_field_settings( 'contacts' )['milestones']['default'],
                'overall_status' => DT_Posts::get_post_field_settings( 'contacts' )['overall_status']['default'],
                'faith_status'   => DT_Posts::get_post_field_settings( 'contacts' )['faith_status']['default'],
                'link_obj_id'    => Disciple_Tools_Magic_Links_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'translations'   => [
                    'add' => __( 'Add Magic', 'disciple-tools-magic-links' ),
                ],
            ] ) ?>][0]

            // Fetch assigned contacts
            window.get_magic = () => {
                jQuery.ajax({
                    type: "GET",
                    data: {
                        action: 'get',
                        parts: jsObject.parts
                    },
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                    }
                })
                    .done(function (data) {
                        window.load_magic(data)
                    })
                    .fail(function (e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
            };

            // Display returned list of assigned contacts
            window.load_magic = (data) => {
                let content = jQuery('#api-content');
                let table = jQuery('.api-content-table');
                let total = jQuery('#total');
                let spinner = jQuery('.loading-spinner');

                // Remove any previous entries
                table.find('tbody').empty()

                // Set total hits count
                total.html(data['total'] ? data['total'] : '0');

                // Iterate over returned posts
                if (data['posts']) {
                    data['posts'].forEach(v => {

                        let html = `<tr onclick="get_assigned_contact_details('${window.lodash.escape(v.id)}', '${window.lodash.escape(v.name)}');">
                                <td>${window.lodash.escape(v.name)}</td>
                            </tr>`;

                        table.find('tbody').append(html);

                    });
                }
            };

            // Fetch assigned contacts
            window.get_magic();

            // Fetch requested contact details
            window.get_contact = (post_id) => {
                let comment_count = 2;

                jQuery('.form-content-table').fadeOut('fast', function () {

                    // Dispatch request call
                    jQuery.ajax({
                        type: "GET",
                        data: {
                            action: 'get',
                            parts: jsObject.parts,
                            id: post_id,
                            comment_count: comment_count,
                            ts: moment().unix() // Alter url shape, so as to force cache refresh!
                        },
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/post',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce);
                            xhr.setRequestHeader('Cache-Control', 'no-store');
                        }

                    }).done(function (data) {

                        // Was our post fetch request successful...?
                        if (data['success'] && data['post']) {

                            // Display submit button
                            jQuery('#content_submit_but').fadeIn('fast');

                            // ID
                            jQuery('#post_id').val(data['post']['ID']);

                            // NAME
                            if (window.is_field_enabled('name')) {
                                jQuery('#form_content_name_td').html(`
                                <input id="post_name" type="text" value="${window.lodash.escape(data['post']['name'])}" />
                                `);
                            } else {
                                jQuery('#form_content_name_tr').hide();
                            }

                            // MILESTONES
                            if (window.is_field_enabled('milestones')) {
                                let html_milestones = ``;
                                jQuery.each(jsObject.milestones, function (idx, milestone) {

                                    // Determine button selection state
                                    let button_select_state = 'empty-select-button';
                                    if (data['post']['milestones'] && (data['post']['milestones'].indexOf(idx) > -1)) {
                                        button_select_state = 'selected-select-button';
                                    }

                                    // Build button widget
                                    html_milestones += `<button id="${window.lodash.escape(idx)}"
                                                            type="button"
                                                            data-field-key="milestones"
                                                            class="dt_multi_select ${button_select_state} button select-button">
                                                        <img class="dt-icon" src="${window.lodash.escape(milestone['icon'])}"/>
                                                        ${window.lodash.escape(milestone['label'])}
                                                    </button>`;
                                });
                                jQuery('#form_content_milestones_td').html(html_milestones);

                                // Respond to milestone button state changes
                                jQuery('.dt_multi_select').on("click", function (evt) {
                                    let milestone = jQuery(evt.currentTarget);
                                    if (milestone.hasClass('empty-select-button')) {
                                        milestone.removeClass('empty-select-button');
                                        milestone.addClass('selected-select-button');
                                    } else {
                                        milestone.removeClass('selected-select-button');
                                        milestone.addClass('empty-select-button');
                                    }
                                });
                            } else {
                                jQuery('#form_content_milestones_tr').hide();
                            }

                            // OVERALL_STATUS
                            if (window.is_field_enabled('overall_status')) {
                                let html_overall_status = `<select id="post_overall_status" class="select-field">`;
                                jQuery.each(jsObject.overall_status, function (idx, overall_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['overall_status'] && (String(data['post']['overall_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_overall_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(overall_status['label'])}</option>`;
                                });
                                html_overall_status += `</select>`;
                                jQuery('#form_content_overall_status_td').html(html_overall_status);
                            } else {
                                jQuery('#form_content_overall_status_tr').hide();
                            }

                            // FAITH_STATUS
                            if (window.is_field_enabled('faith_status')) {
                                let html_faith_status = `<select id="post_faith_status" class="select-field">`;
                                html_faith_status += `<option value=""></option>`;
                                jQuery.each(jsObject.faith_status, function (idx, faith_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['faith_status'] && (String(data['post']['faith_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_faith_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(faith_status['label'])}</option>`;
                                });
                                html_faith_status += `</select>`;
                                jQuery('#form_content_faith_status_td').html(html_faith_status);
                            } else {
                                jQuery('#form_content_faith_status_tr').hide();
                            }

                            // CONTACT_PHONE
                            if (window.is_field_enabled('contact_phone')) {
                                if (data['post']['contact_phone']) {

                                    let phone_numbers = [];
                                    data['post']['contact_phone'].forEach(phone => {
                                        phone_numbers.push(phone['value']);
                                    });

                                    jQuery('#form_content_contact_phone_td').html(phone_numbers.length > 0 ? phone_numbers.join(', ') : '');
                                }
                            } else {
                                jQuery('#form_content_contact_phone_tr').hide();
                            }

                            // COMMENTS
                            if (window.is_field_enabled('comments')) {
                                let counter = 0;
                                let html_comments = `<textarea></textarea><br>`;
                                if (data['comments']['comments']) {
                                    data['comments']['comments'].forEach(comment => {
                                        if (counter++ < comment_count) { // Enforce comment count limit..!
                                            html_comments += `<b>${window.lodash.escape(comment['comment_author'])} @ ${window.lodash.escape(comment['comment_date'])}</b><br>`;
                                            html_comments += `${window.lodash.escape(comment['comment_content'])}<hr>`;
                                        }
                                    });
                                }
                                jQuery('#form_content_comments_td').html(html_comments);
                            } else {
                                jQuery('#form_content_comments_tr').hide();
                            }

                            // Display updated post fields
                            jQuery('.form-content-table').fadeIn('fast');

                        } else {
                            // TODO: Error Msg...!
                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                    });
                });
            };

            // Determine if field has been enabled
            window.is_field_enabled = (field_id) => {

                // Enabled by default
                let enabled = true;

                // Iterate over type field settings
                if (jsObject.link_obj_id['type_fields']) {
                    jsObject.link_obj_id['type_fields'].forEach(field => {

                        // Is matched field enabled...?
                        if (String(field['id']) === String(field_id)) {
                            enabled = field['enabled'];
                        }
                    });
                }

                return enabled;
            }

            // Handle fetch request for contact details
            window.get_assigned_contact_details = (post_id, post_name) => {
                let contact_name = jQuery('#contact_name');

                // Update contact name
                contact_name.html(post_name);

                // Fetch requested contact details
                window.get_contact(post_id);
            };

            // Submit contact details
            jQuery('#content_submit_but').on("click", function () {
                let id = jQuery('#post_id').val();

                // Reset error message field
                let error = jQuery('#error');
                error.html('');

                // Sanity check content prior to submission
                if (!id || String(id).trim().length === 0) {
                    error.html('Invalid post id detected!');

                } else {

                    // Build payload accordingly, based on enabled states
                    let payload = {
                        action: 'get',
                        parts: jsObject.parts,
                        id: id
                    }
                    if (window.is_field_enabled('name')) {
                        payload['name'] = String(jQuery('#post_name').val()).trim();
                    }
                    if (window.is_field_enabled('milestones')) {
                        let milestones = [];
                        jQuery('#form_content_milestones_td button').each(function () {
                            milestones.push({
                                'value': jQuery(this).attr('id'),
                                'delete': jQuery(this).hasClass('empty-select-button')
                            });
                        });

                        payload['milestones'] = milestones;
                    }
                    if (window.is_field_enabled('overall_status')) {
                        payload['overall_status'] = String(jQuery('#post_overall_status').val()).trim();
                    }
                    if (window.is_field_enabled('faith_status')) {
                        payload['faith_status'] = String(jQuery('#post_faith_status').val()).trim();
                    }
                    if (window.is_field_enabled('contact_phone')) {
                        // Ignored, as field currently shown in a read-only capacity!
                    }
                    if (window.is_field_enabled('comments')) {
                        payload['comments'] = jQuery('#form_content_comments_td').find('textarea').eq(0).val();
                    }

                    // Submit data for post update
                    jQuery('#content_submit_but').prop('disabled', true);

                    jQuery.ajax({
                        type: "GET",
                        data: payload,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/update',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                        }

                    }).done(function (data) {

                        // If successful, refresh page, otherwise; display error message
                        if (data['success']) {
                            window.location.reload();

                        } else {
                            jQuery('#error').html(data['message']);
                            jQuery('#content_submit_but').prop('disabled', false);
                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                        jQuery('#content_submit_but').prop('disabled', false);
                    });
                }
            });
        </script>
        <?php
        return true;
    }

    public function body() {
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"><b><?php echo esc_attr( $this->page_title ); ?></b></h2>
                </div>
            </div>
            <hr>
            <div id="content">
                <h3><?php esc_html_e( "ASSIGNED CONTACTS", 'disciple_tools' ) ?> [ <span id="total">0</span> ]</h3>
                <hr>
                <div class="grid-x api-content-div-style" id="api-content">
                    <table class="api-content-table">
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>
                <br>

                <h3><?php esc_html_e( "CONTACT DETAILS", 'disciple_tools' ) ?> [ <span id="contact_name">---</span> ]
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"/>
                    <?php $field_settings = DT_Posts::get_post_field_settings( 'contacts' ); ?>
                    <table style="display: none;" class="form-content-table">
                        <tbody>
                        <tr id="form_content_name_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['name']['name'] ); ?></b></td>
                            <td id="form_content_name_td"></td>
                        </tr>
                        <tr id="form_content_milestones_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['milestones']['name'] ); ?></b></td>
                            <td id="form_content_milestones_td"></td>
                        </tr>
                        <tr id="form_content_overall_status_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['overall_status']['name'] ); ?></b></td>
                            <td id="form_content_overall_status_td"></td>
                        </tr>
                        <tr id="form_content_faith_status_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['faith_status']['name'] ); ?></b></td>
                            <td id="form_content_faith_status_td"></td>
                        </tr>
                        <tr id="form_content_contact_phone_tr">
                            <td style="vertical-align: top;">
                                <b><?php echo esc_attr( $field_settings['contact_phone']['name'] ); ?></b></td>
                            <td id="form_content_contact_phone_td"></td>
                        </tr>
                        <tr id="form_content_comments_tr">
                            <td style="vertical-align: top;"><b>Comments</b></td>
                            <td id="form_content_comments_td"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but" style="display: none; min-width: 100%;" class="button select-button">
                    Submit
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/' . $this->type, [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'endpoint_get' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/post', [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'get_post' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/update', [
                [
                    'methods'             => "GET",
                    'callback'            => [ $this, 'update_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function endpoint_get( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params  = dt_recursive_sanitize_array( $params );
        $user_id = $params["parts"]["post_id"];

        // Fetch all assigned posts
        $data = [];
        if ( ! empty( $user_id ) ) {

            // Update logged-in user state as required
            $original_user = wp_get_current_user();
            wp_set_current_user( $user_id );

            // Fetch all assigned posts
            $posts = DT_Posts::list_posts( 'contacts', [
                'limit'  => 1000,
                'fields' => [
                    [
                        'assigned_to' => [ 'me' ],
                        "subassigned" => [ 'me' ]
                    ],
                    "overall_status" => [
                        "new",
                        "unassigned",
                        "assigned",
                        "active"
                    ]
                ]
            ] );

            // Revert to original user
            if ( ! empty( $original_user ) && isset( $original_user->ID ) ) {
                wp_set_current_user( $original_user->ID );
            }

            // Iterate and return valid posts
            if ( ! empty( $posts ) && isset( $posts['posts'], $posts['total'] ) ) {
                $data['total'] = $posts['total'];
                foreach ( $posts['posts'] ?? [] as $post ) {
                    $data['posts'][] = [
                        'id'   => $post['ID'],
                        'name' => $post['name']
                    ];
                }
            }
        }

        return $data;
    }

    public function get_post( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['id'], $params['parts'], $params['action'], $params['comment_count'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params  = dt_recursive_sanitize_array( $params );
        $user_id = $params["parts"]["post_id"];

        // Update logged-in user state if required
        if ( ! is_user_logged_in() ) {
            wp_set_current_user( $user_id );
        }

        // Fetch corresponding contacts post record
        $response = [];
        $post     = DT_Posts::get_post( 'contacts', $params['id'], false );
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
            $response['success']  = true;
            $response['post']     = $post;
            $response['comments'] = DT_Posts::get_post_comments( 'contacts', $params['id'], false, 'all', [ 'number' => $params['comment_count'] ] );
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['id'], $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing core parameters", [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params  = dt_recursive_sanitize_array( $params );
        $user_id = $params["parts"]["post_id"];

        // Update logged-in user state if required
        if ( ! is_user_logged_in() ) {
            wp_set_current_user( $user_id );
        }

        // Capture name, if present
        $updates = [];
        if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
            $updates['name'] = $params['name'];
        }

        // Capture overall status
        if ( isset( $params['overall_status'] ) && ! empty( $params['overall_status'] ) ) {
            $updates['overall_status'] = $params['overall_status'];
        }

        // Capture faith status
        if ( isset( $params['faith_status'] ) ) {
            $updates['faith_status'] = $params['faith_status'];
        }

        // Capture milestones
        if ( isset( $params['milestones'] ) ) {
            $milestones = [];
            foreach ( $params['milestones'] ?? [] as $milestone ) {
                $entry          = [];
                $entry['value'] = $milestone['value'];
                if ( strtolower( trim( $milestone['delete'] ) ) === 'true' ) {
                    $entry['delete'] = true;
                }
                $milestones[] = $entry;
            }
            if ( ! empty( $milestones ) ) {
                $updates['milestones'] = [
                    'values' => $milestones
                ];
            }
        }

        // Update specified post record
        $updated_post = DT_Posts::update_post( 'contacts', $params['id'], $updates, false, false );
        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'success' => false,
                'message' => 'Unable to update contact record details!'
            ];
        }

        // Add any available comments
        if ( isset( $params['comments'] ) && ! empty( $params['comments'] ) ) {
            $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $params['comments'], 'comment', [], false );
            if ( empty( $updated_comment ) || is_wp_error( $updated_comment ) ) {
                return [
                    'success' => false,
                    'message' => 'Unable to add comment to contact record details!'
                ];
            }
        }

        // Finally, return successful response
        return [
            'success' => true,
            'message' => ''
        ];
    }
}

Disciple_Tools_Magic_Links_Magic_User_App::instance();
