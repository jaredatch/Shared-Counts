<?php
/**
 * Admin class.
 *
 * Contains functionality for the admin dashboard (is_admin()).
 *
 * @package    SharedCounts
 * @author     Bill Erickson & Jared Atchison
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019
 */
class Shared_Counts_Admin {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Plugin settings.
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'admin_menu', [ $this, 'settings_add' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'settings_assets' ] );
		add_filter( 'plugin_action_links_' . SHARED_COUNTS_BASE, [ $this, 'settings_link' ] );
		add_filter( 'plugin_row_meta',  [ $this, 'author_links' ], 10, 2 );

		// Post Listing Column.
		$options = $this->options();
		if ( ! empty( $options['post_type'] ) ) {
			foreach ( $options['post_type'] as $post_type ) {
				add_filter( 'manage_edit-' . $post_type . '_columns', [ $this, 'add_shared_count_column' ] );
				add_action( 'manage_' . $post_type . '_pages_custom_column', [ $this, 'shared_count_column' ], 10, 2 );
				add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'shared_count_column' ], 10, 2 );
				add_filter( 'manage_edit-' . $post_type . '_sortable_columns', [ $this, 'shared_count_sortable_column' ] );
			}
			add_action( 'pre_get_posts', [ $this, 'sort_column_query' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'column_style' ] );
		}

		// Post metabox.
		add_action( 'admin_init', [ $this, 'metabox_add' ] );
		add_action( 'wp_ajax_shared_counts_refresh', [ $this, 'metabox_ajax' ] );
		add_action( 'wp_ajax_shared_counts_delete', [ $this, 'metabox_ajax_delete' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'metabox_assets' ] );
		add_action( 'save_post', [ $this, 'metabox_save' ], 10, 2 );

		// Dashboard Widget.
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
	}

	// ********************************************************************** //
	//
	// Settings - these methods wrangle our settings and related functionality.
	//
	// ********************************************************************** //

	/**
	 * Return the settings options values.
	 *
	 * Used globally. Options are filterable.
	 *
	 * @since 1.0.0
	 */
	public function options() {

		$options = get_option( 'shared_counts_options', $this->settings_default() );

		return apply_filters( 'shared_counts_options', $options );
	}

	/**
	 * Initialize the Settings page options.
	 *
	 * @since 1.0.0
	 */
	public function settings_init() {

		register_setting( 'shared_counts_options', 'shared_counts_options', [ $this, 'settings_sanitize' ] );
	}

	/**
	 * Add the Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_add() {

		add_options_page( __( 'Shared Counts Settings', 'shared-counts' ), __( 'Shared Counts', 'shared-counts' ), 'manage_options', 'shared_counts_options', [ $this, 'settings_page' ] );
	}

	/**
	 * Build the Settings page.
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {

		?>
		<div class="wrap">

			<svg style="width: 100%; max-width: 400px;" xmlns="http://www.w3.org/2000/svg" width="617" height="86" viewBox="0 0 617 86">
				<g fill="none" fill-rule="evenodd">
					<path fill="#3B5897" d="M61.2143544,48.2014589 L53.876999,48.2014589 L53.876999,34.0405564 C53.876999,32.3206958 53.913546,30.7470233 53.9887899,29.3216889 C53.5373265,29.8698944 52.9740721,30.4417481 52.3033265,31.0415495 L49.2656226,33.5482463 L45.5141767,28.9347202 L54.7003822,21.4533265 L61.2143544,21.4533265 L61.2143544,48.2014589 Z M43.6008317,37.4437307 L36.7923334,37.4437307 L36.7923334,44.0479955 L31.8713822,44.0479955 L31.8713822,37.4437307 L25.0671836,37.4437307 L25.0671836,32.539978 L31.8713822,32.539978 L31.8713822,25.8260721 L36.7923334,25.8260721 L36.7923334,32.539978 L43.6008317,32.539978 L43.6008317,37.4437307 Z M83.9681105,0.000214982578 L8.47482822,0.000214982578 C3.80110697,0.000214982578 0.000214982578,3.80325679 0.000214982578,8.47482822 L0.000214982578,59.318208 C0.000214982578,63.9897794 3.80110697,67.7906714 8.47482822,67.7906714 L43.0999223,67.7906714 L64.0929711,84.5851105 C64.6798735,85.0537725 65.3850164,85.294553 66.0987585,85.294553 C66.5760199,85.294553 67.055431,85.1870617 67.5047446,84.9699293 C68.6312533,84.4281732 69.3320965,83.3124136 69.3320965,82.0633648 L69.3320965,67.7906714 L83.9681105,67.7906714 C88.6396819,67.7906714 92.4427237,63.9897794 92.4427237,59.318208 L92.4427237,8.47482822 C92.4427237,3.80325679 88.6396819,0.000214982578 83.9681105,0.000214982578 Z"/>
					<path fill="#000" d="M156.53634,51.7663 C156.53634,56.6851014 154.767033,60.5612373 151.22627,63.390408 C147.687657,66.2238784 142.762406,67.6406136 136.452667,67.6406136 C130.639538,67.6406136 125.497155,66.5463523 121.025517,64.3599794 L121.025517,53.6280491 C124.70172,55.2683662 127.814667,56.4249725 130.360061,57.0957181 C132.907605,57.7643139 135.235866,58.0996868 137.346995,58.0996868 C139.881639,58.0996868 141.825082,57.615976 143.179472,56.6485544 C144.531713,55.6789829 145.208908,54.2364498 145.208908,52.3252547 C145.208908,51.2589411 144.912232,50.3065683 144.31458,49.4745857 C143.719078,48.6426031 142.844099,47.8428679 141.689643,47.0710805 C140.533037,46.3014429 138.178977,45.0717425 134.627465,43.3841293 C131.299535,41.8169063 128.803587,40.3163279 127.137472,38.8737948 C125.473507,37.4334115 124.144915,35.7565474 123.151695,33.8432024 C122.158476,31.9298575 121.659716,29.6940387 121.659716,27.135746 C121.659716,22.3179864 123.293584,18.5299934 126.561319,15.7717669 C129.826904,13.0156902 134.341538,11.6355021 140.103071,11.6355021 C142.936542,11.6355021 145.638873,11.9708749 148.210064,12.6416206 C150.779106,13.3123662 153.468538,14.2582895 156.276211,15.4729411 L152.550563,24.4549132 C149.643998,23.2617599 147.240493,22.4297774 145.340047,21.9568157 C143.439601,21.4860038 141.569253,21.249523 139.733301,21.249523 C137.546929,21.249523 135.870064,21.7590317 134.702709,22.7780491 C133.535354,23.7949167 132.950601,25.1235091 132.950601,26.7638261 C132.950601,27.7828436 133.187082,28.6707216 133.657894,29.4274603 C134.130855,30.1863488 134.883294,30.9194394 135.913061,31.6267321 C136.944977,32.3340247 139.38288,33.6088714 143.235368,35.4448226 C148.328305,37.8805753 151.817472,40.3227774 153.705019,42.7671293 C155.592566,45.213631 156.53634,48.2147878 156.53634,51.7663 Z M198.934559,66.894839 L187.57058,66.894839 L187.57058,42.5631108 C187.57058,36.5521979 185.334761,33.5445916 180.863123,33.5445916 C177.681381,33.5445916 175.385367,34.625954 173.970782,36.7865289 C172.554047,38.9492537 171.844604,42.4513199 171.844604,47.2948774 L171.844604,66.894839 L160.480625,66.894839 L160.480625,8.91618746 L171.844604,8.91618746 L171.844604,20.7273303 C171.844604,21.6474557 171.756461,23.8080307 171.584475,27.2112049 L171.322197,30.5649331 L171.919848,30.5649331 C174.452343,26.4910132 178.476817,24.4551282 183.99327,24.4551282 C188.886273,24.4551282 192.601172,25.7708216 195.131517,28.4043582 C197.666162,31.0357449 198.934559,34.8129889 198.934559,39.7296404 L198.934559,66.894839 Z M232.05413,66.8959139 L229.854858,61.231123 L229.556032,61.231123 C227.644837,63.6410777 225.675597,65.3093425 223.650461,66.2423669 C221.625325,67.1732415 218.985339,67.6397537 215.732653,67.6397537 C211.731827,67.6397537 208.584482,66.4981962 206.286318,64.2107815 C203.988154,61.9255167 202.840147,58.6728303 202.840147,54.4484226 C202.840147,50.0283808 204.385872,46.7670951 207.479471,44.6688652 C210.570921,42.5684854 215.233893,41.4075794 221.470538,41.1861474 L228.700402,40.9604157 L228.700402,39.1330638 C228.700402,34.9129557 226.537677,32.799677 222.214377,32.799677 C218.888597,32.799677 214.973764,33.8057955 210.478478,35.8180324 L206.714133,28.1431544 C211.510395,25.6343077 216.826914,24.3788094 222.661541,24.3788094 C228.253238,24.3788094 232.537841,25.5977606 235.517499,28.0292136 C238.499308,30.4649662 239.989137,34.1669662 239.989137,39.1330638 L239.989137,66.8959139 L232.05413,66.8959139 Z M228.700402,47.5926282 L224.301858,47.7409662 C220.999726,47.842008 218.538175,48.4396596 216.923656,49.5296213 C215.309137,50.6238826 214.502952,52.2878477 214.502952,54.5236666 C214.502952,57.7290568 216.341053,59.330677 220.017255,59.330677 C222.650792,59.330677 224.755471,58.5717885 226.333444,57.0561613 C227.911416,55.542684 228.700402,53.530447 228.700402,51.0216003 L228.700402,47.5926282 Z M269.141849,24.4546983 C270.681125,24.4546983 271.960271,24.5664892 272.979289,24.7879213 L272.121508,35.4446077 C271.203532,35.1973777 270.083473,35.0726878 268.76778,35.0726878 C265.141024,35.0726878 262.316153,36.0057122 260.291017,37.8674613 C258.265881,39.7292105 257.255463,42.339099 257.255463,45.6928272 L257.255463,66.8944091 L245.891484,66.8944091 L245.891484,25.235085 L254.497236,25.235085 L256.1741,32.2413672 L256.733055,32.2413672 C258.022951,29.9066564 259.770759,28.0255589 261.967881,26.5959247 C264.167153,25.1684404 266.557759,24.4546983 269.141849,24.4546983 Z M295.886327,67.6397537 C289.181021,67.6397537 283.939745,65.7887537 280.164651,62.0867537 C276.387407,58.3847537 274.49986,53.1456282 274.49986,46.3650777 C274.49986,39.3845934 276.243369,33.9863808 279.732536,30.1725899 C283.223853,26.3609488 288.050212,24.4540533 294.211613,24.4540533 C300.097836,24.4540533 304.681264,26.1309174 307.959749,29.4846456 C311.238233,32.8383739 312.8764,37.4712484 312.8764,43.3832693 L312.8764,48.8954226 L286.012177,48.8954226 C286.136867,52.1266108 287.09354,54.6483564 288.882195,56.4606596 C290.67085,58.2751125 293.177547,59.182339 296.408735,59.182339 C298.917582,59.182339 301.290989,58.9200603 303.526808,58.3976526 C305.762627,57.8773948 308.097338,57.0432624 310.53094,55.9017049 L310.53094,64.6966422 C308.544501,65.6898617 306.420474,66.4294017 304.158857,66.9131125 C301.89939,67.3968233 299.141163,67.6397537 295.886327,67.6397537 Z M294.284707,32.5395481 C291.874752,32.5395481 289.987205,33.3027362 288.619916,34.8291125 C287.254777,36.3576387 286.47224,38.5268129 286.272306,41.3344854 L302.219714,41.3344854 C302.172418,38.5268129 301.439327,36.3576387 300.022592,34.8291125 C298.608007,33.3027362 296.694662,32.5395481 294.284707,32.5395481 Z M332.562785,67.6401836 C327.665482,67.6401836 323.823743,65.7397376 321.02682,61.9388456 C318.232046,58.1379537 316.836809,52.8730303 316.836809,46.139776 C316.836809,39.3076296 318.257844,33.9868108 321.102064,30.1730199 C323.948433,26.3613787 327.865416,24.4544833 332.859461,24.4544833 C338.100736,24.4544833 342.099412,26.4903683 344.855489,30.5642882 L345.229559,30.5642882 C344.657705,27.4577899 344.373928,24.6888143 344.373928,22.2552115 L344.373928,8.91554251 L355.774454,8.91554251 L355.774454,66.8941941 L347.05691,66.8941941 L344.855489,61.4916819 L344.373928,61.4916819 C341.789837,65.5913997 337.851356,67.6401836 332.562785,67.6401836 Z M336.548562,58.5851174 C339.455126,58.5851174 341.583454,57.7402359 342.937844,56.0504728 C344.292234,54.3628596 345.029625,51.4928422 345.154315,47.4425704 L345.154315,46.2150199 C345.154315,41.7433822 344.46637,38.537992 343.086182,36.600999 C341.708144,34.6640059 339.465876,33.6944345 336.361527,33.6944345 C333.826883,33.6944345 331.857642,34.7693474 330.455956,36.9170233 C329.05212,39.0668491 328.351276,42.190546 328.351276,46.2881139 C328.351276,50.3878317 329.058569,53.4620826 330.475304,55.5108666 C331.88989,57.5596505 333.912876,58.5851174 336.548562,58.5851174 Z M404.496816,21.248878 C400.152018,21.248878 396.783241,22.8827456 394.401234,26.148331 C392.014927,29.4160662 390.823924,33.9672474 390.823924,39.8061742 C390.823924,51.9526899 395.381554,58.0259477 404.496816,58.0259477 C408.321356,58.0259477 412.95638,57.0692753 418.395439,55.1559303 L418.395439,64.8451951 C413.925952,66.7090941 408.931906,67.6399686 403.417603,67.6399686 C395.493345,67.6399686 389.430837,65.2364634 385.232227,60.429453 C381.035767,55.6224425 378.935387,48.7236516 378.935387,39.7309303 C378.935387,34.0682892 379.967303,29.1043415 382.028986,24.8455366 C384.090669,20.5845819 387.053129,17.3189965 390.916366,15.0444808 C394.777453,12.7742648 399.304986,11.6348571 404.496816,11.6348571 C409.787537,11.6348571 415.104056,12.9161533 420.444223,15.4722962 L416.720725,24.864885 C414.68054,23.8931638 412.631756,23.0504321 410.570074,22.3302404 C408.508391,21.6100488 406.485405,21.248878 404.496816,21.248878 Z M461.14709,45.990578 C461.14709,52.7711286 459.358435,58.0768986 455.781125,61.8992889 C452.203815,65.7281286 447.224818,67.6393237 440.839836,67.6393237 C436.84116,67.6393237 433.313295,66.7643446 430.258393,65.0122366 C427.201341,63.2601286 424.855881,60.7448324 423.215564,57.4663481 C421.575247,54.1878638 420.756163,50.3633237 420.756163,45.990578 C420.756163,39.1842296 422.534069,33.8935084 426.085581,30.1162645 C429.637093,26.3411704 434.628989,24.4536233 441.065567,24.4536233 C445.062093,24.4536233 448.592107,25.3243028 451.64701,27.061362 C454.699762,28.8005711 457.049522,31.2965188 458.687689,34.5513551 C460.328006,37.8061913 461.14709,41.6199822 461.14709,45.990578 Z M432.345874,45.990578 C432.345874,50.1139439 433.020919,53.2311913 434.375309,55.34447 C435.7297,57.455599 437.935421,58.5090136 440.990323,58.5090136 C444.019428,58.5090136 446.199351,57.4620484 447.527944,55.3616686 C448.858686,53.2634387 449.520832,50.1397418 449.520832,45.990578 C449.520832,41.8672122 448.852236,38.7757627 447.510745,36.7140798 C446.169254,34.6523969 443.969982,33.6204805 440.915079,33.6204805 C437.883825,33.6204805 435.699602,34.6459474 434.358111,36.6947314 C433.01662,38.7435153 432.345874,41.8414143 432.345874,45.990578 Z M495.010071,66.894839 L493.483694,61.5654209 L492.888193,61.5654209 C491.669241,63.5045638 489.942931,65.0008425 487.709262,66.0542571 C485.471294,67.1119714 482.9259,67.6408286 480.068782,67.6408286 C475.173628,67.6408286 471.486677,66.3294348 469.001478,63.7087972 C466.51843,61.0881596 465.27583,57.3173652 465.27583,52.4007136 L465.27583,25.235515 L476.639809,25.235515 L476.639809,49.5672432 C476.639809,52.5748495 477.175116,54.8278669 478.243579,56.3305951 C479.309893,57.8333233 481.012555,58.5857624 483.347266,58.5857624 C486.526858,58.5857624 488.825022,57.5237484 490.239607,55.3975707 C491.656343,53.2756927 492.365785,49.754278 492.365785,44.8354767 L492.365785,25.235515 L503.729764,25.235515 L503.729764,66.894839 L495.010071,66.894839 Z M548.308552,66.894839 L536.944572,66.894839 L536.944572,42.5631108 C536.944572,39.5576544 536.409266,37.3024871 535.340802,35.7997589 C534.274489,34.2970307 532.571827,33.5445916 530.237116,33.5445916 C527.055374,33.5445916 524.75936,34.6066056 523.344775,36.7327833 C521.928039,38.8546613 521.218597,42.376076 521.218597,47.2948774 L521.218597,66.894839 L509.854618,66.894839 L509.854618,25.235515 L518.535614,25.235515 L520.06414,30.5649331 L520.696189,30.5649331 C521.964586,28.5526962 523.710245,27.0306195 525.933165,26.000853 C528.156085,24.9689366 530.68428,24.4551282 533.5156,24.4551282 C538.359158,24.4551282 542.03536,25.7643721 544.544207,28.3850098 C547.053053,31.0077972 548.308552,34.7871909 548.308552,39.7296404 L548.308552,66.894839 Z M573.285443,58.5851174 C575.274031,58.5851174 577.656038,58.1508526 580.440063,57.282323 L580.440063,65.7397376 C577.608742,67.005985 574.130324,67.6401836 570.006958,67.6401836 C565.460077,67.6401836 562.149345,66.4921767 560.074763,64.1918631 C558.002331,61.8958491 556.963965,58.4496784 556.963965,53.8533509 L556.963965,33.7696784 L551.524906,33.7696784 L551.524906,28.9626679 L557.785199,25.161776 L561.063683,16.3668387 L568.330094,16.3668387 L568.330094,25.23487 L579.992899,25.23487 L579.992899,33.7696784 L568.330094,33.7696784 L568.330094,53.8533509 C568.330094,55.46787 568.781557,56.6588735 569.688784,57.430661 C570.59601,58.2002986 571.795613,58.5851174 573.285443,58.5851174 Z M616.701819,54.5228066 C616.701819,58.7966603 615.218439,62.0493467 612.24953,64.2851655 C609.280621,66.5209843 604.83908,67.6410436 598.92706,67.6410436 C595.897955,67.6410436 593.313864,67.4346603 591.179087,67.0240436 C589.042161,66.6155767 587.042823,66.0136254 585.178924,65.2181899 L585.178924,55.8277509 C587.290053,56.8209704 589.66991,57.652953 592.314195,58.3258484 C594.960631,58.9944443 597.288892,59.3298171 599.301129,59.3298171 C603.424495,59.3298171 605.486178,58.1388136 605.486178,55.752507 C605.486178,54.8581794 605.21315,54.1315383 604.667094,53.5747334 C604.118889,53.0157787 603.177265,52.3815801 601.835774,51.6742875 C600.494283,50.9648449 598.705628,50.1393118 596.469809,49.1933885 C593.266568,47.8518972 590.910359,46.6114477 589.409781,45.4698902 C587.904903,44.3261829 586.812791,43.0147892 586.129147,41.5378589 C585.445502,40.0587787 585.10583,38.2400261 585.10583,36.0794512 C585.10583,32.3774512 586.539763,29.5160331 589.409781,27.4908972 C592.277648,25.4657613 596.345119,24.4553432 601.610042,24.4553432 C606.627736,24.4553432 611.50999,25.5453049 616.254655,27.7316777 L612.825683,35.9311132 C610.742502,35.0367857 608.79046,34.3036951 606.978157,33.7318415 C605.163704,33.1599878 603.312704,32.8762108 601.427307,32.8762108 C598.071429,32.8762108 596.396715,33.7834373 596.396715,35.5935906 C596.396715,36.6147578 596.936321,37.4940366 598.017683,38.2400261 C599.096896,38.9838659 601.461704,40.0910261 605.114258,41.5572073 C608.366945,42.8729007 610.751101,44.102601 612.266729,45.2463084 C613.784506,46.3878659 614.900265,47.7035592 615.622607,49.1933885 C616.342798,50.6853676 616.701819,52.4611237 616.701819,54.5228066 Z"/>
				</g>
			</svg>

			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %1$s - opening link tag; %2$s - closing link tag. */
						__( 'Welcome to Shared Counts. Please see %1$sthe documentation%2$s for more information.', 'shared-counts' ),
						[
							'a' => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
						]
					),
					'<a href="https://sharedcountsplugin.com/" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="shared-counts-settings-form">

				<?php
				settings_fields( 'shared_counts_options' );
				$options = get_option( 'shared_counts_options', $this->settings_default() );
				?>

				<!-- Count Settings, as in the numbers -->

				<h2 class="title"><?php esc_html_e( 'Share Counts', 'shared-counts' ); ?></h2>

				<table class="form-table">

					<!-- Count Source -->
					<tr valign="top" id="shared-counts-setting-row-count_source">
						<th scope="row"><label for="shared-counts-setting-count_source"><?php esc_html_e( 'Count Source', 'shared-counts' ); ?></label></th>
						<td>
							<select name="shared_counts_options[count_source]" id="shared-counts-setting-count_source">
								<?php
								$opts = [
									'none'        => __( 'None', 'shared-counts' ),
									'sharedcount' => __( 'SharedCount.com', 'shared-counts' ),
									'native'      => __( 'Native', 'shared-counts' ),
								];
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'count_source' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description" style="margin-bottom: 10px;">
								<?php esc_html_e( 'This determines the source of the share counts.', 'shared-counts' ); ?>
							</p>
							<p class="description" style="margin-bottom: 10px;">
								<?php
								echo wp_kses(
									__( '<strong>None</strong>: no counts are displayed and your website will not connect to an outside API, useful if you want simple badges without the counts or associated overhead.', 'shared-counts' ),
									[
										'strong' => [],
									]
								);
								?>
							</p>
							<p class="description" style="margin-bottom: 10px;">
								<?php
								echo wp_kses(
									__( '<strong>SharedCount.com</strong>: counts are retrieved from the SharedCount.com API. This is our recommended option for those wanting share counts. This method allows fetching all counts for with only 2 API calls, so it is best for performance.', 'shared-counts' ),
									[
										'strong' => [],
									]
								);
								?>
							</p>
							<p class="description">
								<?php
								echo wp_kses(
									__( '<strong>Native</strong>: counts are retrieved from their native service. Eg Facebook API for Facebook counts, Pinterest API for Pin counts, etc. This method is more "expensive" since depending on the counts desired uses more API calls (6 API calls if all services are enabled).', 'shared-counts' ),
									[
										'strong' => [],
									]
								);
								?>
							</p>
						</td>
					</tr>

					<!-- ShareCount API Key (ShareCount only) -->
					<tr valign="top" id="shared-counts-setting-row-sharedcount_key">
						<th scope="row"><label for="shared-counts-setting-sharedcount_key"><?php esc_html_e( 'SharedCount API Key', 'shared-counts' ); ?></label></th>
						<td>
							<input type="text" name="shared_counts_options[sharedcount_key]" value="<?php echo esc_attr( $this->settings_value( 'sharedcount_key' ) ); ?>" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Sign up on SharedCount.com for your (free) API key. SharedCount provides 1,000 API requests daily, or 10,000 requests daily if you connect to Facebook. With our caching, this works with sites that receive millions of page views a month and is adaquate for most sites.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Twitter Counts (SharedCount only) -->
					<tr valign="top" id="shared-counts-setting-row-twitter_counts">
						<th scope="row"><label for="shared-counts-setting-twitter_counts"><?php esc_html_e( 'Include Twitter Counts', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[twitter_counts]" value="1" id="shared-counts-setting-twitter_counts" <?php checked( $this->settings_value( 'twitter_counts' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'SharedCount.com does not provide Twitter counts. Checking this option will seperately pull Twitter counts from twitcount.com, which is the service that tracks Twitter counts.', 'shared-counts' ); ?><br><a href="https://twitcount.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sign up for twitcount.com (free).', 'shared-counts' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Yummly Counts (SharedCount only) -->
					<tr valign="top" id="shared-counts-setting-row-yummly_counts">
						<th scope="row"><label for="shared-counts-setting-yummly_counts"><?php esc_html_e( 'Include Yummly Counts', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[yummly_counts]" value="1" id="shared-counts-setting-yummly_counts" <?php checked( $this->settings_value( 'yummly_counts' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'SharedCount.com does not provide Yummly counts. Checking this option will seperately pull Yummly counts from their official API.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Retrieve Share Counts From (Native only) -->
					<tr valign="top" id="shared-counts-setting-row-service">
						<th scope="row"><?php esc_html_e( 'Retrieve Share Counts From', 'shared-counts' ); ?></th>
						<td>
							<fieldset>
							<?php
							$services = $this->query_services();
							foreach ( $services as $service ) {
								echo '<label for="shared-counts-setting-service-' . sanitize_html_class( $service['key'] ) . '">';
									printf(
										'<input type="checkbox" name="shared_counts_options[query_services][]" value="%s" id="shared-counts-setting-service-%s" %s>',
										esc_attr( $service['key'] ),
										sanitize_html_class( $service['key'] ),
										checked( in_array( $service['key'], $this->settings_value( 'query_services' ), true ), true, false )
									);
									echo esc_html( $service['label'] );
								echo '</label><br />';
							}
							?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Each service requires a separate API request, so using many services could cause performance issues. Alternately, consider using SharedCounts for the count source.', 'shared-counts' ); ?>
								<br><br><?php esc_html_e( 'Twitter does provide counts; Twitter share counts will pull from twitcount.com, which is the service that tracks Twitter counts.', 'shared-counts' ); ?><br><a href="https://twitcount.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sign up for twitcount.com (free).', 'shared-counts' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Facebook Access Token (Native only) -->
					<tr valign="top" id="shared-counts-setting-row-fb_access_token">
						<th scope="row"><label for="shared-counts-setting-fb_access_token"><?php esc_html_e( 'Facebook Access Token', 'shared-counts' ); ?></label></th>
						<td>
							<input type="text" name="shared_counts_options[fb_access_token]" value="<?php echo esc_attr( $this->settings_value( 'fb_access_token' ) ); ?>" id="shared-counts-setting-fb_access_token" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'If you have trouble receiving Facebook counts, you may need to setup an access token.', 'shared-counts' ); ?><br><a href="https://smashballoon.com/custom-facebook-feed/access-token/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Follow these instructions.', 'shared-counts' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Count Total Only (SharedCount / Native only) -->
					<tr valign="top" id="shared-counts-setting-row-total_only">
						<th scope="row"><label for="shared-counts-setting-total_only"><?php esc_html_e( 'Count Total Only', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[total_only]" value="1" id="shared-counts-setting-total_only" <?php checked( $this->settings_value( 'total_only' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Check this if you would like to only display the share count total. This is useful if you would like to display the total counts (via Total Counts button) but not the individual counts for each service.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Empty Counts (SharedCount / Native only) -->
					<tr valign="top" id="shared-counts-setting-row-hide_empty">
						<th scope="row"><label for="shared-counts-setting-hide_empty"><?php esc_html_e( 'Hide Empty Counts', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[hide_empty]" value="1" id="shared-counts-setting-hide_empty" <?php checked( $this->settings_value( 'hide_empty' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Optionally, empty counts (0) can be hidden.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Preserve non-HTTPS counts -->
					<?php if ( apply_filters( 'shared_counts_admin_https', is_ssl() ) ) : ?>
					<tr valign="top" id="shared-counts-setting-row-preserve_http">
						<th scope="row"><label for="shared-counts-setting-preserve_http"><?php esc_html_e( 'Preserve HTTP Counts', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[preserve_http]" value="1" id="shared-counts-setting-preserve_http" <?php checked( $this->settings_value( 'preserve_http' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Check this if you would also like to include non-SSL (http://) share counts. This is useful if the site was originally used http:// but has since moved to https://. Enabling this option will double the API calls. ', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>
					<?php endif; ?>

				</table>

				<hr />

				<!-- Display settings -->

				<h2 class="title"><?php esc_html_e( 'Display', 'shared-counts' ); ?></h2>

				<table class="form-table">

					<!-- Buttons Display -->
					<tr valign="top" id="shared-counts-setting-row-included_services">
						<th scope="row"><?php esc_html_e( 'Share Buttons to Display', 'shared-counts' ); ?></th>
						<td>
							<select name="shared_counts_options[included_services][]" id="shared-counts-setting-included_services" class="shared-counts-services" multiple="multiple" style="min-width:350px;">
								<?php
								$services = apply_filters(
									'shared_counts_admin_services',
									[
										'facebook'        => 'Facebook',
										'facebook_likes'  => 'Facebook Like',
										'facebook_shares' => 'Facebook Share',
										'twitter'         => 'Twitter',
										'pinterest'       => 'Pinterest',
										'yummly'          => 'Yummly',
										'linkedin'        => 'LinkedIn',
										'included_total'  => 'Total Counts',
										'print'           => 'Print',
										'email'           => 'Email',
									]
								);
								$selected = $this->settings_value( 'included_services' );

								// Output selected elements first to preserve order.
								foreach ( $selected as $opt ) {
									if ( isset( $services[ $opt ] ) ) {
										printf(
											'<option value="%s" selected>%s</option>',
											esc_attr( $opt ),
											esc_html( $services[ $opt ] )
										);
										unset( $services[ $opt ] );
									}
								}
								// Now output other items.
								foreach ( $services as $key => $label ) {
									printf(
										'<option value="%s">%s</option>',
										esc_attr( $key ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php
								printf(
									/* translators: %1$s - list of services that support share counts. */
									esc_html__( 'Buttons that support share counts: %1$s, and Email.', 'shared-counts' ),
									implode( ', ', wp_list_pluck( $this->query_services(), 'label' ) ) //phpcs:ignore
								);
								?>
							</p>
						</td>
					</tr>

					<!-- Enable Email reCAPTCHA (if email button is configured) -->
					<tr valign="top" id="shared-counts-setting-row-recaptcha">
						<th scope="row"><label for="shared-counts-setting-recaptcha"><?php esc_html_e( 'Enable Email reCAPTCHA', 'shared-counts' ); ?></label></th>
						<td>
							<input type="checkbox" name="shared_counts_options[recaptcha]" value="1" id="shared-counts-setting-recaptcha" <?php checked( $this->settings_value( 'recaptcha' ), 1 ); ?>>
							<p class="description">
								<?php esc_html_e( 'Highly recommended, Google\'s v2 reCAPTCHA will protect the email sharing feature from abuse.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Google reCAPTCHA Site key (if recaptcha is enabled) -->
					<tr valign="top" id="shared-counts-setting-row-recaptcha_site_key">
						<th scope="row"><label for="shared-counts-setting-recaptcha_site_key"><?php esc_html_e( 'reCAPTCHA Site Key', 'shared-counts' ); ?></label></th>
						<td>
							<input type="text" name="shared_counts_options[recaptcha_site_key]" value="<?php echo esc_attr( $this->settings_value( 'recaptcha_site_key' ) ); ?>" id="shared-counts-setting-recaptcha_site_key" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'After signing up for Google\'s v2 reCAPTCHA (free), provide your site key here.', 'shared-counts' ); ?><br><a href="https://www.google.com/recaptcha/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more.', 'shared-counts' ); ?></a>
							</p>
						</td>
					</tr>

					<!-- Google reCAPTCHA Secret key (if recaptcha is enabled) -->
					<tr valign="top" id="shared-counts-setting-row-recaptcha_secret_key">
						<th scope="row"><label for="shared-counts-setting-recaptcha_secret_key"><?php esc_html_e( 'reCAPTCHA Secret Key', 'shared-counts' ); ?></label></th>
						<td>
							<input type="text" name="shared_counts_options[recaptcha_secret_key]" value="<?php echo esc_attr( $this->settings_value( 'recaptcha_secret_key' ) ); ?>" id="shared-counts-setting-recaptcha_secret_key" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'After signing up for Google\'s v2 reCAPTCHA (free), provide your secret key here.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Button style -->
					<tr valign="top" id="shared-counts-setting-row-style">
						<th scope="row"><label for="shared-counts-setting-style"><?php esc_html_e( 'Share Button Style', 'shared-counts' ); ?></label></th>
						<td>
							<select name="shared_counts_options[style]" id="shared-counts-setting-style">
								<?php
								$opts = apply_filters(
									'shared_counts_styles',
									[
										'fancy'   => esc_html__( 'Fancy', 'shared-counts' ),
										'slim'    => esc_html__( 'Slim', 'shared-counts' ),
										'classic' => esc_html__( 'Classic', 'shared-counts' ),
										'block'   => esc_html__( 'Block', 'shared-counts' ),
										'bar'     => esc_html__( 'Bar', 'shared-counts' ),
										'rounded' => esc_html__( 'Rounded', 'shared-counts' ),
										'buttons' => esc_html__( 'Buttons', 'shared-counts' ),
										'icon'    => esc_html__( 'Icon', 'shared-counts' ),
									]
								);
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'style' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php
								printf(
									wp_kses(
										/* translators: %1$s - opening link tag; %2$s - closing link tag. */
										__( 'Multiple share button styles are available; see the %1$splugin page%2$s for screenshots.', 'shared-counts' ),
										[
											'a' => [
												'href'   => [],
												'rel'    => [],
												'target' => [],
											],
										]
									),
									'<a href="https://wordpress.org/plugins/shared-counts/" target="_blank" rel="noopener noreferrer">',
									'</a>'
								);
								?>
							</p>
						</td>
					</tr>

					<!-- Theme location -->
					<tr valign="top" id="shared-counts-setting-row-theme_location">
						<th scope="row"><label for="shared-counts-setting-theme_location"><?php esc_html_e( 'Theme Location', 'shared-counts' ); ?></label></th>
						<td>
							<select name="shared_counts_options[theme_location]" id="shared-counts-setting-theme_location">
								<?php
								$opts = [
									''                     => esc_html__( 'None', 'shared-counts' ),
									'before_content'       => esc_html__( 'Before Content', 'shared-counts' ),
									'after_content'        => esc_html__( 'After Content',  'shared-counts' ),
									'before_after_content' => esc_html__( 'Before and After Content', 'shared-counts' ),
								];
								foreach ( $opts as $key => $label ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										selected( $key, $this->settings_value( 'theme_location' ), false ),
										esc_html( $label )
									);
								}
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Automagically add the share buttons before and/or after your post content.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

					<!-- Supported Post Types (Hide if theme location is None) -->
					<tr valign="top" id="shared-counts-setting-row-post_type">
						<th scope="row"><?php esc_html_e( 'Supported Post Types', 'shared-counts' ); ?></th>
						<td>
							<fieldset>
							<?php
							$opts = get_post_types(
								[
									'public' => true,
								],
								'names'
							);
							if ( isset( $opts['attachment'] ) ) {
								unset( $opts['attachment'] );
							}
							foreach ( $opts as $post_type ) {
								echo '<label for="shared-counts-setting-post_type-' . sanitize_html_class( $post_type ) . '">';
									printf(
										'<input type="checkbox" name="shared_counts_options[post_type][]" value="%s" id="shared-counts-setting-post_type-%s" %s>',
										esc_attr( $post_type ),
										sanitize_html_class( $post_type ),
										checked( in_array( $post_type, $this->settings_value( 'post_type' ), true ), true, false )
									);
									echo esc_html( $post_type );
								echo '</label><br/>';
							}
							?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Which content type(s) you would like to display the share buttons on.', 'shared-counts' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'shared-counts' ); ?>" />
				</p>

			</form>

		</div>
		<?php
	}

	/**
	 * Load settings page assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current hook.
	 */
	public function settings_assets( $hook ) {

		if ( 'settings_page_shared_counts_options' === $hook ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Choices CSS.
			wp_enqueue_style(
				'choices',
				SHARED_COUNTS_URL . 'assets/css/choices' . $suffix . '.css',
				[],
				'3.0.2'
			);

			// Select2 JS library.
			wp_enqueue_script(
				'choices',
				SHARED_COUNTS_URL . 'assets/js/choices' . $suffix . '.js',
				[ 'jquery' ],
				'3.0.2',
				false
			);

			// jQuery Conditions JS library.
			wp_enqueue_script(
				'jquery-conditionals',
				SHARED_COUNTS_URL . 'assets/js/jquery.conditions' . $suffix . '.js',
				[ 'jquery' ],
				'1.0.0',
				false
			);

			// Our settings JS.
			wp_enqueue_script(
				'shared-counts',
				SHARED_COUNTS_URL . 'assets/js/admin-settings' . $suffix . '.js',
				[ 'jquery' ],
				SHARED_COUNTS_VERSION,
				false
			);

			// Localize JS strings.
			$args = [
				'choices_placeholder' => esc_html__( 'Select services...', 'shared-counts' ),
			];
			wp_localize_script( 'shared-counts', 'shared_counts', $args );
		}
	}

	/**
	 * Default settings values.
	 *
	 * @since 1.0.0
	 */
	public function settings_default() {

		return [
			'count_source'         => 'none',
			'fb_access_token'      => '',
			'sharedcount_key'      => '',
			'twitter_counts'       => '',
			'yummly_counts'        => '',
			'style'                => 'classic',
			'total_only'           => '',
			'hide_empty'           => '',
			'preserve_http'        => '',
			'post_type'            => [ 'post' ],
			'theme_location'       => '',
			'included_services'    => [ 'facebook', 'twitter', 'pinterest' ],
			'query_services'       => [],
			'recaptcha'            => '',
			'recpatcha_site_key'   => '',
			'recaptcha_secret_key' => '',
		];
	}

	/**
	 * Return settings value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Settings key.
	 *
	 * @return bool|string|array
	 */
	public function settings_value( $key = false ) {

		$defaults = $this->settings_default();
		$options  = get_option( 'shared_counts_options', $defaults );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		} elseif ( isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		} else {
			return false;
		}
	}

	/**
	 * Query Services.
	 *
	 * @since 1.0.0
	 *
	 * @return array $services
	 */
	public function query_services() {

		$services = [
			[
				'key'   => 'facebook',
				'label' => 'Facebook',
			],
			[
				'key'   => 'twitter',
				'label' => 'Twitter',
			],
			[
				'key'   => 'pinterest',
				'label' => 'Pinterest',
			],
			[
				'key'   => 'yummly',
				'label' => 'Yummly',
			],
		];

		$services = apply_filters( 'shared_counts_query_services', $services );

		return $services;
	}

	/**
	 * Sanitize saved settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Inputs to save/sanitize.
	 *
	 * @return array
	 */
	public function settings_sanitize( $input ) {

		// Reorder services based on the order they were provided.
		$input['count_source']         = sanitize_text_field( $input['count_source'] );
		$input['total_only']           = ! empty( $input['total_only'] ) ? '1' : '';
		$input['hide_empty']           = ! empty( $input['hide_empty'] ) ? '1' : '';
		$input['preserve_http']        = ! empty( $input['preserve_http'] ) ? '1' : '';
		$input['query_services']       = ! empty( $input['query_services'] ) ? array_map( 'sanitize_text_field', $input['query_services'] ) : [];
		$input['fb_access_token']      = sanitize_text_field( $input['fb_access_token'] );
		$input['sharedcount_key']      = sanitize_text_field( $input['sharedcount_key'] );
		$input['twitter_counts']       = ! empty( $input['twitter_counts'] ) ? '1' : '';
		$input['yummly_counts']        = ! empty( $input['yummly_counts'] ) ? '1' : '';
		$input['style']                = sanitize_text_field( $input['style'] );
		$input['post_type']            = ! empty( $input['post_type'] ) ? array_map( 'sanitize_text_field', $input['post_type'] ) : [];
		$input['theme_location']       = sanitize_text_field( $input['theme_location'] );
		$input['included_services']    = ! empty( $input['included_services'] ) ? array_map( 'sanitize_text_field', $input['included_services'] ) : [];
		$input['recaptcha']            = ! empty( $input['recaptcha'] ) ? '1' : '';
		$input['recaptcha_site_key']   = sanitize_text_field( $input['recaptcha_site_key'] );
		$input['recaptcha_secret_key'] = sanitize_text_field( $input['recaptcha_secret_key'] );

		return $input;
	}

	/**
	 * Add settings link to the Plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Plugin settings link.
	 *
	 * @return array $links
	 */
	public function settings_link( $links ) {

		$setting_link = sprintf( '<a href="%s">%s</a>', add_query_arg( [ 'page' => 'shared_counts_options' ], admin_url( 'options-general.php' ) ), __( 'Settings', 'shared-counts' ) );
		array_unshift( $links, $setting_link );
		return $links;
	}

	/**
	 * Plugin author name links.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links Plugin settings link.
	 * @param string $file  Plugin file slug.
	 *
	 * @return string
	 */
	public function author_links( $links, $file ) {

		if ( strpos( $file, 'shared-counts.php' ) !== false ) {
			$links[1] = 'By <a href="https://www.billerickson.net">Bill Erickson</a> & <a href="https://www.jaredatchison.com">Jared Atchison</a>';
		}
		return $links;
	}

	// ********************************************************************** //
	//
	// Post Listing Column - these methods register and handle the column on post listing screen.
	//
	// ********************************************************************** //

	/**
	 * Add Shared Count Column.
	 *
	 * @since 1.1.0
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_shared_count_column( $columns ) {

		$icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="19" viewBox="0 0 20 19"><path fill="#444" fill-rule="evenodd" d="M13.2438425,10.4284937 L11.6564007,10.4284937 L11.6564007,7.36477277 C11.6564007,6.99267974 11.6643076,6.65221463 11.6805867,6.34384253 C11.5829123,6.46244718 11.4610518,6.58616811 11.3159356,6.71593556 L10.6587263,7.25826114 L9.84709835,6.2601216 L11.8345402,4.64151695 L13.2438425,4.64151695 L13.2438425,10.4284937 Z M9.43314486,8.10105184 L7.9601216,8.10105184 L7.9601216,9.52988904 L6.89547044,9.52988904 L6.89547044,8.10105184 L5.42337742,8.10105184 L5.42337742,7.0401216 L6.89547044,7.0401216 L6.89547044,5.58756346 L7.9601216,5.58756346 L7.9601216,7.0401216 L9.43314486,7.0401216 L9.43314486,8.10105184 Z M18.1666332,0.000121602787 L1.83360997,0.000121602787 C0.822447184,0.000121602787 0.000121602787,0.8229123 0.000121602787,1.83360997 L0.000121602787,12.83361 C0.000121602787,13.8443076 0.822447184,14.6666332 1.83360997,14.6666332 L9.32477277,14.6666332 L13.8666332,18.3001216 C13.99361,18.401517 14.1461681,18.45361 14.3005867,18.45361 C14.4038425,18.45361 14.5075635,18.4303542 14.6047728,18.3833774 C14.8484937,18.2661681 15.0001216,18.0247728 15.0001216,17.7545402 L15.0001216,14.6666332 L18.1666332,14.6666332 C19.1773309,14.6666332 20.0001216,13.8443076 20.0001216,12.83361 L20.0001216,1.83360997 C20.0001216,0.8229123 19.1773309,0.000121602787 18.1666332,0.000121602787 Z"/></svg>';
		$label = __( 'Share Count', 'shared-counts' );

		$shared_count_column = [
			'shared_counts' => $icon . '<span class="screen-reader-text">' . $label . '</span>',
		];

		// Insert our column after 'comments'.
		$new_columns = [];

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'comments' === $key ) {
				$new_columns = array_merge( $new_columns, $shared_count_column );
			}
		}

		// If no comments column, insert at the end.
		if ( ! array_key_exists( 'shared_counts', $new_columns ) ) {
			$new_columns = array_merge( $new_columns, $shared_count_column );
		}

		return $new_columns;
	}

	/**
	 * Shared Count Column.
	 *
	 * @since 1.1.0
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public function shared_count_column( $column, $post_id ) {

		if ( 'shared_counts' === $column ) {
			echo intval( get_post_meta( $post_id, 'shared_counts_total', true ) );
		}
	}

	/**
	 * Shared Count Sortable Column.
	 *
	 * @since 1.1.0
	 *
	 * @param array $columns Post columns array.
	 *
	 * @return array
	 */
	public function shared_count_sortable_column( $columns ) {

		$columns['shared_counts'] = 'shared_counts';
		return $columns;
	}

	/**
	 * Sort Column Query.
	 *
	 * @since 1.1.0
	 *
	 * @param object $query WP_Query object.
	 */
	public function sort_column_query( $query ) {

		if ( is_admin() && 'shared_counts' === $query->get( 'orderby' ) ) {
			$meta_query = array(
				'relation' => 'OR',
				array(
					'key' => 'shared_counts_total',
					'type' => 'NUMERIC',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => 'shared_counts_total',
					'type' => 'NUMERIC',
					'compare' => 'EXISTS',
				)
			);
			$query->set( 'orderby', 'meta_value_num date' );
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Column Style.
	 *
	 * @since 1.1.0
	 *
	 * @param string $hook Current hook.
	 */
	public function column_style( $hook ) {

		if ( 'edit.php' !== $hook ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'shared-counts-column',
			SHARED_COUNTS_URL . 'assets/css/admin-column' . $suffix . '.css',
			[],
			SHARED_COUNTS_VERSION
		);
	}

	// ********************************************************************** //
	//
	// Metabox - these methods register and handle the post edit metabox.
	//
	// ********************************************************************** //

	/**
	 * Initialize the metabox for supported post types.
	 *
	 * @since 1.0.0
	 */
	public function metabox_add() {

		$options = $this->options();

		if ( ! empty( $options['post_type'] ) ) {
			$post_types = (array) $options['post_type'];
			foreach ( $post_types as $post_type ) {
				add_meta_box( 'shared-counts-metabox', __( 'Share Counts', 'shared-counts' ), [ $this, 'metabox' ], $post_type, 'side', 'low' );
			}
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @since 1.0.0
	 */
	public function metabox() {

		global $post;
		$options = $this->options();

		// Only display if we're collecting share counts.
		if ( ! empty( $options['count_source'] ) && 'none' !== $options['count_source'] ) {

			// Alert user that post must be published to track share counts.
			if ( 'publish' !== $post->post_status ) {
				echo '<p>' . esc_html__( 'Post must be published to view share counts.', 'shared-counts' ) . '</p>';
				return;
			}

			$counts = get_post_meta( $post->ID, 'shared_counts', true );
			$groups = get_post_meta( $post->ID, 'shared_counts_groups', true );

			if ( ! empty( $counts ) ) {

				// Decode the primary counts. This is the total of all possible
				// share count URLs.
				$counts = json_decode( $counts, true );

				// Output the primary counts numbers.
				echo $this->metabox_counts_group( 'total', $counts, $post->ID ); // phpcs:ignore

				// Show https and http groups at the top if we have them.
				if ( ! empty( $groups['http'] ) && ! empty( $groups['https'] ) ) {
					echo $this->metabox_counts_group( 'https', [], $post->ID ); // phpcs:ignore
					echo $this->metabox_counts_group( 'http', [], $post->ID ); // phpcs:ignore
				}

				// Output other counts.
				if ( ! empty( $groups ) ) {
					foreach ( $groups as $slug => $group ) {
						// Skip https and https groups since we output them manually
						// above already.
						if ( ! in_array( $slug, [ 'http', 'https' ], true ) ) {
							echo $this->metabox_counts_group( $slug, [], $post->ID ); // phpcs:ignore
						}
					}
				}

				// Display the date and time the share counts were last updated.
				$date = get_post_meta( $post->ID, 'shared_counts_datetime', true );
				$date = $date + ( get_option( 'gmt_offset' ) * 3600 );
				echo '<p class="counts-updated">' . esc_html__( 'Last updated', 'shared-counts' ) . ' <span>' . esc_html( date( 'M j, Y g:ia', $date ) ) . '</span></p>';

			} else {

				// Current post has not fetched share counts yet.
				echo '<p class="counts-empty">' . esc_html__( 'No share counts downloaded for this post.', 'shared-counts' ) . '</p>';
			}

			// Action buttons.
			echo '<div class="button-wrap">';

				// Toggle option to add a new URL to track.
				if ( apply_filters( 'shared_counts_url_groups', true ) ) {
					echo '<button class="button shared-counts-refresh add" data-nonce="' . esc_attr( wp_create_nonce( 'shared-counts-refresh-' . $post->ID ) ) . '" data-postid="' . absint( $post->ID ) . '">';
						esc_html_e( 'Add URL', 'shared-counts' );
					echo '</button>';
				}

				// Refresh share counts.
				echo '<button class="button shared-counts-refresh" data-nonce="' . esc_attr( wp_create_nonce( 'shared-counts-refresh-' . $post->ID ) ) . '" data-postid="' . absint( $post->ID ) . '">';
					esc_html_e( 'Refresh Counts', 'shared-counts' );
				echo '</button>';

			echo '</div>';

		}

		// Option to exclude share buttons for this post.
		$exclude   = absint( get_post_meta( $post->ID, 'shared_counts_exclude', true ) );
		$post_type = get_post_type_object( get_post_type( $post->ID ) );
		echo '<p><input type="checkbox" name="shared_counts_exclude" id="shared_counts_exclude" value="1" ' . checked( 1, $exclude, false ) . ' /> <label for="shared_counts_exclude">' . esc_html__( 'Don\'t display buttons on this', 'shared-counts' ) . ' ' . esc_html( strtolower( $post_type->labels->singular_name ) ) . '</label></p>';

		// Nonce for saving exclude setting on save.
		wp_nonce_field( 'shared_counts', 'shared_counts_nonce' );
	}

	/**
	 * Build the metabox list item counts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group   Group type.
	 * @param array  $counts  Current counts.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function metabox_counts_group( $group = 'total', $counts = [], $post_id ) {

		$icon    = 'total' === $group ? 'down-alt2' : 'right-alt2';
		$class   = 'total' === $group ? 'count-group-open' : 'count-group-closed';
		$options = $this->options();
		$url     = false;
		$disable = false;

		if ( 'total' === $group ) {
			$name  = esc_html__( 'Total', 'shared-counts' );
			$total = get_post_meta( $post_id, 'shared_counts_total', true );
		} else {
			$groups = get_post_meta( $post_id, 'shared_counts_groups', true );
			if ( ! empty( $groups[ $group ]['name'] ) ) {
				$name    = esc_html( $groups[ $group ]['name'] );
				$counts  = json_decode( $groups[ $group ]['counts'], true );
				$total   = $groups[ $group ]['total'];
				$url     = ! empty( $groups[ $group ]['url'] ) ? $groups[ $group ]['url'] : false;
				$disable = ! empty( $groups[ $group ]['disable'] ) ? true : false;
			}
		}

		if ( empty( $counts ) || ! is_array( $counts ) ) {
			return;
		}

		ob_start();

		// Count group wrap.
		echo '<div class="count-group ' . sanitize_html_class( $class ) . ' ' . sanitize_html_class( $group ) . '">';

			// Group title, delete, and display toggle.
			echo '<h3>';
				echo esc_html( $name );
				echo '<span class="total">(' . number_format( absint( $total ) ) . ')</span>';
				if ( ! in_array( $group, [ 'total', 'http', 'https' ], true ) ) {
					echo '<a href="#" class="shared-counts-refresh delete" data-group="' . esc_attr( $group ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'shared-counts-refresh-' . absint( $post_id ) ) ) . '" data-postid="' . absint( $post_id ) . '" title="' . esc_attr__( 'Delete count group', 'shared-counts' ) . '"><span class="dashicons dashicons-dismiss"></span></a>';
				}
				echo '<a href="#" class="count-group-toggle" title="' . esc_attr__( 'Toggle count group', 'shared-counts' ) . '"><span class="dashicons dashicons-arrow-' . esc_html( $icon ) . '"></span></a>';
			echo '</h3>';

			echo '<div class="count-details">';

				if ( $url ) {
					echo '<input type="text" value="' . esc_attr( $url ) . '" class="count-url" readonly />';
				}

				echo '<ul>';
					echo '<li>' . esc_html__( 'Facebook Total:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Facebook']['total_count'] ) ? number_format( absint( $counts['Facebook']['total_count'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Facebook Likes:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Facebook']['like_count'] ) ? number_format( absint( $counts['Facebook']['like_count'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Facebook Shares:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Facebook']['share_count'] ) ? number_format( absint( $counts['Facebook']['share_count'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Facebook Comments:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Facebook']['comment_count'] ) ? number_format( absint( $counts['Facebook']['comment_count'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Twitter:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Twitter'] ) ? number_format( absint( $counts['Twitter'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Pinterest:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Pinterest'] ) ? number_format( absint( $counts['Pinterest'] ) ) : '0' ) . '</strong></li>';
					echo '<li>' . esc_html__( 'Yummly:', 'shared-counts' ) . ' <strong>' . ( ! empty( $counts['Yummly'] ) ? number_format( absint( $counts['Yummly'] ) ) : '0' ) . '</strong></li>';
					// Show Email shares if enabled.
					if ( in_array( 'email', $options['included_services'], true ) ) {
						echo '<li>' . esc_html__( 'Email:', 'shared-counts' ) . ' <strong>' . absint( get_post_meta( $post_id, 'shared_counts_email', true ) ) . '</strong></li>';
					}
				echo '</ul>';

				if ( ! in_array( $group, [ 'total', 'http', 'https' ], true ) ) {
					echo '<p><input type="checkbox" name="shared_counts_disable[' . esc_html( $group ) . ']" id="shared_counts_disable_' . esc_html( $group ) . '" value="1" ' . checked( true, $disable, false ) . ' /> <label for="shared_counts_disable_' . esc_html( $group ) . '">' . esc_html__( 'Disable API updates.', 'shared-counts' ) . '</label></p>';
				}

			echo '</div>';

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Metabox AJAX functionality.
	 *
	 * @since 1.0.0
	 */
	public function metabox_ajax() {

		// Run a security check.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'shared-counts-refresh-' . $_POST['post_id'] ) ) { //phpcs:ignore
			wp_send_json_error(
				[
					'msg'     => esc_html__( 'Failed security.', 'shared-counts' ),
					'msgtype' => 'error',
				]
			);
		}

		// Check for permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'msg'     => esc_html__( 'You do not have permission.', 'shared-counts' ),
					'msgtype' => 'error',
				]
			);
		}

		$id     = absint( $_POST['post_id'] ); //phpcs:ignore
		$groups = get_post_meta( $id, 'shared_counts_groups', true );
		$msg    = esc_html__( 'Share counts updated.', 'shared-counts' );

		// Empty post meta returns an empty string but we want an empty array.
		if ( ! is_array( $groups ) ) {
			$groups = [];
		}

		if ( ! empty( $_POST['group_url'] ) && ! empty( $_POST['group_name'] ) ) {
			// Check if we are are adding a new URL group.

			$msg                 = esc_html__( 'New URL added; Share counts updated.', 'shared-counts' );
			$group_id            = uniqid();
			$groups[ $group_id ] = [
				'name'   => sanitize_text_field( wp_unslash( $_POST['group_name'] ) ), //phpcs:ignore
				'url'    => esc_url_raw( wp_unslash( $_POST['group_url'] ) ), //phpcs:ignore
				'counts' => '',
				'total'  => 0,
			];

			update_post_meta( $id, 'shared_counts_groups', $groups );

		} elseif ( ! empty( $_POST['group_delete'] ) && isset( $groups[ $_POST['group_delete'] ] ) ) {
			// Check if we are deleting a URL group.

			$msg = esc_html__( 'URL deleted; Share counts updated.', 'shared-counts' );

			unset( $groups[ $_POST['group_delete'] ] );

			update_post_meta( $id, 'shared_counts_groups', $groups );
		}

		// Force the counts to update.
		$total = shared_counts()->core->counts( $id, true, true );

		// Include the primary counts numbers.
		$counts = $this->metabox_counts_group( 'total', $total, $id );

		// Include https and http groups at the top if we have them.
		if ( ! empty( $groups['http'] ) && ! empty( $groups['https'] ) ) {
			$counts .= $this->metabox_counts_group( 'https', [], $id );
			$counts .= $this->metabox_counts_group( 'http', [], $id );
		}

		// Include other count groups.
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $slug => $group ) {
				// Skip https and https groups since we output them manually
				// above already.
				if ( ! in_array( $slug, [ 'http', 'https' ], true ) ) {
					$counts .= $this->metabox_counts_group( $slug, [], $id );
				}
			}
		}

		wp_send_json_success(
			[
				'msg'     => $msg,
				'msgtype' => 'success',
				'date'    => date( 'M j, Y g:ia', time() + ( get_option( 'gmt_offset' ) * 3600 ) ),
				'counts'  => $counts,
			]
		);
	}

	/**
	 * Load metabox assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current hook.
	 */
	public function metabox_assets( $hook ) {

		global $post;

		$options = $this->options();

		if ( empty( $options['post_type'] ) ) {
			return;
		}

		if ( 'post.php' === $hook && in_array( $post->post_type, $options['post_type'], true ) ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script(
				'shared-counts',
				SHARED_COUNTS_URL . 'assets/js/admin-metabox' . $suffix . '.js',
				[ 'jquery' ],
				SHARED_COUNTS_VERSION,
				false
			);

			wp_enqueue_style(
				'shared-counts',
				SHARED_COUNTS_URL . 'assets/css/admin-metabox' . $suffix . '.css',
				[],
				SHARED_COUNTS_VERSION
			);

			// Localize JS strings.
			$args = [
				'loading'        => esc_html__( 'Updating...', 'shared-counts' ),
				'refresh'        => esc_html__( 'Refresh Counts', 'shared-counts' ),
				'add_url'        => esc_html__( 'Add URL', 'shared-counts' ),
				'adding'         => esc_html__( 'Adding...', 'shared-counts' ),
				'url_prompt'     => esc_html__( 'Enter the full URL you would like to track.', 'shared-counts' ),
				'url_prompt_eg'  => esc_html__( 'E.g. http://your-domain.com/some-old-post-url', 'shared-counts' ),
				'name_prompt'    => esc_html__( 'Enter the nickname for the URL.', 'shared-counts' ),
				'name_prompt_eg' => esc_html__( 'E.g. "Post title typo"', 'shared-counts' ),
				'confirm_delete' => esc_html__( 'Are you sure you want to remove this URL group and the associated share counts?', 'shared-counts' ),
			];
			wp_localize_script( 'shared-counts', 'shared_counts', $args );
		}
	}

	/**
	 * Save the Metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function metabox_save( $post_id, $post ) {

		// Security check.
		if ( ! isset( $_POST['shared_counts_nonce'] ) || ! wp_verify_nonce( $_POST['shared_counts_nonce'], 'shared_counts' ) ) { //phpcs:ignore
			return;
		}

		// Bail out if running an autosave, ajax, cron.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Display exclude setting.
		if ( isset( $_POST['shared_counts_exclude'] ) ) {
			update_post_meta( $post_id, 'shared_counts_exclude', 1 );
		} else {
			delete_post_meta( $post_id, 'shared_counts_exclude' );
		}

		// Disable group update settings.
		$groups = get_post_meta( $post_id, 'shared_counts_groups', true );

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $slug => $group ) {
				if ( in_array( $slug, [ 'http', 'https' ], true ) ) {
					continue;
				}
				if ( isset( $groups[ $slug ]['disable'] ) ) {
					unset( $groups[ $slug ]['disable'] );
				}
				if ( isset( $_POST['shared_counts_disable'][ $slug ] ) ) {
					$groups[ $slug ]['disable'] = true;
				}
			}
			update_post_meta( $post_id, 'shared_counts_groups', $groups );
		}
	}

	/**
	 * Register Shared Content Dashboard Widget.
	 *
	 * @since 1.0.0
	 */
	public function register_dashboard_widget() {

		wp_add_dashboard_widget(
			'shared_counts_dashboard_widget',
			esc_html__( 'Most Shared Content', 'shared-counts' ),
			[ $this, 'dashboard_widget' ]
		);
	}

	/**
	 * Shared Content Dashboard Widget.
	 *
	 * @since 1.0.0
	 */
	public function dashboard_widget() {

		$posts = get_transient( 'shared_counts_dashboard_posts' );

		if ( false === $posts ) {

			$posts = '';
			$loop  = new WP_Query(
				apply_filters(
					'shared_counts_dashboard_widget_args',
					[
						'posts_per_page' => 20,
						'orderby'        => 'meta_value_num',
						'order'          => 'DESC',
						'meta_key'       => 'shared_counts_total', //phpcs:ignore
					]
				)
			);

			if ( $loop->have_posts() ) {
				$posts .= '<ol>';
				while ( $loop->have_posts() ) {
					$loop->the_post();
					$shares = get_post_meta( get_the_ID(), 'shared_counts_total', true );
					$posts .= sprintf(
						'<li><a href="%s">%s (%s %s)</a></li>',
						esc_url( get_permalink() ),
						get_the_title(),
						esc_html( $shares ),
						esc_html( _n( 'share', 'shares', $shares, 'shared-counts' ) )
					);
				}
				$posts .= '</ol>';
			}
			wp_reset_postdata();

			set_transient( 'shared_counts_dashboard_posts', $posts, DAY_IN_SECONDS );
		} else {
			echo '<!-- Shared Counts Posts: Cached -->';
		}

		echo $posts; //phpcs:ignore
	}
}
