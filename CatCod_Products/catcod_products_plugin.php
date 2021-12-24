<?php
/*
Plugin Name: Product List
Version: 1.0
Description: Show products of a particular product line, brought in from API
Author: Catrina Zapata
Author URI: https://www.catrina.codes
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: catrina.codes-product-list
*/

/*
Header above required to register plugin
*/

/*
EXTRA SCRIPTS/STYLING
it is optional to load specific JS or CSS needed to style plugin view
*/
	function include_product_asset_files() 
	{
	// JS
	wp_register_script('prefix_bootstrap_pr', '//cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js');
	wp_enqueue_script('prefix_bootstrap_pr');

	// CSS
	wp_register_style('prefix_bootstrap_pr', '//cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css');
	wp_enqueue_style('prefix_bootstrap_pr');
	
	wp_register_style('prefix_animatecss_pr', '//cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
	wp_enqueue_style('prefix_animatecss_pr');

	wp_register_style('catcod-stylesheet_pr', plugins_url('/css/style.css', __FILE__), false, '1.0.0', 'all');
	wp_enqueue_style('catcod-stylesheet_pr');
	}
	add_action('wp_enqueue_scripts', 'include_product_asset_files' );

/*
WORDPRESS SHORTCUT
Create shortcode to be used for plugin
Params: add_shortcode(shortcode_name, nameOfPluginFunctionBelow)
*/
	function shortcode_product_init(){
		add_shortcode('catcod_products', 'catcod_product');
	}
	add_action('init', 'shortcode_product_init');

/*
WORDPRESS PLUGIN MAIN FUNCTION
THIS PLUGIN ACCEPTS PARAMETERS: $atts
*/
	function catcod_product($atts)
	{
		//set up param name (line) and value '' (blank) this is added in shortcode to pass value
		$a = shortcode_atts(array(
			'line' => ''
		), $atts);

		/******************
		Begin API Call
		******************/
		$chosenLine = esc_attr($a['line']);
		$chosenLine = strtolower($chosenLine);
		$chosenLine = str_replace(" ","-", $chosenLine);

		$curl = curl_init();

		$apiUrl = esc_url("https://coretowp.azurewebsites.net/products/") . $chosenLine;

		// OPTIONS:
		//curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
		curl_setopt($curl, CURLOPT_URL, $apiUrl);
		curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,  2);
		curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 2);

		// EXECUTE:
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) 
		{
			return  "cURL Error #:" . $err;
		}

		$jsonObj = json_decode($response);
		/******************
		End API
		******************/

		/******************
		Begin Design
		******************/
		//must create an $output variable to return.  using 'echo' spits out html as page loads, and often before api is able to retrieve the needed data	
		//set as empty in case something needed before major loop
		$outputLoop = '';
		$outputHeader = '
		<div class="container">
			<div class="row row-cols-2">';

			foreach ($jsonObj as $item) 
			{
				$truncatedText = preg_replace('/((\w+\W*){'.(20).'}(\w+))(.*)/', '${1}', $item->productDescription) . " ...";

				$outputLoop = $outputLoop .
					'<div class="col">
						<div class="card shadow p-3 mb-5 rounded animate__animated animate__fadeIn">
							<div class="card-body">
								<h5 class="card-title">' . $item->productName . '</h5>
								<h6 class="card-subtitle mb-2 text-muted"><s>$' . $item->msrp . '</s> $' . $item->buyPrice . '</h6>
								<p class="card-text">' . $truncatedText . '</p>';
								/*<a href="/dealers" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="fa fa-search"></i> Where To Buy</a>';*/

				$outputLoop = $outputLoop . '
							</div>
						</div>
					</div>';
					//close foreach
			}

			$outputFooter = '
			</div>
		</div>';
		/******************
		End Design
		******************/

		$output = $outputHeader . $outputLoop . $outputFooter;

		//close main WordPress function, returning design string
		return $output;
}