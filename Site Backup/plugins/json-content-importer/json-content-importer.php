<?php
/*
Plugin Name: Get Use APIs - JSON Content Importer
Plugin URI: https://json-content-importer.com/
Description: Plugin to import, cache and display a JSON-Feed. Display is done with wordpress-shortcode or gutenberg-block.
Version: 2.0.8
Author: Bernhard Kux
Author URI: https://json-content-importer.com/
Text Domain: json-content-importer
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

/* block direct requests */
if ( !function_exists( 'add_action' ) ) {
	esc_html_e('Hello, this is a plugin: You must not call me directly.', 'json-content-importer');
	exit;
}
defined('ABSPATH') OR exit;
define( 'JCIFREE_VERSION', '2.0.8' );
define( 'JCIFREE_UO_AUTOLOAD', FALSE); # FALSE: update_option does not load values everytime, but only if really needed

function jcifree_getjson($api_set, $convert_xmlcsv_to_json=FALSE, $cacheinsec=0, $debugmode=FALSE) {
	# get data to API-Access-Set
	$apiitems = get_option( 'jci_free_api_access_items' );
	$apiitemsArr = json_decode($apiitems, TRUE);
	$aasfound = FALSE;
	foreach($apiitemsArr as $aask => $aasv) {
		$selcaasSet = $aasv["set"] ?? NULL;
		if (!is_null($selcaasSet)) {
			$selcaas = $selcaasSet["nameofselectedjas"] ?? '';
			if (!empty($selcaas) && ($api_set==$selcaas)) {
				$apiaccessetarr = $aasv;
				$apiaccesset = $aask;
				$aasfound = TRUE;
			}
		}
	}
	
	if (!$aasfound){
		return 1;
	}
	
	if ($debugmode) {	echo "Name of API-Access-Set: ". esc_html($api_set)."<br>";	echo "ID of API-Access-Set: ". esc_html($apiaccesset)."<br>";	}
	#return json_encode($apiaccessetarr);

	# load API-Access-Set
	if ((!class_exists('jci_free_request'))) {
		require_once plugin_dir_path( __FILE__ ) . 'getlib.php';
	}
	$jci_free_request = new jci_free_request();
	$formdata["accset"] = $api_set;
	$loadaccset = TRUE;
	$formdata = $jci_free_request->setDataFromAccSet($apiaccessetarr, $formdata, $loadaccset);
	if ((!class_exists('jci_free_request_prepare'))	) {
		require_once plugin_dir_path( __FILE__ ) . 'lib/lib_request.php';
	}
	$jci_request_handler = new jci_free_request_prepare($formdata);
	$curloptions = $jci_request_handler->getCurlOptionsString();
	$curloptions4Request = $jci_request_handler->getCurlOptions4Request($curloptions);

	# BEGIN cache: The  retrieved JSON from the API-Access-Set is NOT cached
	$cacheEnable = FALSE;  
	$cacheFile = WP_CONTENT_DIR.'/cache/jsoncontentimporter/'.sanitize_file_name(md5($formdata["jciurl"])) . ".cgi";

	## cache set in basic settings?
	$cacheTime = 0;
	$cacheExpireTime = 0;
	if (1==get_option('jci_enable_cache')) {
		$cacheTime = get_option('jci_cache_time');  # max age of cachefile: if younger use cache, if not retrieve from web
		$format = get_option('jci_cache_time_format');
		$cacheExpireTime = strtotime(gmdate('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));
		if ($debugmode) {		echo "Cache active, set in Basic Settings: ". esc_html(time()-$cacheExpireTime). " seconds, Folder: ".esc_html($cacheFile)."<br>";		}
	}

	# cache set in function: override basic settings
	if ((int) $cacheinsec>0) {
		$cacheEnable = TRUE;  
		$cacheExpireTime = $cacheinsec;
		if ($debugmode) {		echo "Cache active, set in Function:  ". esc_html($cacheExpireTime). " seconds, Folder: ".esc_html($cacheFile)."<br>";	}
	}
	# END Cache 

	# BEGIN LOAD
	if ((!class_exists('FileLoadWithCacheFreeV2'))	|| (!class_exists('JSONdecodeFreeV2'))) {
		require_once plugin_dir_path( __FILE__ ) . 'class-fileload-cache-v2.php';
	}
	$debugModeIsOn = FALSE;
	$debugLevel = 0;
	$header = "";
	$urlencodepostpayload = "";
	$encodingofsource = "";
	$httpstatuscodemustbe200 = "no";
	$auth = "";
	$showapiresponse = FALSE;
	$followlocation = TRUE; # follow 301 etc.
	
	$methodtechTmp = $formdata["methodtech"];
	$methodTmp = $formdata["method"];
	#return "$api_set-$curloptions4Request -".json_encode($formdata);
	
	$selectedmethod = $jci_free_request->getSelectedmethod($methodtechTmp, $methodTmp);
	if ($debugmode) {			echo "Request:  ". esc_html(wp_json_encode($formdata))."<br>";		}
	$fileLoadWithCacheObj = new FileLoadWithCacheFreeV2(
		$formdata["jciurl"], $formdata["timeout"], $cacheEnable, $cacheFile, $cacheExpireTime, 
		$selectedmethod, NULL, '', '',
		$formdata["payload"], $header, $auth, $formdata["payload"],
		$debugLevel, $debugModeIsOn, $urlencodepostpayload, $curloptions4Request,
		$httpstatuscodemustbe200, $encodingofsource, $showapiresponse, $followlocation 
	);
	$fileLoadWithCacheObj->retrieveJsonData();
	$feedData = $fileLoadWithCacheObj->getFeeddataWithoutpayloadinputstr();  
	$httpcode = $fileLoadWithCacheObj->getErrormsgHttpCode();
	if ($debugmode) {	echo "HTTP-Code:  ". esc_html($httpcode)."<br>";		}
	if ($debugmode) {	echo "API-Answer:  ". esc_html($feedData)."<br>";		}
	
	if (TRUE===$convert_xmlcsv_to_json) {   # in case csv or XML is given by the API
		$convertJsonNumbers2Strings = TRUE; # default!
		$jsonDecodeObj = new JSONdecodeFreeV2($feedData, TRUE, $debugLevel, $debugModeIsOn, $convertJsonNumbers2Strings, $cacheFile, $fileLoadWithCacheObj->getContentType(), 
				$formdata["indataformat"], $formdata["csvdelimiter"], $formdata["csvline"],
				$formdata["csvenclosure"], $formdata["csvskipempty"], $formdata["csvescape"]
		);

		$vals = $jsonDecodeObj->getJsondata();
		$receivedData = wp_json_encode($vals);	
		return $receivedData;
	}
	return $feedData;
}



#function jci_i18n_init() {
	#$pd = dirname(plugin_basename(__FILE__)	).'/languages/';
	#$lt = load_plugin_textdomain('json-content-importer', false, $pd);
#}

/*
function jci_block_plugin_de_translation($mofile, $domain) {
	if ('json-content-importer' === $domain && strpos($mofile, 'de_DE.mo') !== false) {
		$custom_translation = WP_PLUGIN_DIR. "/".dirname(plugin_basename(__FILE__)	).'/languages/json-content-importer-de_DE.mo';
		if (file_exists($custom_translation)) {
            return $custom_translation;
        } else {
            return $mofile;
        }
    }
    return $mofile;
}
*/
#if (is_admin()) {
	#add_action('plugins_loaded', 'jci_i18n_init');
	#add_filter('load_textdomain_mofile', 'jci_block_plugin_de_translation', 10, 2);
#}

#function jci_load_css() {
#	wp_enqueue_style('jci-style', plugin_dir_url(__FILE__) . 'css/jci.css',null, 1);
#}
#add_action('admin_enqueue_scripts', 'jci_load_css', 100);

class jciGutenberg {
	private $gutenbergIsActive = FALSE;
	private $gutenbergPluginIsActive = FALSE;
	private $itIsWP5 = FALSE;
	private $gutenbergMessage = ""; 

	function __construct() {
		$this->buildGutenbergMessage(5);
		$this->checkGutenbergIsActive();
    }	
	
	private function checkGutenbergIsActive() {
		$jci_gutenberg_off_option_value = get_option('jci_gutenberg_off') ?? '';
		if (1==$jci_gutenberg_off_option_value) {
			$this->buildGutenbergMessage(1);
		} else {
			# previous to 5.0 the constant GUTENBERG_VERSION indicates, that the Gutenberg-Plugin is active
			$this->gutenbergPluginIsActive = (true === defined('GUTENBERG_VERSION'));
			if ($this->gutenbergPluginIsActive) {
				$this->gutenbergIsActive = TRUE;
				$this->buildGutenbergMessage(2);
			}
			# things change from 5.0 on
			$this->itIsWP5 = version_compare(get_bloginfo('version'),'5.','>='); # ????? 5. // 5.0
			if ($this->itIsWP5) {
				# maybe the classic editor plugin is active in wp 5.0
				if ( class_exists( 'Classic_Editor' ) ) {
				#if (is_plugin_active( 'classic-editor/classic-editor.php' )) {
					$this->buildGutenbergMessage(3);
				} else {
					$this->gutenbergIsActive = TRUE;
					$this->buildGutenbergMessage(4);
				}
			}
		}
		define( 'JCI_GUTENBERG_PLUGIN_MESSAGE', $this->gutenbergMessage );
	}

	public function getGutenbergIsActive() {
		return $this->gutenbergIsActive;
	}

	#private function buildGutenbergMessage($color, $message)
	private function buildGutenbergMessage($message)
	{
		$this->gutenbergMessage = $message;#'<a style="color:'.$color.'; font-weight: bold;" href="https://wordpress.org/gutenberg/" target="_blank">'.$message.'</a>';
	}
}

if (!isset($jciGB)) {
	$jciGB = new jciGutenberg();
}


if ( $jciGB->getGutenbergIsActive() ) {
	define( 'JCI_FREE_BLOCK_VERSION', '0.2' );
	if ( ! defined( 'JCI_FREE_BLOCK_NAME' ) ) {
		define( 'JCI_FREE_BLOCK_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
	}
	if ( ! defined( 'JCI_FREE_BLOCK_DIR' ) ) {
		define( 'JCI_FREE_BLOCK_DIR', WP_PLUGIN_DIR . '/' . JCI_FREE_BLOCK_NAME );
	}
	if ( ! defined( 'JCI_FREE_BLOCK_URL' ) ) {
		define( 'JCI_FREE_BLOCK_URL', WP_PLUGIN_URL . '/' . JCI_FREE_BLOCK_NAME );
	}
	require_once( JCI_FREE_BLOCK_DIR . '/block/index.php' );
}


// add Quicktag to Text Editor
function jcifree_add_quicktags() {
	if ( wp_script_is( 'quicktags' ) ) { 
		$jsonexample = plugin_dir_url( __FILE__ )."json/gutenbergblockexample1.json";
		$template = "{start}<br>{subloop-array:level2:-1}{level2.key}<br>{subloop:level2.data:-1}id: {level2.data.id}<br>{/subloop:level2.data}{/subloop-array:level2}";
		?>
		<script type="text/javascript">
			window.onload = function() {
				QTags.addButton( 'jcifreequicktag', 'JSON Content Importer', '[jsoncontentimporter url=<?php echo esc_attr($jsonexample); ?> debugmode=10 basenode=level1]<?php echo esc_attr($template); ?>[/jsoncontentimporter]', '', '', '', 1 );
			};
		</script>
	<?php }

}
add_action( 'admin_print_footer_scripts', 'jcifree_add_quicktags' );

if (!function_exists('jci_addlinks')) {
	function buildGutenbergMessage($color, $message) {
		return '<a style="color:'.$color.'; font-weight: bold;" href="https://wordpress.org/gutenberg/" target="_blank">'.$message.'</a>';
	}

	function jci_addlinks($links, $file) {
		if ( strpos( $file, 'json-content-importer.php' ) !== false ) {
			$gbmsg = "";
			if ( defined( 'JCI_GUTENBERG_PLUGIN_MESSAGE' ) ) {
				if (JCI_GUTENBERG_PLUGIN_MESSAGE==1) {
					$gbmsg = buildGutenbergMessage("#f00", __('Gutenberg-Mode of Plugin switched off in Options', 'json-content-importer'));
				} else if (JCI_GUTENBERG_PLUGIN_MESSAGE==2) {
					$gbmsg = buildGutenbergMessage("#3db634", __('Gutenberg-Plugin-Mode', 'json-content-importer'));
				} else if (JCI_GUTENBERG_PLUGIN_MESSAGE==3) {
					$gbmsg = buildGutenbergMessage("#f00", __('No Gutenberg: Classic Editor Plugin active', 'json-content-importer'));
				} else if (JCI_GUTENBERG_PLUGIN_MESSAGE==4) {
					$gbmsg = buildGutenbergMessage("#3db634", __('JCI Block is available', 'json-content-importer'));
				} else if (JCI_GUTENBERG_PLUGIN_MESSAGE==5) {
					$gbmsg = buildGutenbergMessage("#f00", __('Gutenberg not available', 'json-content-importer'));
				}
			}
			$link2pro = array(
				$gbmsg,
				'<a style="color:#3db634; font-weight: bold;" href="https://json-content-importer.com/welcome-to-the-home-of-the-json-content-importer-plugin/" target="_blank">'.__('Upgrade to PRO-Version', 'json-content-importer').'</a>'
			);
			return array_merge( $links, $link2pro);
		}
		return $links;
	}
	add_filter( 'plugin_row_meta', 'jci_addlinks', 10, 2 );
}


if (is_admin()) {
	require_once plugin_dir_path( __FILE__ ) . '/options.php';
}
require_once plugin_dir_path( __FILE__ ) . '/class-json-content-importer.php';
$JsonContentImporter = new JsonContentImporter();

/* extension hook BEGIN */
do_action('json_content_importer_extension');
/* extension hook END */

## API for JCI-Block
/**/
function jci_restapi() {
	register_rest_route(
		'wp/jcifree/v1',
		'/get/crte/',
		array(
			'callback'            => function ( $request ) {
				$nonce = $request->get_header('X-WP-Nonce');
				$ret[] = Array();
				if (is_null($nonce)) {
					$ret["template"]  ="permission denied";
					return wp_json_encode($ret);
				}

				$url = isset( $request['url'] ) ? esc_attr( $request['url'] ) : null;
				$basenode = isset( $request['basenode'] ) ? esc_attr( $request['basenode'] ) : null;
				$ret[] = Array();
				$ret["useragent"]  = get_option( "jci_http_header_default_useragent");
				if (preg_match("/^\//", $url)) {
					$url = WP_PLUGIN_URL.$url;
				}
				$ret["url"]  = $url;
				if ("e1"==$ret["url"]) {
					$example_url = '/json-content-importer/json/gutenbergblockexample1.json';
					$ret["url"] = WP_PLUGIN_URL.$example_url; 
				} 
				
				$ret["basenode"] = $basenode;
 
				require_once plugin_dir_path( __FILE__ ) . '/class-fileload-cache.php';
				
				$urlgettimeout = 5;
				$cacheEnable = FALSE;
				$cacheFile = '';
				$cacheExpireTime = 0;
				$oauth_bearer_access_key = "";
				$http_header_default_useragent_flag = "";
				$debugmode = "";
				$fallback2cache = 0;
				$removewrappingsquarebrackets = FALSE;
				
				$fileLoadWithCacheObj = new FileLoadWithCache($ret["url"], $urlgettimeout, $cacheEnable, $cacheFile, $cacheExpireTime, $oauth_bearer_access_key, $http_header_default_useragent_flag, $debugmode, $fallback2cache, $removewrappingsquarebrackets);
				$fileLoadWithCacheObj->retrieveJsonData();
				$feedData = $fileLoadWithCacheObj->getFeeddata();
					
				$jsonArr = json_decode($feedData);
				if (is_null($jsonArr)) {
					$ret["template"]  ="Invalid JSON: $url";
					return wp_json_encode($ret);
				}
				
				require_once plugin_dir_path( __FILE__ ) . '/lib/JsonToTemplateConverter.php';

				$j2t = new JsonToTemplateConverter($jsonArr, $basenode);
				$res = $j2t->getTemplate();
				$ret["template"] = $res;
				return wp_json_encode($ret);
			},
			'permission_callback' => function () {
				return current_user_can('edit_posts');
			},			
			'methods'             => 'GET',
		)
	);
}
add_action( 'rest_api_init', 'jci_restapi' );


// REST for POST JCI-Block - begin
function register_jcifree_block_restapi() {
    register_rest_route(
		'wp/jcifree/v1',
		'/post/block-renderer/',
		array(
        'methods' => 'POST',
        'callback' => 'jcifree_handle_block_endpoint',
        'permission_callback' => function() {
			return current_user_can('edit_posts');
        }
    ));
}

add_action('rest_api_init', 'register_jcifree_block_restapi');

function jcifree_handle_block_endpoint(WP_REST_Request $request) {
	$val_jci_contributor_off = get_option('jci_contributor_off') ?? 1;
	if (1==$val_jci_contributor_off) {
		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		if ( in_array( 'contributor', $roles, true ) ) {
			$renderedContent = "Access denied: You must be an Administrator, Editor, or Author to use the JCI plugin. Your current role 'Contributor' does not have the required permissions.";
			return new WP_REST_Response(array('renderedContent' => $renderedContent));
		}
	}

	$nonce = $request->get_header('X-WP-Nonce');
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		$renderedContent = 'Permission denied for JCIfree Block REST-API';
		return new WP_REST_Response(array('renderedContent' => $renderedContent));
    }
	$gp = $request->get_params();
	$attributes = $gp["attributes"];	
	
	$renderedContent = jci_free_render( $attributes, "" );
    return new WP_REST_Response(array('renderedContent' => $renderedContent));
}
// REST for POST JCI-Block - end

# only if wp-contact-form-7 is active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	include_once( 'otherplugins/cf7.php' );
}
?>