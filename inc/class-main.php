<?php

/**
 * Class Main Function
 * @package whello-moosend
 * @since 1.0..0 
 */

class WHMainFunction {

	/**
	 * Declare variables
	 * @var string
	 */
	private $moosend;
	private $regdata;
	private $apiKey;
	private $condition;
	private $roles;
	private $excludeRoles;

	/**
	 * Init class constructor
	 */
	public function __construct(){
		$this->moosend = new WHMoosendApi();
		$this->apiKey = get_option('api_key');
		$this->condition = get_option('select_condition');
		$this->excludeRoles = explode(',', get_option('role_exclude'));
		add_action( 'admin_menu', array($this, 'wm_admin_page'));
		add_action( 'admin_init', array($this, 'display_setting_field'));
		$this->regdata = get_option('user_reg_data');

		if($this->regdata == 'yes'){
			add_action('wpuf_after_register', array($this, 'new_subscriber_crindel'), 99);
		}

		if(get_option('integrate_wpcf') == 'yes'){
			add_action('wpcf7_before_send_mail', array($this, 'trigger_wpcf7'));
		}

		add_action('mc4wp_form_subscribed', array($this, 'mailchimp_send'));
	}

	/**
	 * Get available roles
	 * @return array
	 */
	public function get_role_list(){
		$temp = array();
		foreach (get_editable_roles() as $role_name => $role_info){
			$temp[] = $role_name;
		}

		$this->roles = array_diff($temp, ['administrator']);

		return $this->roles;
	}

	/**
	 * Get mailing list array
	 * @return array
	 */
	public function get_list(){
		$temp = array();

		if(!empty($this->apiKey)){
			$list = $this->moosend->getCacheData();

			if(!empty($list)){
				foreach ($list as $d) {
					$temp[$d['ID']] = $d['Name'];
				}
			} else {
				$temp['nodata'] = 'No mailing list data';
			}
		}

		return $temp;
	}

	/**
	 * Create amdin page
	 * @callback wm_admin_page_display
	 */
	public function wm_admin_page(){
		add_menu_page( 
			'Whello Moosend', 
			'Whello Moosend',  
			'manage_options', 
			'wm_settings', 
			array($this, 'wm_admin_page_display')
		);
	}

	/**
	 * Display admin page
	 * @include partials/admin-page.php
	 */
	public function wm_admin_page_display(){
		include_once( WM_PLUGIN_DIR . 'partials/admin-page.php');
	}

	/**
	 * Settings field admin page
	 * @var array
	 */
	public function display_setting_field(){
		add_settings_section('wm_field', 'General Settings', null, 'wm_settings');
		
		// Default field
		$fields = array(
			array(
	            'uid' => 'api_key',
	            'label' => 'Moosend api key',
	            'section' => 'wm_field',
	            'type' => 'text',
	            'options' => false,
	            'placeholder' => 'XXXX-XXXX-XXXX-XXXX',
	            'helper' => '',
	            'supplemental' => 'Moosend api key to access and get request authentication',
	            'default' => ''
	        )
		);

		// Check if API key installed
		if(!empty($this->apiKey)){

		    $fields[] = array(
		        'uid' => 'select_condition',
		        'label' => 'Mailing list condition',
		        'section' => 'wm_field',
		        'type' => 'select',
		        'options' => array(
		        	'global' => 'Use for global mailing list',
		        	'per_role' => 'Use per role mailing list'
		        ),
		        'placeholder' => 'Mailing List Item',
		        'helper' => '',
		        'supplemental' => 'Select condition for mailing list',
		        'default' => ''
		    );
		    
		    // Check condition for mailing list selection 
		    if($this->condition == 'global'){

		    	$fields[] = array(
			        'uid' => 'select_mail_list',
			        'label' => 'Select mailing list',
			        'section' => 'wm_field',
			        'type' => 'select',
			        'options' => $this->get_list(),
			        'placeholder' => 'Mailing List Item',
			        'helper' => '',
			        'supplemental' => 'Select mailing list that to be used',
			        'default' => ''
			    );

		    } elseif ($this->condition == 'per_role') {

		    	$fields[] = array(
		            'uid' => 'role_exclude',
		            'label' => 'Exclude role',
		            'section' => 'wm_field',
		            'type' => 'textarea',
		            'options' => false,
		            'placeholder' => '',
		            'helper' => '',
		            'supplemental' => 'Select role to exclude. Using comma to select multiple
		            	<br/><strong>Role available: ' . implode(', ', $this->get_role_list()) . '</strong>',
		            'default' => ''
		        );

		        $temp = array_diff($this->get_role_list(), $this->excludeRoles) ? 
		        		array_diff($this->get_role_list(), $this->excludeRoles) : 
		        		$this->get_role_list();

		    	foreach($temp as $role_name){
					$fields[] = array(
				        'uid' => 'select_role_' . $role_name,
				        'label' => 'Select mailing list for ' .$role_name,
				        'section' => 'wm_field',
				        'type' => 'select',
				        'options' => $this->get_list(),
				        'placeholder' => 'Mailing List Item',
				        'helper' => '',
				        'supplemental' => 'Select mailing list that to be used for '.$role_name,
				        'default' => ''
				    );
				}
		    }

		    $fields[] = array(
		        'uid' => 'user_reg_data',
		        'label' => 'Add subscriber during user registration?',
		        'section' => 'wm_field',
		        'type' => 'select',
		        'options' => array(
		        	'yes' => 'Yes',
		        	'no' => 'No'
		        ),
		        'placeholder' => 'Mailing List Item',
		        'helper' => '',
		        'supplemental' => 'Add subscriber to mailing list when new user register',
		        'default' => 'no'
			);
			
			if(class_exists('MC4WP_MailChimp')){

				$fields[] = array(
					'uid' => 'integrate_mailchimp',
					'label' => 'Integrated with mailchimp?',
					'section' => 'wm_field',
					'type' => 'select',
					'options' => array(
						'yes' => 'Yes',
						'no' => 'No'
					),
					'placeholder' => 'Mailchimp',
					'helper' => '',
					'supplemental' => 'Add subscriber from mailchimp email form',
					'default' => 'no'
				);

				$fields[] = array(
			        'uid' => 'mc4wp_mail_list',
			        'label' => 'Select mailing list for mailchimp',
			        'section' => 'wm_field',
			        'type' => 'select',
			        'options' => $this->get_list(),
			        'placeholder' => 'Mailing List Item',
			        'helper' => '',
			        'supplemental' => 'Select mailing list that to be used',
			        'default' => ''
			    );


				

			}

			if(class_exists('WPCF7')){
				$fields[] = array(
					'uid' => 'integrate_wpcf',
					'label' => 'Integrated with Contact Form 7?',
					'section' => 'wm_field',
					'type' => 'select',
					'options' => array(
						'yes' => 'Yes',
						'no' => 'No'
					),
					'placeholder' => 'WPCF7',
					'helper' => '',
					'supplemental' => 'Add subscriber from contact form 7',
					'default' => 'no'
				);

				if(get_option('integrate_wpcf') == 'yes'){

					$fields[] = array(
				        'uid' => 'wpcf_mailing',
				        'label' => 'Select mailing list for WPCF7',
				        'section' => 'wm_field',
				        'type' => 'select',
				        'options' => $this->get_list(),
				        'placeholder' => 'Mailing List Item',
				        'helper' => '',
				        'supplemental' => 'Select mailing list that to be used for wpcf7',
				        'default' => ''
				    );

				    $fields[] = array(
			            'uid' => 'wpcf7_form_id',
			            'label' => 'WPCF7 form ID',
			            'section' => 'wm_field',
			            'type' => 'text',
			            'options' => false,
			            'placeholder' => '',
			            'helper' => '',
			            'supplemental' => 'Select wpcf7 form ID',
			            'default' => ''
			        );
				}
			}
		}

		foreach( $fields as $field ){
	    	add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'wm_settings', $field['section'], $field );
	    	register_setting( 'wm_field', $field['uid'] );
	    }
	}

	/**
	 * Field callback
	 * @param  string
	 * @return string
	 */
	public function field_callback($arguments){
		$value = get_option( $arguments['uid'] );
		if( ! $value ) { 
	        $value = $arguments['default']; 
	    }

	    switch( $arguments['type'] ){
	        case 'text': 
	            printf( '<input class="settings-input" name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
	        	break;
	        case 'textarea':
		        printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
		        break;
		    case 'editor':
		        wp_editor( $value, $arguments['uid'], $settings );
		        break;
	        case 'select': 
		        if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
		            $options_markup = '';
		            foreach( $arguments['options'] as $key => $label ){
		                $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
		            }
		            printf( '<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $options_markup );
		        }
		        break;
	    }

	    if( $helper = $arguments['helper'] ){
	        printf( '<span class="helper"> %s</span>', $helper ); 
	    }

	    if( $supplimental = $arguments['supplemental'] ){
	        printf( '<p class="description">%s</p>', $supplimental ); 
	    }
	}

	/**
	 * Create new subscriber
	 * only accept 1 argument
	 * @param  string
	 * @return object
	 */
	public function new_subscriber($user_id){

		$user_meta = get_userdata($user_id);
		$user_roles = $user_meta->roles[0];

		$body = array(
			'Email' => $_POST['user_email'],
			'Name' => $user_roles
		);

		$this->moosend->createNewSubsciber($body, $user_roles);
	}

	/**
	 * Create new subscriber for crindel
	 * only accept 1 argument
	 * @param  string
	 * @return object
	 */
	public function new_subscriber_crindel($user_id){

		$user_meta = get_userdata($user_id);
		$user_roles = $user_meta->roles;
		
		$email = $user_meta->user_email;
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		if ( in_array( 'standard_user', $user_roles, true ) ) {
			$role = 'standard_user';
		} else if ( in_array( 'specialist_user', $user_roles, true ) ) {
			$role = 'specialist_user';
		}
		
		if($this->condition == 'global'){

			$mailingList = get_option('select_mail_list');

		} elseif ($this->condition == 'per_role') {
			$mailingList = get_option('select_role_' . $role);
		}

		if ( $role == 'standard_user' ) {
			$body = array(
				'Email' => $_POST['user_email'],
				'Name' => $_POST['first_name'] . ' ' . $_POST['last_name']
			);

		} else if ( $role == 'specialist_user' ) {
			$body = array(
				'Email' => $_POST['user_email'],
				'Name' => $_POST['first_name'] . ' ' . $_POST['last_name'],
				'CustomFields' => array(
					'Address=' . $_POST['address'],
					'Category='. $_POST['specialist_branch'] ,
					'Company=' . $_POST['company_name'],
					'KVK=' . $_POST['kvk_number'],
					'Phone=' . $_POST['phone_number'],
					'Postcode='. $_POST['postal_code'],
					'Speciality=' . $_POST['specialist_sub_branch']
				)
			);
		}

		$this->moosend->createNewSubsciber($body, $mailingList );
		
	}

	/**
	 * Create new subscriber from wpcf7
	 * only accept 1 argument
	 * @param  string
	 * @return object
	 */
	public function trigger_wpcf7($wpcf7){
		$submitted = WPCF7_Submission::get_instance();
		if($submitted){
			$form_data = $submitted->get_posted_data();
		}

		if($wpcf7->id() == get_option('wpcf7_form_id')){

			$moo_id = get_option('wpcf_mailing');

			$body = array(
				'Email' => $form_data['your-email'],
				'Name' => $form_data['your-first-name'] . ' ' .  $form_data['your-first-name'],
				'CustomFields' => array(
					'Address=' . $form_data['your-address'],
					'Postcode=' . $form_data['your-postcode'],
					'KVK=' . $form_data['your-kvk-nummer'],
					'Company=' . $form_data['your-company'],
					'CRM=' . $form_data['your-crm-system'],
					'Phone=' . $form_data['your-phone']
				)
			);

			$this->moosend->createNewSubsciber($body, $moo_id);
		}
	}

	public function mailchimp_send($form){
		$data = $form->get_data();
		

		$body = array(
			'Email' => $data['EMAIL'],
			'Name' => $data['EMAIL']
		);

		$list = get_option('mc4wp_mail_list');

		$this->moosend->createNewSubsciber($body, $list);
		
		# wp_redirect('/thank-you-newsletter/');
		//var_dump($data);
	}

}