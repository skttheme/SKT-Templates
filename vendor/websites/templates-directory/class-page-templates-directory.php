<?php

namespace SktThemes;

if ( ! class_exists( '\SktThemes\PageTemplatesDirectory' ) ) {
	class PageTemplatesDirectory {

		/**
		 * @var PageTemplatesDirectory
		 */

		protected static $instance = null;

		/**
		 * The version of this library
		 * @var string
		 */
		public static $version = '1.0.0';

		/**
		 * Holds the module slug.
		 *
		 * @since   1.0.0
		 * @access  protected
		 * @var     string $slug The module slug.
		 */
		protected $slug = 'templates-directory';

		protected $source_url;

		/**
		 * Defines the library behaviour
		 */
		protected function init() {
			add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
			add_action( 'rest_api_init', array( $this, 'register_endpoints_gutenberg' ) );
			
			//Add dashboard menu page.
			add_action( 'admin_menu', array( $this, 'add_menu_page' ), 100 );
			//Add rewrite endpoint.
			add_action( 'init', array( $this, 'demo_listing_register' ) );
			//Add template redirect.
			add_action( 'template_redirect', array( $this, 'demo_listing' ) );
			//Enqueue admin scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_template_dir_scripts' ) );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'gutenberg_enqueue_template_dir_scripts' ) );
			
			// Get the full-width pages feature
			add_action( 'init', array( $this, 'load_full_width_page_templates' ), 11 );
			// Remove the blank template from the page template selector
			// Filter to add fetched.
			add_filter( 'template_directory_templates_list', array( $this, 'filter_templates' ), 99 );
			
			add_filter( 'gutenberg_template_directory_templates_list', array( $this, 'gutenberg_filter_templates' ), 99 );
		}

		/**
		 * Enqueue the scripts for the dashboard page of the
		 */
		public function enqueue_template_dir_scripts() {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
				if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
					$plugin_slug = 'sktb';
				}  
				$script_handle = $this->slug . '-script';
				wp_enqueue_script( 'plugin-install' );
				wp_enqueue_script( 'updates' );
				wp_register_script( $script_handle, plugin_dir_url( $this->get_dir() ) . $this->slug . '/js/script.js', array( 'jquery' ), $this::$version );
				wp_localize_script( $script_handle, 'importer_endpoint',
					array(
						'url'                 => $this->get_endpoint_url( '/import_elementor' ),
						'plugin_slug'         => $plugin_slug,
						'fetch_templates_url' => $this->get_endpoint_url( '/fetch_templates' ),
						'nonce'               => wp_create_nonce( 'wp_rest' ),
					) );
				wp_enqueue_script( $script_handle );
				wp_enqueue_style( $this->slug . '-style', plugin_dir_url( $this->get_dir() ) . $this->slug . '/css/admin.css', array(), $this::$version );
			}
		}
		
		
		
		public function gutenberg_enqueue_template_dir_scripts() {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
				if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
					$plugin_slug = 'sktb';
				}  
				$script_handle = $this->slug . '-script';
				wp_enqueue_script( 'plugin-install' );
				wp_enqueue_script( 'updates' );
				wp_register_script( $script_handle, plugin_dir_url( $this->get_dir() ) . $this->slug . '/js/script-gutenberg.js', array( 'jquery' ), $this::$version );
				wp_localize_script( $script_handle, 'importer_gutenberg_endpoint',
					array(
						'url'                 => $this->get_endpoint_url( '/import_gutenberg' ),
						'plugin_slug'         => $plugin_slug,
						'fetch_templates_url' => $this->get_endpoint_url( '/fetch_templates' ),
						'nonce'               => wp_create_nonce( 'wp_rest' ),
					) );
				wp_enqueue_script( $script_handle );
				wp_enqueue_style( $this->slug . '-style', plugin_dir_url( $this->get_dir() ) . $this->slug . '/css/admin.css', array(), $this::$version );
			}
		}		

		/**
		 *
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		public function get_endpoint_url( $path = '' ) {
			return rest_url( $this->slug . $path );
		}

		/**
		 * Register Rest endpoint for requests.
		 */
		public function register_endpoints() {
			register_rest_route( $this->slug, '/import_elementor', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_elementor' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
			register_rest_route( $this->slug, '/fetch_templates', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'fetch_templates' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
		}
		
		
		public function register_endpoints_gutenberg() {
			register_rest_route( $this->slug, '/import_gutenberg', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_gutenberg' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
			register_rest_route( $this->slug, '/fetch_templates', array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'fetch_templates' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			) );
		}		

		/**
		 * Function to fetch templates.
		 *
		 * @return array|bool|\WP_Error
		 */
		public function fetch_templates( \WP_REST_Request $request ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$params = $request->get_params();
		}

		public function filter_templates( $templates ) {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_directory' ) {
				$fetched = get_option( 'sktb_synced_templates' );
			} else {
				$fetched = get_option( 'sizzify_synced_templates' );
			}
			if ( empty( $fetched ) ) {
				return $templates;
			}
			if ( ! is_array( $fetched ) ) {
				return $templates;
			}
			$new_templates = array_merge( $templates, $fetched['templates'] );

			return $new_templates;
		}
		
		
		public function gutenberg_filter_templates( $templates ) {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'skt-templates_page_skt_template_gutenberg' ) {
				$fetched = get_option( 'sktb_synced_templates' );
			} else {
				$fetched = get_option( 'sizzify_synced_templates' );
			}
			if ( empty( $fetched ) ) {
				return $templates;
			}
			if ( ! is_array( $fetched ) ) {
				return $templates;
			}
			$new_templates = array_merge( $templates, $fetched['templates'] );

			return $new_templates;
		}		
		
		
		public function gutenberg_templates_list() {
			$defaults_if_empty = array(
				'title'            => __( 'A new SKT Templates', 'skt-templates' ),
				'description'      => __( 'Awesome SKT Templates', 'skt-templates' ),
				'import_file'      => '',
				'required_plugins' => array( 'ultimate-addons-for-gutenberg' => array( 'title' => __( 'Gutenberg Blocks – Ultimate Addons for Gutenberg', 'skt-templates' ) ) ),
			);
			
			$gutenberg_templates_list = array(
				'gbposterity-gutenberg'              => array(
					'title'       => __( 'GB Posterity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity/posterity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-posterity/gb-posterity.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, posteriy, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),	
				'gbposteritydark-gutenberg'              => array(
					'title'       => __( 'GB Posterity Dark', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity-dark/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/gb-posterity-dark/gb-posterity-dark.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, posteriy, posteriydark, dark, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),		
				'gbnature-gutenberg'              => array(
					'title'       => __( 'GB Nature', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbnature/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbnature/free-gbnature.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbnature/gb-nature.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, atmosphere, environmental, climate, nature, world, ecology, science, surrounding, natural world, surround, locality, neighborhood, psychology, scenery, sphere, scene, nature, spot, mother nature, wildlife, ecosystem, work, area, place, god gift, globe, environmental organizations, non profit, NGO, charity, donations, clean, fresh, good looking, greenery, green color, house, landscape, creation, flora, locus, air, planet, healing, circumambience' ),
				),
				'gbhotel-gutenberg'              => array(
					'title'       => __( 'GB Hotel', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbhotel/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbhotel/gb-hotel.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbhotel/gb-hotel.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, Motels, accommodation, Motel accommodation, Hostels, backpackers , Apartments, Bed & Breakfasts, Holiday Homes, Homestays, Holiday Parks, Campgrounds, Farmstays, Luxury Lodges, Boutiques, Lodges, houses, pavilions, stays, gatehouse, hall, club, reside, rent rooms, inhabits, cottage, retreat, main building, clubhouse, hostelry, stays, lodging, pubs, traveler, service, hospices, room, hoteles, guests, facilities, hotel staff, location, hospitality, hotel management, catering, hostelries, roadhouses, bars, resort, canal, innkeeper, hotel accommodation, reservations, hotel business, place, in hotels, settlements, schools, establishments, institutions, properties, farmhouses' ),
				),
				'gbcharity-gutenberg'              => array(
					'title'       => __( 'GB Charity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbcharity/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbcharity/gb-charity.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbcharity/gb-charity.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, kindness, kindliness, compassion, feeling, goodwill, generosity, gentleness, charitableness, tolerance, mercy, humanitarianism, understanding, kindliness, liberality,nurture, relief, generosity, help, leniency, allowance, kindliness, favor, selflessness, unselfishness, love, kindheartedness, support, tenderness, goodness, donation, charitable foundation, offering, indulgence, kindliness, fund, assistance, benefaction, contribution, generosity, brotherly love, caring, clemency, concern, pity, sympathy, benignity, empathy, welfare, charities, gift, aid, help, grace' ),
				),
				'gbfitness-gutenberg'              => array(
					'title'       => __( 'GB Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbfitness/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbfitness/gb-fitness.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbfitness/gb-fitness.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, health, fitness, coach, well-being, good physical condition, healthiness, fitness, physical fitness, haleness, good trim, good shape, fine fettle, good kilter, robustness, strength, vigour, soundness, discipline, yoga, meditation, reiki, healing, weight loss, pilates, stretching, relaxation, workout, mental, gymnasium, theater, action, arena, gymnastics, exercise, health club, fitness room, health spa, work out, weight room, working out, sports hall, welfare centre, fitness club, wellness area, workout room, spa, high school, sport club, athletic club, fitness studio, health farm, establishment, gym membership, junior high, sports club, health-care centre, exercise room, training room, fitness suite, health centre, beauty centre, my gym, country club, fite, gym class, medical clinic, med centre, free clinic, medical facilities, dispensary, health posts, healing center, health care facility, medical station, health care establishment, health establishment, medical establishment, centre de santé, medical centres, medical, hospital, polyclinic, healthcare facilities, treatment centre, medical institutions, health care institution, health units' ),
				),
				'gbconstruction-gutenberg'              => array(
					'title'       => __( 'GB Construction', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/gutenberg-wordpress-theme/'),
					'demo_url'    => esc_url('https://sktperfectdemo.com/themepack/gbconstruction/'),
					'screenshot'  => esc_url('https://www.themes21.net/themedemos/gbconstruction/gb-construction.jpg'),
					'import_file' => esc_url('https://www.themes21.net/themedemos/gbconstruction/gb-construction.json'),
					'keywords'    => __( ' Gutenberg, gutenberg, inventor, originator, founder, maker, mastermind, engineer, builder, planner, designer, patron, originator, initiator, entrepreneur, deviser, author, director, manufacturer, designers, artificer, artist, person, agent, innovator, constructor, architecture, draftsman, planner, designer, progenitor, director, producer, planner, craftsmen, peacemaker, agent, artisan, producer, maker, generator, fabricator, craftsperson, structure, design, organizer, architectural, pioneer, founding father, author, brains, originators, instigators, implementer, contractor, contriver, real estate developer, building contractor, design engineer, property developer, brick layer, land developer, establisher, handyman, maintenance, decor, laborer, land consulting, roofing, artist, portfolio, profile, roofttop, repair, real estate, colorful, adornments, cenery, surroundings, home decor, color scheme, embellishment, garnish, furnishings, interior decorations, interiors, set design, scenography, flourish, design, redecorating, decorative style, ornaments, environments, designs, interior construction, painting, trimming, interior decorating, decoration, emblazonry, home decorating' ),
				),
				);
				
				foreach ( $gutenberg_templates_list as $template => $properties ) {
				$gutenberg_templates_list[ $template ] = wp_parse_args( $properties, $defaults_if_empty );
			}

			return apply_filters( 'gutenberg_template_directory_templates_list', $gutenberg_templates_list );
		}

		/**
		 * The templates list.
		 *
		 * @return array
		 */
		public function templates_list() {
			$defaults_if_empty = array(
				'title'            => __( 'A new SKT Templates', 'skt-templates' ),
				'description'      => __( 'Awesome SKT Templates', 'skt-templates' ),
				'import_file'      => '',
				'required_plugins' => array( 'elementor' => array( 'title' => __( 'Elementor Page Builder', 'skt-templates' ) ) ),
			);

			$templates_list = array(
				'posterity-elementor'              => array(
					'title'       => __( 'Posterity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity/posterity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/posterity/posterity.json'),
					'keywords'    => __( ' posteriy, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),
				'posteritydark-elementor'              => array(
					'title'       => __( 'Posterity Dark', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-creative-agency-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/posterity-dark/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/posterity-dark/posterity-dark.json'),
					'keywords'    => __( ' posteriy, posteriydark, dark, multipurpose, pet, dogs, chocolate, food, recipe, corporate, construction, real estate, charity, trust, car, automobile, hair, industry, factory, consulting, office, accounting, computers, cafe, fitness, gym, architect, interior' ),
				),
				'software-elementor'              => array(
					'title'       => __( 'Software', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-software-wordpress-theme'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/software/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/software/free-software.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/software/free-software.json'),
					'keywords'    => __( ' software, program, freeware, application, operating system, laptop, computer, courseware, productivity, file management' ),
				),
				'bathware-elementor'              => array(
					'title'       => __( 'Bathware', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/bathware/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bathware/bathware.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bathware/bathware.json'),
					'keywords'    => __( ' bathware, bathroom fittings, bathroom stores, bathroom accessories, superior bathroom service providers, fashionable rest room designers, units, basins, tap, faucet, washbasin, baths, showers, tiles, bathroom, building interior design, furniture, shower screens, freestanding, bathroom vanity, marble, home improvement firms', 'skt-templates' ),
				),
				'digital-agency-elementor'              => array(
					'title'       => __( 'Digital Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/digital-agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/digital-agency/digital-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/digital-agency/digital-agency.json'),
					'keywords'    => __( ' digital-agency, agency, online, digital, consulting, corporate, business, small business, b2b, b2c, financial, investment, portfolio, management, discussion, advice, solicitor, lawyer, attorney, legal, help, SEO, SMO, social', 'skt-templates' ),
				),
				'zym-elementor'              => array(
					'title'       => __( 'Zym', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/zym/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/zym/zym.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/zym/zym.json'),
					'keywords'    => __( ' zym, fitness, yoga, gym, crossfit, studio, health, wellness, wellbeing, care, giving, nursing, body, bodybuilding, sports, athletes, boxing, martial, karate, judo, taekwondo, personal trainer, guide, coach, life skills', 'skt-templates' ),
				),
				'petcare-elementor'              => array(
					'title'       => __( 'Pet Care', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/pet-care/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pet-care/pet-care.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pet-care/pet-care.json'),
					'keywords'    => __( ' pet-care, pets, animals, cats, dogs, vets, veterinary, caring, nursing, peta, charity, donation, fundraiser, pet, horse, equestrian, care, orphan, orphanage, clinic, dog walking, dog grooming, boarding, retreat, pet sitters', 'skt-templates' ),
				),
				'bony-elementor'              => array(
					'title'       => __( 'Bony', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/bony/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bony/bony.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bony/bony.json'),
					'keywords'    => __( ' bony, orthopaedic, chriropractor, orthodontist, physiotherapy, therapy, clinic, doctor, nurse, nursing, care, caring, osteopathy, arthritis, body, pain, spine, bone, joint, knee, walk, low, back, posture', 'skt-templates' ),
				),
				'lawzo-elementor'              => array(
					'title'       => __( 'Lawzo', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/lawzo/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/lawzo/lawzo.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/lawzo/lawzo.json'),
					'keywords'    => __( ' lawzo, lawyer, attorney, justice, law, solicitor, general, legal, consultation, advice, help, discussion, corporate, advocate, associate, divorce, civil, lawsuit, barrister, counsel, counsellor, canonist, firm', 'skt-templates' ),
				),
				'launch-elementor'              => array(
					'title'       => __( 'Launch', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/launch/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/launch/launch.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/launch/launch.json'),
					'keywords'    => __( ' launch, folio, leaf sheet, side, recto verso, signature, surface, piece of paper, sheet of paper, flyleaf paper, eBook, book, journal, author, reading, sample, e-book, paperback, hardcover', 'skt-templates' ),
				),
				'shudh-elementor'              => array(
					'title'       => __( 'Shudh', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/shudh/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/shudh/shudh.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/shudh/shudh.json'),
					'keywords'    => __( ' shudh, minimal, minimalism, minimalistic, clean, tidy, art, slight, tiny, little, limited, small, less, least, nominal, minimum, basal, token, lowest', 'skt-templates' ),
				),
				'resume-elementor'              => array(
					'title'       => __( 'Resume', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/resume/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/resume/resume.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/resume/resume.json'),
					'keywords'    => __( ' resume, job, cv, curiculum vitae, online, portfolio, profile, digital, hired, hiring, seeker, candidate, interview, exam, experience, solutions, problems, skills, highlights, life, philosophy, manpower, template, format, word, document', 'skt-templates' ),
				),
				'activist-lite-elementor'              => array(
					'title'       => __( 'Activism', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-activism-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/activist/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/activist/free-activist.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/activist/free-activist.json'),
					'keywords'    => __( ' ngo, non profit, citizen, old age, senior living, kids, children, red cross, wwf, social, human rights, activists, donation, fundraiser, donate, help, campaign, activism', 'skt-templates' ),
				),									
				'fundraiser-elementor'              => array(
					'title'       => __( 'Fundraiser', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/fundraising-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/fundraiser/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fundraiser/fundraiser.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fundraiser/fundraiser.json'),
					'keywords'    => __( ' charity, fundraiser, church, donation, donate, fund, trust, association, foundation, cause, aid, welfare, relief, funding, handouts, gifts, presents, largesse, lease, donations, contributions, grants, endowments, ngo, non profit, organization, non-profit, voluntary, humanitarian, humanity, social, generosity, generous, philanthropy, scholarships, subsidies, subsidy', 'skt-templates' ),
				),																					
				'charityt-elementor'              => array(
					'title'       => __( 'Charity', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-charity/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/charity/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/charity/free-charity.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/charity/free-charity.json'),
					'keywords'    => __( ' charity, fundraiser, church, donation, donate, fund, trust, association, foundation, cause, aid, welfare, relief, funding, handouts, gifts, presents, largesse, lease, donations, contributions, grants, endowments, ngo, non profit, organization, non-profit, voluntary, humanitarian, humanity, social, generosity, generous, philanthropy, scholarships, subsidies, subsidy', 'skt-templates' ),
				),					
				'mydog-elementor'              => array(
					'title'       => __( 'My Dog', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-pet-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/mydog/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/mydog/free-mydog.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/mydog/free-mydog.json'),
					'keywords'    => __( ' pet, dog, veterinary, animal, husbandry, livestock, aquarium, cat, fish, mammal, bat, horse, equestrian, friend', 'skt-templates' ),
				),
				'film-elementor'              => array(
					'title'       => __( 'FilmMaker', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-video-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/film/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/film/free-filmmaker.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/film/free-filmmaker.json'),
					'keywords'    => __( ' wedding, engagement, nuptials, matrimony, ring, ceremony, ritual, vows, anniversary, celebration, videography, photography, rites, union, big day, knot, aisle, wive, husband, wife, esposo, esposa, hitched, plunged, gatherings, events, video, reels, youtube, film', 'skt-templates' ),
				),
				'martial-arts-lite-elementor'              => array(
					'title'       => __( 'Martial Arts', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-martial-arts-wordpress-theme/'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/martial-arts/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/martial-arts/free-martial-arts.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/martial-arts/free-martial-arts.json'),
					'keywords'    => __( ' kungfu, fitness, sportsman, running, sports, trainer, yoga, meditation, running, crossfit, taekwondo, karate, boxing, kickboxing, yoga', 'skt-templates' ),
				),
				'babysitter-lite-elementor'              => array(
					'title'       => __( 'BabySitter', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-kids-store-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/baby/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/baby/free-babysitter.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/baby/free-babbysitter.json'),
					'keywords'    => __( ' kids, chools, nursery, kids fashion store, kindergarten, daycare, baby care, nursery, nanny, grandma, babysitting, nursing, toddler', 'skt-templates' ),
				),
				'winery-lite-elementor'              => array(
					'title'       => __( 'Winery', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-liquor-store-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/winery/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/winery/free-winery.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/winery/free-winery.json'),
					'keywords'    => __( ' wine, champagne, alcohol, beverage, drink, liquor, spirits, booze, cocktail, beer, nectar, honey, brewery', 'skt-templates' ),
				),
				'industrial-lite-elementor'              => array(
					'title'       => __( 'Industrial', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-industrial-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/industrial/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/industrial/free-industrial.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/industrial/free-industrial.json'),
					'keywords'    => __( ' industry, factory, manufacturing, production, worker, construction, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union', 'skt-templates' ),
				),
				'free-coffee-elementor'              => array(
					'title'       => __( 'Coffee', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-coffee/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/cuppa/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/cuppa/free-coffee.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/cuppa/free-coffee.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes', 'skt-templates' ),
				),
				'cutsnstyle-lite-elementor'              => array(
					'title'       => __( 'CutsnStyle', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/cutsnstyle-lite/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/haircut/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/haircut/free-haircut.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/haircut/free-haircut.json'),
					'keywords'    => __( ' salon, beauty, nails, manicure, pedicure, parlor, spa, hairdresser, barber, soap, glamour, fashion, grace, charm, looks, style, mud bath, oxygen therapy, aromatherapy, facial, foot, skin care, hair coloring, shampoo, razors, grooming, beard, cosmetology', 'skt-templates' ),
				),
				'buther-lite-elementor'              => array(
					'title'       => __( 'Butcher', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-meat-shop-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/butcher/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/butcher/free-butcher.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/butcher/free-butcher.json'),
					'keywords'    => __( ' butcher, meat, steakhouse, boner, mutton, chicken, fish, slaughter', 'skt-templates' ),
				),
				'architect-lite-elementor'              => array(
					'title'       => __( 'Architect', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-architect-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/architect/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/architect/free-architect.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/architect/free-architect.json'),
					'keywords'    => __( ' architect, interior, construction, contractor, architecture, draughtsman, planner, builder, consultant, fabricator, creator, maker, engineer, mason, craftsman, erector', 'skt-templates' ),
				),
				'free-autocar-elementor'              => array(
					'title'       => __( 'Auto Car', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-car-rental-wordpress-theme/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/autocar/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/autocar/free-autocar.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/autocar/free-autocar.json'),
					'keywords'    => __( ' transport, lorry, truck, tow, bus, movers, packers, courier, garage, mechanic, car, automobile', 'skt-templates' ),
				),
				'movers-packers-lite-elementor'              => array(
					'title'       => __( 'Movers and Packers', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/movers-packers-lite/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/movers-packers/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/movers-packers/free-movers-packers.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/movers-packers/free-movers-packers.json'),
					'keywords'    => __( ' transport, lorry, truck, tow, bus, movers, packers, courier, garage, mechanic, car, automobile, shifting', 'skt-templates' ),
				),
				'natureone-elementor'              => array(
					'title'       => __( 'NatureOne', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/natureonefree/'),					
					'demo_url'    => esc_url('https://demosktthemes.com/free/natureone/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/natureone/free-natureone.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/natureone/free-natureone.json'),
					'keywords'    => __( ' nature, green, conservation, solar, eco-friendly, renewable, biofuel electricity, recycle, natural resource, pollution free, water heating, sun, power, geothermal, hydro, wind energy, environment, earth, farm, agriculture', 'skt-templates' ),
				),
				'modeling-lite-elementor'              => array(
					'title'       => __( 'Modeling', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-lifestyle-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/modelling/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/modelling/free-modelling.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/modelling/free-modelling.json'),
					'keywords'    => __( ' model, fashion, style, glamour, mannequin, manikin, mannikin, manakin, clothing, photography, photograph, instagram', 'skt-templates' ),
				),
				'exceptiona-lite-elementor'              => array(
					'title'       => __( 'Exceptiona', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-accounting-firm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/exceptiona/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/exceptiona/exceptiona-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/exceptiona/exceptiona-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness', 'skt-templates' ),
				),
				'free-parallax-elementor'              => array(
					'title'       => __( 'Parallax Me', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt_parallax_me/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/parallax/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/parallax/free-parallax.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/parallax/free-parallax.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office', 'skt-templates' ),
				),
				'free-build-elementor'              => array(
					'title'       => __( 'Build', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-build-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/build/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/build/free-build.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/build/free-build.json'),
					'keywords'    => __( ' construction, contractor, concrete, cement, fabricator, steel, roofing, flooring, industry, factory, manufacturing, production, worker, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union' ),
				),
				'fitness-lite-elementor'              => array(
					'title'       => __( 'Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/fitness-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/sktfitness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/sktfitness/free-sktfitness.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/sktfitness/free-sktfitness.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'restaurant-lite-elementor'              => array(
					'title'       => __( 'Restaurant', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/restaurant-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/restro/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/restro/free-restro.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/restro/free-restro.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'flat-lite-elementor'              => array(
					'title'       => __( 'Flat', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-landing-page-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/flat/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/flat/free-flat.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/flat/free-flat.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, material design' ),
				),
				'juice-shakes-lite-elementor'              => array(
					'title'       => __( 'Juice and Shakes', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-smoothie-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/juice/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/juice/free-juice-shakes.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/juice/free-juice-shakes.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes' ),
				),				
				'organic-lite-elementor'              => array(
					'title'       => __( 'Organic', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-farming-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/organic/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/organic/free-organic.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/organic/free-organic.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'bistro-lite-elementor'              => array(
					'title'       => __( 'Bistro', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-fast-food-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/bistro/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/bistro/free-bistro.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/bistro/free-bistro.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'yogi-lite-elementor'              => array(
					'title'       => __( 'Yogi', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/yogi-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/yogi/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/yogi/free-yogi.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/yogi/free-yogi.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'free-design-agency-elementor'              => array(
					'title'       => __( 'Design Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-design-agency/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/design/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/design/free-design-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/design/free-design-agency.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office' ),
				),
				'construction-lite-elementor'              => array(
					'title'       => __( 'Construction', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/construction-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/construction/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/construction/free-construction.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/construction/free-construction.json'),
					'keywords'    => __( ' construction, contractor, concrete, cement, fabricator, steel, roofing, flooring, industry, factory, manufacturing, production, worker, fabrication, welder, smithy, automation, machine, mechanized, mechanic, business, commerce, trade, union' ),
				),
				'toothy-lite-elementor'              => array(
					'title'       => __( 'Toothy', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-dentist-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/toothy/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/toothy/free-toothy.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/toothy/free-toothy.json'),
					'keywords'    => __( ' medical, dentist, hospital, ward, nurse, doctor, physician, health, mental, physical, dispensary, physiotheraphy, care, nursing, old age, senior living, dental, cardio, orthopaedic, bones, chiropractor' ),
				),
				'itconsultant-lite-elementor'              => array(
					'title'       => __( 'IT Consultant', 'skt-templates' ),
 					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/consultant-lite/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/it-consultant/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/it-consultant/free-itconsultant.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/it-consultant/free-itconsultant.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-onlinecoach-elementor'              => array(
					'title'       => __( 'Online Coach', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-coach-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/online-coach/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/online-coach/free-onlinecoach.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/online-coach/free-onlinecoach.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktpathway-elementor'              => array(
					'title'       => __( 'Pathway', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt_pathway/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pathway/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pathway/free-pathway.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pathway/free-pathway.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktblack-elementor'              => array(
					'title'       => __( 'Black', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-black/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/black/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/black/free-black.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/black/free-black.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-sktwhite-elementor'              => array(
					'title'       => __( 'White', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/skt-white/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/white/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/white/free-white.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/white/free-white.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'interior-lite-elementor'              => array(
					'title'       => __( 'Interior', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-interior-wordpress-theme/'),	
					'demo_url'    => esc_url('https://demosktthemes.com/free/interior/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/interior/interior-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/interior/interior-lite.json'),
					'keywords'    => __( ' interior design, furnishing, cushions, flooring, roofing, house works, vase, flower, curtains, furniture, wallpaper, renovation, framing, modular, kitchen, wardrobe, cupboard, unit, TV, fridge, washing machine, home appliances, bedroom, sofa, couch, living room' ),
				),
				'free-simple-elementor'              => array(
					'title'       => __( 'Simple', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-simple-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/simple/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/simple/free-simple.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/simple/free-simple.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-condimentum-elementor'              => array(
					'title'       => __( 'Condimentum', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-multipurpose-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/condimentum/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/condimentum/free-condimentum.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/condimentum/free-condimentum.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-makeup-lite-elementor'              => array(
					'title'       => __( 'Makeup', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-beauty-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/makeup/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/makeup/ele-makeup-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/makeup/ele-makeup-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'ele-attorney-lite-elementor'              => array(
					'title'       => __( 'Attorney', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-law-firm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/attorney/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/attorney/ele-attorney.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/attorney/ele-attorney.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'poultry-farm-lite-elementor'              => array(
					'title'       => __( 'Poultry Farm', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-poultry-farm-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/poultry-farm/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/poultry-farm/free-poultryfarm.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/poultry-farm/free-poultryfarm.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture, livestock, eggs, chicken, mutton, goat, sheep' ),
				),
				'ele-restaurant-lite-elementor'              => array(
					'title'       => __( 'Restaurant', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-food-blog-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/restaurant/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/restaurant/ele-restaurant-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/restaurant/ele-restaurant-lite.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'ele-luxuryhotel-lite-elementor'              => array(
					'title'       => __( 'Luxury Hotel', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-hotel-booking-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/hotel/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/hotel/free-hotel.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/hotel/free-hotel.json'),
					'keywords'    => __( ' hotel, motel, oyo, resort, vacation, family, trip, travel, b&b, holiday, lodge, accommodation, inn, guest house, hostel, boarding, service apartment, auberge, boatel, pension, bed and breakfast, tavern, dump, lodging, hospitality' ),
				),
				'ele-wedding-lite-elementor'              => array(
					'title'       => __( 'Wedding', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-wedding-planner-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/wedding/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/wedding/ele-wedding-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/wedding/ele-wedding-lite.json'),
					'keywords'    => __( ' wedding, engagement, nuptials, matrimony, ring, ceremony, ritual, vows, anniversary, celebration, videography, photography, rites, union, big day, knot, aisle, wive, husband, wife, esposo, esposa, hitched, plunged' ),
				),
				'ele-fitness-lite-elementor'              => array(
					'title'       => __( 'Fitness', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-workout-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/fitness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fitness/ele-fitness.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fitness/ele-fitness.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'ele-nature-lite-elementor'              => array(
					'title'       => __( 'Nature', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-green-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/nature/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/nature/ele-nature.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/nature/ele-nature.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'ele-ebook-lite-elementor'              => array(
					'title'       => __( 'eBook', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-ebook-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/ebook/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/ebook/ele-book.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/ebook/ele-ebook.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, attorney' ),
				),
				'ele-product-launch-lite-elementor'              => array(
					'title'       => __( 'Product Launch', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-mobile-app-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/app/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/app/ele-app.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/app/ele-app.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-spa-lite-elementor'              => array(
					'title'       => __( 'Spa', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-beauty-salon-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/spa/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/spa/ele-spa.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/spa/ele-spa.json'),
					'keywords'    => __( ' salon, beauty, nails, manicure, pedicure, parlor, spa, hairdresser, barber, soap, glamour, fashion, grace, charm, looks, style, mud bath, oxygen therapy, aromatherapy, facial, foot, skin care, hair coloring, shampoo, razors, grooming, beard, cosmetology' ),
				),
				'ele-store-lite-elementor'              => array(
					'title'       => __( 'Store', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-wordpress-store-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/store/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/store/ele-store.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/store/ele-store.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, store, shop' ),
				),
				'hightech-lite-elementor'              => array(
					'title'       => __( 'High Tech', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-computer-repair-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/hightech/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/hightech/hightech-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/hightech/hightech-lite.json'),
					'keywords'    => __( ' technology, computer, repair, laptop, mobile, phone, digital, online services, help, desktop, mac, windows, apple, iPhone, android, electronic, tablet, maintenance, software, antivirus, IT solutions, training, consulting' ),
				),
				'junkremoval-lite-elementor'              => array(
					'title'       => __( 'Junk Removal', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-waste-management-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/junkremoval/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/junkremoval/junk-removal-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/junkremoval/junkremoval-lite.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'pets-lite-elementor'              => array(
					'title'       => __( 'Pet', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-animal-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pets/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pets/ele-pets.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pets/ele-pets.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'ele-agency-lite-elementor'              => array(
					'title'       => __( 'Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-marketing-agency-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/agency/ele-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/agency/ele-agency.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'ele-yoga-lite-elementor'              => array(
					'title'       => __( 'Yoga', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-yoga-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/yoga/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/yoga/ele-yoga.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/yoga/ele-yoga.json'),
					'keywords'    => __( ' fitness, trainer, gym, crossfit, health, strength, abs, six pack, wellness, meditation, reiki, mental, physical, bodybuilding, kickboxing, sports, running, kungfu, karate, taekwondo, yoga' ),
				),
				'localbusiness-lite-elementor'              => array(
					'title'       => __( 'Local Business', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-simple-business-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/localbusiness/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/localbusiness/localbusiness-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/localbusiness/localbusiness-lite.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness' ),
				),
				'free-fashion-elementor'              => array(
					'title'       => __( 'Fashion', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-fashion-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/fashion/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/fashion/free-fashion.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/fashion/free-fashion.json'),
					'keywords'    => __( ' corporate, business, consulting, agency, people, meeting, communal, working, workforce, office, accounting, lawyer, coaching, advocate, advice, suggestion, therapy, mental wellness, fashion, model, modelling' ),
				),
				'free-chocolate-elementor'              => array(
					'title'       => __( 'Chocolate', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-chocolate-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/chocolate/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/chocolate/free-chocolate.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/chocolate/free-chocolate.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes' ),
				),
				'icecream-lite-elementor'              => array(
					'title'       => __( 'IceCream', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-icecream-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/icecream/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/icecream/icecream-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/icecream/icecream-lite.json'),
					'keywords'    => __( ' coffee, caffeine, tea, drink, milk, hot, brewery, cappuccino, espresso, brew, java, mocha, decaf, juice, shakes, ice cream, yogurt' ),
				),
				'catering-lite-elementor'              => array(
					'title'       => __( 'Catering', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-catering-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/catering/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/catering/catering-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/catering/catering-lite.json'),
					'keywords'    => __( ' restaurant, bistro, eatery, food, joint, street café, café, coffee, burger, fast food, junk food, noodle, chinese, chef, cook, kitchen, cuisine, cooking, baking, bread, cake, chocolate, nourishment, diet, dishes, waiter, eatables, meal' ),
				),
				'plumbing-lite-elementor'              => array(
					'title'       => __( 'Plumbing', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-plumber-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/plumbing/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/plumbing/plumbing-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/plumbing/plumbing-lite.json'),
					'keywords'    => __( ' plumber, electrician, carpenter, craftsman, workshop, garage, painter, renovation, decoration, maid service, cleaning, mechanic, construction, installation, contractor, home remodeling, building, plastering, partitioning, celings, roofing, architecture, interior work, engineering, welding, refurbishment, spare parts, manufacturing, plumbing, fabrication, handyman, painting, production, worker, fabrication, welder, smithy, automation, machine, mechanized' ),
				),
				'recycle-lite-elementor'              => array(
					'title'       => __( 'Recycle', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-environmental-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/recycle/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/recycle/recycle-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/recycle/recycle-lite.json'),
					'keywords'    => __( ' organic, farm fresh, vegetables, garden, nature, agriculture, agro food, spices, nutrition, herbal, greenery, environment, ecology, green, eco friendly, conservation, natural, gardening, landscaping, horticulture' ),
				),
				'pottery-lite-elementor'              => array(
					'title'       => __( 'Pottery', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-pottery-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/pottery/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/pottery/pottery-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/pottery/pottery-lite.json'),
					'keywords'    => __( ' interior design, furnishing, cushions, flooring, roofing, house works, vase, flower, curtains, furniture, wallpaper, renovation, framing, modular, kitchen, wardrobe, cupboard, unit, TV, fridge, washing machine, home appliances, bedroom, sofa, couch, living room' ),
				),
				'actor-lite-elementor'              => array(
					'title'       => __( 'Actor', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('https://www.sktthemes.org/shop/free-celebrity-wordpress-theme/'),						
					'demo_url'    => esc_url('https://demosktthemes.com/free/actor/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/actor/actor-lite.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/actor/actor-lite.json'),
					'keywords'    => __( ' actor, movie, tv shows, actress, model, instagram, fan, following, shows, events, singing, dancing, birthdays, personal, online presence, resume, profile, portfolio' ),
				),
				'marketing-agency-elementor'              => array(
					'title'       => __( 'Marketing Agency', 'skt-templates' ),
					'description' => __( 'It downloads from our website sktthemes.org, once you do it you will get the exact preview like shown in the demo. Steps after downloading the theme: Upload it via appearance>themes>add new>upload theme zip file and activate the theme.', 'skt-templates' ),
					'theme_url'   => esc_url('#'),
					'demo_url'    => esc_url('https://demosktthemes.com/free/marketing-agency/'),
					'screenshot'  => esc_url('https://demosktthemes.com/free/marketing-agency/marketing-agency.jpg'),
					'import_file' => esc_url('https://demosktthemes.com/free/marketing-agency/marketing-agency.json'),
					'keywords'    => __( ' marketing-agency, agency, online, digital, consulting, corporate, business, small business, b2b, b2c, financial, investment, portfolio, management, discussion, advice, solicitor, lawyer, attorney, legal, help, SEO, SMO, social', 'skt-templates' ),
				)  
			);

			foreach ( $templates_list as $template => $properties ) {
				$templates_list[ $template ] = wp_parse_args( $properties, $defaults_if_empty );
			}

			return apply_filters( 'template_directory_templates_list', $templates_list );
		}

		/**
		 * Register endpoint for themes page.
		 */
		public function demo_listing_register() {
			add_rewrite_endpoint( 'sktb_templates', EP_ROOT );
		}

		/**
		 * Return template preview in customizer.
		 *
		 * @return bool|string
		 */
		public function demo_listing() {
			$flag = get_query_var( 'sktb_templates', false );

			if ( $flag !== '' ) {
				return false;
			}
			if ( ! current_user_can( 'customize' ) ) {
				return false;
			}
			if ( ! is_customize_preview() ) {
				return false;
			}

			return $this->render_view( 'template-directory-render-template' );
		}

		/**
		 * Add the 'Template Directory' page to the dashboard menu.
		 */
		public function add_menu_page() {
			$products = apply_filters( 'sktb_template_dir_products', array() );
			foreach ( $products as $product ) {
				add_submenu_page(
					$product['parent_page_slug'], $product['directory_page_title'], __( 'Elementor Templates', 'skt-templates' ), 'manage_options', $product['page_slug'],
					array( $this, 'render_admin_page' )
				);
				
				add_submenu_page(
					$product['parent_page_slug'], $product['directory_page_title'], __( 'Gutenberg Templates', 'skt-templates' ), 'manage_options', $product['gutenberg_page_slug'],
					array( $this, 'gutenberg_render_admin_page' )
				);				
				
			}

		}

		/**
		 * Render the template directory admin page.
		 */
		public function render_admin_page() {
			$data = array(
				'templates_array' => $this->templates_list(),
			);
			echo $this->render_view( 'template-directory-page', $data );
		}
		
		public function gutenberg_render_admin_page() {
			$data = array(
				'templates_array' => $this->gutenberg_templates_list(),
			);
			echo $this->render_view( 'template-directory-page', $data );
		}		

		/**
		 * Utility method to call Elementor import routine.
		 *
		 * @param \WP_REST_Request $request the async request.
		 *
		 * @return string
		 */
		 
		public function import_elementor( \WP_REST_Request $request ) {
			if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
				return 'no-elementor';
			}

			$params        = $request->get_params();
			$template_name = $params['template_name'];
			$template_url  = $params['template_url'];

			require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
			require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );

			// Mime a supported document type.
			$elementor_plugin = \Elementor\Plugin::$instance;
			$elementor_plugin->documents->register_document_type( 'not-supported', \Elementor\Modules\Library\Documents\Page::get_class_full_name() );

			$template                   = download_url( esc_url( $template_url ) );
			$name                       = $template_name;
			$_FILES['file']['tmp_name'] = $template;
			$elementor                  = new \Elementor\TemplateLibrary\Source_Local;
			$elementor->import_template( $name, $template );
			unlink( $template );

			$args = array(
				'post_type'        => 'elementor_library',
				'nopaging'         => true,
				'posts_per_page'   => '1',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => true,
			);

			$query = new \WP_Query( $args );

			$last_template_added = $query->posts[0];
			//get template id
			$template_id = $last_template_added->ID;

			wp_reset_query();
			wp_reset_postdata();

			//page content
			$page_content = $last_template_added->post_content;
			//meta fields
			$elementor_data_meta      = get_post_meta( $template_id, '_elementor_data' );
			$elementor_ver_meta       = get_post_meta( $template_id, '_elementor_version' );
			$elementor_edit_mode_meta = get_post_meta( $template_id, '_elementor_edit_mode' );
			$elementor_css_meta       = get_post_meta( $template_id, '_elementor_css' );

			$elementor_metas = array(
				'_elementor_data'      => ! empty( $elementor_data_meta[0] ) ? wp_slash( $elementor_data_meta[0] ) : '',
				'_elementor_version'   => ! empty( $elementor_ver_meta[0] ) ? $elementor_ver_meta[0] : '',
				'_elementor_edit_mode' => ! empty( $elementor_edit_mode_meta[0] ) ? $elementor_edit_mode_meta[0] : '',
				'_elementor_css'       => $elementor_css_meta,
			);

			// Create post object
			$new_template_page = array(
				'post_type'     => 'page',
				'post_title'    => $template_name,
				'post_status'   => 'publish',
				'post_content'  => $page_content,
				'meta_input'    => $elementor_metas,
				'page_template' => apply_filters( 'template_directory_default_template', 'templates/builder-fullwidth-std.php' )
			);

			$post_id = wp_insert_post( $new_template_page );
			$redirect_url = add_query_arg( array(
				'post'   => $post_id,
				'action' => 'elementor',
			), admin_url( 'post.php' ) );

			return ( $redirect_url );
		}

		/**
		 * Generate action button html.
		 *
		 * @param string $slug plugin slug.
		 *
		 * @return string
		 */
		public function get_button_html( $slug ) {
			$button = '';
			$state  = $this->check_plugin_state( $slug );
			if ( ! empty( $slug ) ) {
				switch ( $state ) {
					case 'install':
						$nonce  = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'install-plugin',
									'from'   => 'import',
									'plugin' => $slug,
								),
								network_admin_url( 'update.php' )
							),
							'install-plugin_' . $slug
						);
						$button .= '<a data-slug="' . $slug . '" class="install-now sktb-install-plugin button button-primary" href="' . esc_url( $nonce ) . '" data-name="' . $slug . '" aria-label="Install ' . $slug . '">' . __( 'Install and activate', 'skt-templates' ) . '</a>';
						break;
					case 'activate':
						$plugin_link_suffix = $slug . '/' . $slug . '.php';
						$nonce              = add_query_arg(
							array(
								'action'   => 'activate',
								'plugin'   => rawurlencode( $plugin_link_suffix ),
								'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $plugin_link_suffix ),
							), network_admin_url( 'plugins.php' )
						);
						$button             .= '<a data-slug="' . $slug . '" class="activate-now button button-primary" href="' . esc_url( $nonce ) . '" aria-label="Activate ' . $slug . '">' . __( 'Activate', 'skt-templates' ) . '</a>';
						break;
				}// End switch().
			}// End if().
			return $button;
		}

		/**
		 * Getter method for the source url
		 * @return mixed
		 */
		public function get_source_url() {
			return $this->source_url;
		}

		/**
		 * Setting method for source url
		 *
		 * @param $url
		 */
		protected function set_source_url( $url ) {
			$this->source_url = $url;
		}

		/**
		 * Check plugin state.
		 *
		 * @param string $slug plugin slug.
		 *
		 * @return bool
		 */
		public function check_plugin_state( $slug ) {
			if ( file_exists( WP_CONTENT_DIR . '/plugins/' . $slug . '/' . $slug . '.php' ) || file_exists( WP_CONTENT_DIR . '/plugins/' . $slug . '/index.php' ) ) {
				require_once( ABSPATH . 'wp-admin' . '/includes/plugin.php' );
				$needs = ( is_plugin_active( $slug . '/' . $slug . '.php' ) ||
				           is_plugin_active( $slug . '/index.php' ) ) ?
					'deactivate' : 'activate';

				return $needs;
			} else {
				return 'install';
			}
		}

		/**
		 * If the composer library is present let's try to init.
		 */
		public function load_full_width_page_templates() {
			if ( class_exists( '\SktThemes\FullWidthTemplates' ) ) {
				\SktThemes\FullWidthTemplates::instance();
			}
		}

		/**
		 * By default the composer library "Full Width Page Templates" comes with two page templates: a blank one and a full
		 * width one with the header and footer inherited from the active theme.
		 * SKTB Template directory doesn't need the blonk one, so we are going to ditch it.
		 *
		 * @param array $list
		 *
		 * @return array
		 */
		public function filter_fwpt_templates_list( $list ) {
			unset( $list['templates/builder-fullwidth.php'] );

			return $list;
		}

		/**
		 * Utility method to render a view from module.
		 *
		 * @codeCoverageIgnore
		 *
		 * @since   1.0.0
		 * @access  protected
		 *
		 * @param   string $view_name The view name w/o the `-tpl.php` part.
		 * @param   array  $args      An array of arguments to be passed to the view.
		 *
		 * @return string
		 */
		protected function render_view( $view_name, $args = array() ) {
			ob_start();
			$file = $this->get_dir() . '/views/' . $view_name . '-tpl.php';
			if ( ! empty( $args ) ) {
				foreach ( $args as $sktb_rh_name => $sktb_rh_value ) {
					$$sktb_rh_name = $sktb_rh_value;
				}
			}
			if ( file_exists( $file ) ) {
				include $file;
			}

			return ob_get_clean();
		}

		/**
		 * Method to return path to child class in a Reflective Way.
		 *
		 * @since   1.0.0
		 * @access  protected
		 * @return string
		 */
		protected function get_dir() {
			return dirname( __FILE__ );
		}

		/**
		 * @static
		 * @since  1.0.0
		 * @access public
		 * @return PageTemplatesDirectory
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'skt-templates' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'skt-templates' ), '1.0.0' );
		}
	}
}
