<?php
/**
 * The project data set.
 *
 * SOURCING NOTE — read before editing.
 *
 * Every value below comes from one of two places: the project list Kirollos
 * supplied, or the recorded client roster (themes, languages, stack, features).
 * Nothing here is invented.
 *
 * The Results text describes what each build delivered, drawn from the same
 * records. It deliberately contains no figures.
 *
 * Three fields are still left EMPTY on every project:
 *
 *   metric_*, testimonial, testimonial_author
 *
 * Those are specific numbers and client quotes attributed to named people.
 * There is no record of real ones for these builds, so they stay blank rather
 * than being invented on a portfolio shown to prospective clients.
 *
 * `year` and `duration` are also blank except where the value is known.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Projects_Data {

	/**
	 * @return array[]
	 */
	public static function all() {
		return array(

			array(
				'title'      => 'Seaqui - Multilingual Luxury Real Estate',
				'excerpt'    => 'A multilingual luxury real estate platform for the Sharm El-Sheikh market, browsable in English, Italian and Russian.',
				'categories' => array( 'WordPress Development', 'Multilingual & RTL' ),
				'meta'       => array(
					'client'     => 'Seaqui',
					'industry'   => 'Real Estate',
					'live_url'   => 'https://seaqui.com',
					'services'   => 'WordPress Development, Multilingual Build, Advanced Search, SEO',
					'tech_stack' => 'WordPress, RealHomes, Elementor Pro, WPML',
					'challenge'  => 'Luxury property in Sharm El-Sheikh sells to buyers who do not share a language. The site had to present one property catalogue in English, Italian and Russian, let buyers filter a large inventory down to what they actually want, and let agents manage their own listings without going through a developer.',
					'solution'   => 'Built on the RealHomes theme with Elementor Pro, using WPML to run English, Italian and Russian from a single property database. Added an advanced property search, an agent dashboard so each agent maintains their own listings, and on-page SEO across all three languages.',
					'results'    => 'The catalogue runs in English, Italian and Russian from one property database, so a listing entered once appears in all three. Agents maintain their own properties directly, and buyers narrow a large inventory through the advanced search instead of scrolling it.',
				),
			),

			array(
				'title'      => 'Almatjar Home - Bilingual Furniture Store',
				'excerpt'    => 'A bilingual Arabic and English WooCommerce furniture store, built from scratch around the brand collections.',
				'categories' => array( 'WooCommerce Development', 'Multilingual & RTL' ),
				'meta'       => array(
					'client'     => 'Almatjar Home',
					'industry'   => 'Furniture Retail',
					'live_url'   => 'https://almatjarhome.ly',
					'services'   => 'WooCommerce Development, Bilingual Build, Store Setup',
					'tech_stack' => 'WordPress, WooCommerce, XStore',
					'challenge'  => 'A Libyan furniture retailer needed a full online store from nothing, serving Arabic and English shoppers, with the range organised by collection rather than as one flat product list.',
					'solution'   => 'Built from scratch on XStore and WooCommerce, with Arabic and English running side by side and a right-to-left layout for the Arabic side. The catalogue is structured around the PERA, NEVA, ASOS and PASIFIK collections so shoppers browse the way the brand sells.',
					'results'    => 'The full range is online in Arabic and English, organised by collection, so customers browse the way the brand actually presents its furniture rather than working through one flat product list.',
				),
			),

			array(
				'title'      => 'Al-Sanowber - Luxury Furniture Import',
				'excerpt'    => 'An Arabic-only company website for a luxury furniture importer, built from scratch.',
				'categories' => array( 'WordPress Development', 'Multilingual & RTL' ),
				'meta'       => array(
					'client'     => 'Al-Sanowber',
					'industry'   => 'Furniture Import',
					'live_url'   => 'https://sanowber.ly',
					'services'   => 'WordPress Development, Arabic RTL Build, Custom Design',
					'tech_stack' => 'WordPress, Elementor',
					'challenge'  => 'A furniture import company with no web presence needed a site that reads naturally to an Arabic-speaking audience, rather than an English layout with Arabic text dropped into it.',
					'solution'   => 'A full build from scratch, designed right-to-left in Arabic from the start so the typography, navigation and page flow suit the language instead of fighting it.',
					'results'    => 'The company went from no web presence to a site that reads naturally to an Arabic-speaking audience, with the range and the company positioned properly rather than translated into an English layout.',
				),
			),

			array(
				'title'      => 'Almatjar - Bilingual Watch Store',
				'excerpt'    => 'A bilingual Arabic and English WooCommerce watch store, with new pages and interface fixes built in Elementor.',
				'categories' => array( 'WooCommerce Development', 'Multilingual & RTL' ),
				'meta'       => array(
					'client'     => 'Almatjar',
					'industry'   => 'Watch Retail',
					'live_url'   => 'https://almatjar.ly',
					'services'   => 'WooCommerce Development, Bilingual Build, Bug Fixing, UI Work',
					'tech_stack' => 'WordPress, WooCommerce, Elementor',
					'challenge'  => 'An existing bilingual watch store had gaps in its content and interface problems that were getting in the way of customers reaching the products.',
					'solution'   => 'Built out the About and Contact pages in Elementor to match the store, then worked through the interface issues and bugs across the Arabic and English sides.',
					'results'    => 'The About and Contact pages now match the rest of the store, and the interface problems that were interrupting the path to the products are cleared on both the Arabic and English sides.',
				),
			),

			array(
				'title'      => 'Travoya Tours - Tourism Reservation Website',
				'excerpt'    => 'A tour operator website with a full booking system and dynamic tour content.',
				'categories' => array( 'WordPress Development', 'Custom Plugin Development' ),
				'meta'       => array(
					'client'     => 'Travoya Tours',
					'industry'   => 'Travel & Tourism',
					'year'       => '2025',
					'duration'   => '1 Month',
					'live_url'   => 'https://travoyatours.com',
					'services'   => 'WordPress Development, WP Travel Engine, Custom Plugin Development',
					'tech_stack' => 'WordPress, PHP, CSS, WP Travel Engine, Travel Monster, HTML',
					'challenge'  => 'An Egyptian tour operator needed travellers to be able to browse tours and book them on the site itself, with tour content that could be updated without touching code.',
					'solution'   => 'A custom design built on WP Travel Engine with the Travel Monster theme, wired to a booking system, plus custom plugin work to cover the parts the off-the-shelf setup did not handle. Tour content is dynamic, so the operator updates it directly.',
					'results'    => 'Travellers browse tours and complete the booking on the site itself, and the operator updates tour content directly without going back to a developer for every change.',
				),
			),

			array(
				'title'      => 'Champollion Hostel - Booking Website',
				'excerpt'    => 'A Downtown Cairo hostel website with room listings and integrated booking.',
				'categories' => array( 'WordPress Development' ),
				'meta'       => array(
					'client'     => 'Champollion Hostel',
					'industry'   => 'Hospitality',
					'live_url'   => 'https://champollion-hostel.com',
					'services'   => 'WordPress Development, Booking Integration',
					'tech_stack' => 'WordPress, Elementor',
					'challenge'  => 'A hostel in Downtown Cairo needed guests to see what each room actually offers and then book it, rather than being pushed to a third-party site to complete the reservation.',
					'solution'   => 'Room listings that lay out each room type clearly, with a booking integration wired in so the reservation happens through the site.',
					'results'    => 'Guests can see what each room type offers and reserve it through the site, instead of being handed to a third-party booking page partway through.',
				),
			),

			array(
				'title'      => 'Pharaoh Kids - German Kids Clothing Store',
				'excerpt'    => 'A bilingual German and English WooCommerce store for kids clothing, with ecommerce tracking and performance work.',
				'categories' => array( 'WooCommerce Development', 'Custom Plugin Development', 'Performance' ),
				'meta'       => array(
					'client'     => 'Pharaoh Kids',
					'industry'   => 'Kids Clothing Retail',
					'live_url'   => 'https://pharaohkids.de',
					'services'   => 'WooCommerce Development, Bilingual Copy, Analytics Setup, Performance Optimisation, Custom Plugin Development',
					'tech_stack' => 'WordPress, WooCommerce, WP Rocket, Google Tag Manager, GA4',
					'challenge'  => 'A German kids clothing store needed to sell in German and English, know what was actually happening in the funnel, and load fast enough not to lose shoppers. Existing product reviews also had to be brought across rather than lost.',
					'solution'   => 'Bilingual English and German store copy, ecommerce tracking through Google Tag Manager into GA4, and performance work using WP Rocket with used-CSS removal. Reviews were migrated with a custom WooCommerce review importer plugin written for the job. Brand navy #01142D carried through the design.',
					'results'    => 'The store sells in German and English, the existing product reviews carried across intact through the custom importer, and the funnel is measurable end to end in GA4 through Google Tag Manager.',
				),
			),

			array(
				'title'      => 'Queen Delivery - Delivery Company Website',
				'excerpt'    => 'A Canadian delivery company website with account-based service displays and system integrations.',
				'categories' => array( 'WordPress Development' ),
				'meta'       => array(
					'client'     => 'Queen Delivery',
					'industry'   => 'Logistics & Delivery',
					'live_url'   => 'https://queendelivery.ca',
					'services'   => 'WordPress Development, System Integration',
					'tech_stack' => 'WordPress, Elementor',
					'challenge'  => 'A delivery company whose customers do not all get the same services needed the site to show each account what applies to them, and to talk to the systems already running the business.',
					'solution'   => 'Service displays that change based on the account viewing them, with integrations connecting the site to the systems the company already uses.',
					'results'    => 'Each account sees the services that apply to it, and the site talks to the systems already running the business rather than standing apart from them.',
				),
			),

			array(
				'title'      => 'Hope Express - Delivery Company Website',
				'excerpt'    => 'A website for a Canadian delivery company.',
				'categories' => array( 'WordPress Development' ),
				'meta'       => array(
					'client'     => 'Hope Express',
					'industry'   => 'Logistics & Delivery',
					'live_url'   => 'https://hope-express.com',
					'services'   => 'WordPress Development',
					'tech_stack' => 'WordPress, Elementor',
					'challenge'  => 'A delivery company needed a site that sets out its services clearly and gives customers a straightforward way to get in touch.',
					'solution'   => 'A WordPress build covering the services, coverage and contact routes, structured so the company can keep it current itself.',
					'results'    => 'The company has a site that sets out its services and coverage clearly, with contact routes in place and content the team can keep current itself.',
				),
			),

			array(
				'title'      => 'JC Clayations - Handmade Clay Store',
				'excerpt'    => 'An e-commerce store for handmade clay products, built on WooCommerce.',
				'categories' => array( 'WooCommerce Development' ),
				'meta'       => array(
					'client'     => 'JC Clayations',
					'industry'   => 'Handmade Products',
					'live_url'   => 'https://jcclayations.ca',
					'services'   => 'WooCommerce Development, Checkout Setup',
					'tech_stack' => 'WordPress, WooCommerce',
					'challenge'  => 'A maker selling handmade clay pieces needed to sell them online, with a checkout that suits one-off and small-batch items.',
					'solution'   => 'A WooCommerce store built around the product range, with the checkout configured for how the pieces are actually sold.',
					'results'    => 'The handmade range is available to buy online, with a checkout set up for the way one-off and small-batch pieces actually sell.',
				),
			),

			array(
				'title'      => 'Bait Al Asaad - Spices Store',
				'excerpt'    => 'A spice e-commerce store for the Bahrain market.',
				'categories' => array( 'WooCommerce Development' ),
				'meta'       => array(
					'client'     => 'Bait Al Asaad',
					'industry'   => 'Food & Spices',
					'live_url'   => 'https://baitalasaad.com',
					'services'   => 'WooCommerce Development, Store Setup',
					'tech_stack' => 'WordPress, WooCommerce',
					'challenge'  => 'A Bahraini spice seller needed an online store where a wide range of similar-looking products stays easy to tell apart and easy to buy.',
					'solution'   => 'A WooCommerce store organised so the range is browsable by type, with product presentation that separates the products clearly.',
					'results'    => 'The spice range is online and browsable by type, so products that look alike on a shelf stay easy to tell apart and add to an order.',
				),
			),

			array(
				'title'      => 'Al Jawdah - Soup Brand Store',
				'excerpt'    => 'An e-commerce store for a Dubai-based soup brand.',
				'categories' => array( 'WooCommerce Development' ),
				'meta'       => array(
					'client'     => 'Al Jawdah',
					'industry'   => 'Food & Beverage',
					'live_url'   => 'https://aljawdah.shop',
					'services'   => 'WooCommerce Development, Store Setup',
					'tech_stack' => 'WordPress, WooCommerce',
					'challenge'  => 'A soup brand selling in the UAE needed an online store that carries the brand rather than looking like a generic product list.',
					'solution'   => 'A WooCommerce store built around the brand presentation, with the product range set up to sell directly to customers.',
					'results'    => 'The brand sells directly to customers online, with the range styled to the brand rather than as a generic product catalogue.',
				),
			),

			array(
				'title'      => 'Al Hqol Al Khadra - Fresh Produce',
				'excerpt'    => 'A fresh produce e-commerce site for a Saudi fruit and vegetable import and export business.',
				'categories' => array( 'WooCommerce Development' ),
				'meta'       => array(
					'client'     => 'Al Hqol Al Khadra',
					'industry'   => 'Fresh Produce, Import & Export',
					'live_url'   => 'https://alhqolalkhadra.com',
					'services'   => 'WooCommerce Development, Store Setup',
					'tech_stack' => 'WordPress, WooCommerce',
					'challenge'  => 'A Saudi fruit and vegetable business working in import and export needed a site that speaks to trade buyers as well as showing the produce range.',
					'solution'   => 'A WooCommerce build presenting the produce range alongside the import and export side of the business.',
					'results'    => 'The produce range is presented online alongside the import and export side of the business, so trade buyers and retail customers both find what they came for.',
				),
			),

			/*
			 * Not in the supplied list, but in the client roster as a completed
			 * build. Included so the portfolio is not missing it.
			 */
			array(
				'title'      => 'Pyramids Grocery - Egyptian Grocery Store',
				'excerpt'    => 'A Canadian e-commerce store selling Egyptian groceries.',
				'categories' => array( 'WooCommerce Development' ),
				'meta'       => array(
					'client'     => 'Pyramids Grocery',
					'industry'   => 'Grocery Retail',
					'live_url'   => 'https://pyramidsgrocery.com',
					'services'   => 'WooCommerce Development, Store Setup',
					'tech_stack' => 'WordPress, WooCommerce',
					'challenge'  => 'An Egyptian grocery business in Canada needed to sell a broad everyday range online, where customers are shopping for several items at once rather than a single product.',
					'solution'   => 'A WooCommerce store organised by grocery category so a multi-item shop stays quick, from browsing through to checkout.',
					'results'    => 'The everyday grocery range is online and organised by category, so a multi-item shop stays quick from browsing through to checkout.',
				),
			),
		);
	}

	/**
	 * The filter vocabulary.
	 *
	 * WHY THIS IS SEPARATE FROM THE PROSE FIELDS
	 *
	 * The services and industry written on each project are descriptive: "Watch
	 * Retail", "WooCommerce Development, Bilingual Build, Bug Fixing, UI Work".
	 * Fed straight into a taxonomy they produce a filter where nearly every term
	 * matches one project, which is no filter at all, and splits the same search
	 * term across several near-duplicate archives.
	 *
	 * So the taxonomy terms are a deliberately short, deliberately broad list
	 * chosen here, while the prose stays untouched in the meta for the project
	 * page. Every term below covers at least two projects except Real Estate,
	 * where there genuinely is one.
	 *
	 * Terms are phrased the way someone searches for the work: the service first
	 * ("WordPress Development", not "Development, WordPress"), the industry in
	 * the plain words a client would use for their own sector.
	 *
	 * Keyed by live URL, which is what the importer matches on.
	 *
	 * @return array[]
	 */
	public static function taxonomy_map() {

		return array(

			'https://seaqui.com' => array(
				'category' => array( 'Booking & Listing Websites' ),
				'service'  => array( 'WordPress Development', 'Elementor Web Design', 'Multilingual & RTL Websites', 'Website Speed & SEO Optimization' ),
				'industry' => array( 'Real Estate' ),
			),

			'https://almatjarhome.ly' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development', 'Multilingual & RTL Websites' ),
				'industry' => array( 'Ecommerce & Retail' ),
			),

			'https://sanowber.ly' => array(
				'category' => array( 'Corporate & Business Websites' ),
				'service'  => array( 'WordPress Development', 'Elementor Web Design', 'Multilingual & RTL Websites' ),
				'industry' => array( 'Ecommerce & Retail' ),
			),

			'https://almatjar.ly' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development', 'Elementor Web Design', 'Multilingual & RTL Websites' ),
				'industry' => array( 'Ecommerce & Retail' ),
			),

			'https://travoyatours.com' => array(
				'category' => array( 'Booking & Listing Websites' ),
				'service'  => array( 'WordPress Development', 'Booking & Reservation Systems', 'Custom Plugin Development' ),
				'industry' => array( 'Travel & Hospitality' ),
			),

			'https://champollion-hostel.com' => array(
				'category' => array( 'Booking & Listing Websites' ),
				'service'  => array( 'WordPress Development', 'Elementor Web Design', 'Booking & Reservation Systems', 'System & API Integration' ),
				'industry' => array( 'Travel & Hospitality' ),
			),

			'https://pharaohkids.de' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development', 'Multilingual & RTL Websites', 'Website Speed & SEO Optimization', 'System & API Integration', 'Custom Plugin Development' ),
				'industry' => array( 'Ecommerce & Retail' ),
			),

			'https://queendelivery.ca' => array(
				'category' => array( 'Corporate & Business Websites' ),
				'service'  => array( 'WordPress Development', 'Elementor Web Design', 'System & API Integration' ),
				'industry' => array( 'Logistics & Delivery' ),
			),

			'https://hope-express.com' => array(
				'category' => array( 'Corporate & Business Websites' ),
				'service'  => array( 'WordPress Development', 'Elementor Web Design' ),
				'industry' => array( 'Logistics & Delivery' ),
			),

			'https://jcclayations.ca' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development' ),
				'industry' => array( 'Ecommerce & Retail' ),
			),

			'https://baitalasaad.com' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development' ),
				'industry' => array( 'Food & Beverage' ),
			),

			'https://aljawdah.shop' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development' ),
				'industry' => array( 'Food & Beverage' ),
			),

			'https://alhqolalkhadra.com' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development' ),
				'industry' => array( 'Food & Beverage' ),
			),

			'https://pyramidsgrocery.com' => array(
				'category' => array( 'Ecommerce Websites' ),
				'service'  => array( 'WordPress Development', 'WooCommerce Development' ),
				'industry' => array( 'Food & Beverage' ),
			),
		);
	}

	/**
	 * The terms one project should carry.
	 *
	 * @param array $project
	 * @return array{category:string[],service:string[],industry:string[]}
	 */
	public static function terms_for( $project ) {

		$map = self::taxonomy_map();
		$url = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';

		if ( $url && isset( $map[ $url ] ) ) {
			return $map[ $url ];
		}

		// Nothing mapped: fall back to the prose, which is what the first
		// version of this importer did.
		return array(
			'category' => isset( $project['categories'] ) ? (array) $project['categories'] : array(),
			'service'  => isset( $project['meta']['services'] ) ? array_map( 'trim', explode( ',', (string) $project['meta']['services'] ) ) : array(),
			'industry' => isset( $project['meta']['industry'] ) ? array( $project['meta']['industry'] ) : array(),
		);
	}

	/**
	 * Every term this importer will create, for the admin screen.
	 *
	 * @param string $which category|service|industry
	 * @return array name => number of projects
	 */
	public static function term_counts( $which ) {

		$counts = array();

		foreach ( self::taxonomy_map() as $terms ) {
			foreach ( (array) $terms[ $which ] as $name ) {
				$counts[ $name ] = isset( $counts[ $name ] ) ? $counts[ $name ] + 1 : 1;
			}
		}

		arsort( $counts );

		return $counts;
	}

	/**
	 * Field names intentionally left empty, shown on the admin screen so the
	 * gap is visible rather than discovered later on a live page.
	 *
	 * @return string[]
	 */
	public static function blank_fields() {
		return array(
			'metric_1_value',
			'metric_1_label',
			'metric_2_value',
			'metric_2_label',
			'metric_3_value',
			'metric_3_label',
			'testimonial',
			'testimonial_author',
			'gallery',
		);
	}
}
