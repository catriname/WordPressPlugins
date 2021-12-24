<?php
/*
Plugin Name: Product Line List
Version: 1.0
Description: Show products lines brought in from API
Author: Catrina Zapata 
Author URI: https://www.catrina.codes
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: catrina.codes-product-line-list
*/

/*
Header above required to register plugin
*/

/*
EXTRA SCRIPTS/STYLING
it is optional to load specific JS or CSS needed to style plugin view
*/
	function include_product_line_asset_files()
	{
		// JS
		wp_register_script('prefix_bootstrap-prl', '//cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js');
		wp_enqueue_script('prefix_bootstrap-prl');

		// CSS
		wp_register_style('prefix_bootstrap-prl', '//cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css');
		wp_enqueue_style('prefix_bootstrap-prl');
		
		wp_register_style('prefix_animatecss_prl', '//cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
		wp_enqueue_style('prefix_animatecss_prl');

		wp_register_style('catcod-stylesheet-prl', plugins_url('/css/style.css', __FILE__), false, '1.0.0', 'all');
		wp_enqueue_style('catcod-stylesheet-prl');
	}
	add_action('wp_enqueue_scripts', 'include_product_line_asset_files');

/*
WORDPRESS SHORTCUT
Create shortcode to be used for plugin
Params: add_shortcode(shortcode_name, nameOfPluginFunctionBelow)
*/
	function shortcode_product_line_init()
	{
		add_shortcode('catcod_product_lines', 'catcod_product_line');
	}
	add_action('init', 'shortcode_product_line_init');

/*
WORDPRESS PLUGIN MAIN FUNCTION
*/
	function catcod_product_line()
	{
		/******************
		Begin API Call
		******************/

		$curl = curl_init();

		$apiUrl = esc_url("https://coretowp.azurewebsites.net/productlines/");

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

		if ($err) {
			return  "cURL Error #:" . $err;
		}

		//convert response from api to jsonObj
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
			
			foreach ($jsonObj as $item) {

				if ($item->image != null) {
					$imageUrl = esc_url($item->image);
				} else {
					//if no image return, use a greyed placeholder
					$imageUrl = "https://via.placeholder.com/200";
				}

				$productLink = "/" . str_replace(" ", "-", $item->productLine1);
				$productLink = strtolower($productLink);

				$truncatedText = preg_replace('/((\w+\W*){' . (20) . '}(\w+))(.*)/', '${1}', $item->textDescription) . " ...";

				$outputLoop = $outputLoop .
					'<div class="col">
					<div class="card shadow p-3 mb-5 rounded animate__animated animate__fadeIn">
						<a href="' . $productLink . '">
							<img class="card-img-top" alt="' . $item->productLine1 . '" src="' . $imageUrl . '">
						</a>

							<div class="card-body">
								<div class="card-title fs-3">' . $item->productLine1 . '</div>
								<div class="fs-6">' . $truncatedText . '</div>';

				$outputLoop = $outputLoop . '
							<a href="' . $productLink . '" class="btn btn-secondary mt-3">Read More</a>
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