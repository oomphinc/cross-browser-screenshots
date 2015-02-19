<?php
/**
 * Plugin Name: Oomph Cross Browser Screenshots
 * Plugin URI: http://oomphinc.com
 * Description: Upload a list of urls and a new post with screenshot results will magically appear
 * Author: hirozed oomphinc
 * Author URI: http://oomphinc.com
 * Version: 1.0
 */
class Oomph_CBT{

    // Define and register singleton
    private static $instance = false;
    public static function instance() {
    if( !self::$instance )
      self::$instance = new Oomph_CBT; // MUST BE UPDATED WITH CLASS NAME
    return self::$instance;
    }
    // Disallow clone() of object
    private function __clone() {
    }

    const EOL =  "\n";
    const TAB = "\t";

    var $option_name = 'cross-browser-screenshots';
    var $title = 'Oomph Cross Browser Screenshots';
    var $errors = false;
    var $fields = array(
        'username' => array(
            'label' => 'Username',
            'placeholder' => 'Username',
            'description' => 'Cross Browser Testing Username'
        ),
        'auth_key' => array(
            'label' => 'Auth Key',
            'placeholder' => 'Auth Key',
            'description' => 'Cross Browser Testing Auth Key found on your Account page',
        ),
        'browser_list' => array(
            'label' => 'Browser List',
            'placeholder' => 'Browser List',
            'description' => 'If you have configured a browser list, enter it here.',
        ),
        'basic_auth_id' => array(
            'label' => 'Basic Auth ID',
            'placeholder' => 'Basic Auth ID',
            'description' => 'If the site requires basic authentication, enter the id',
        ),
        'basic_auth_pw' => array(
            'label' => 'Basic Auth Password',
            'placeholder' => 'Basic Auth Password',
            'description' => 'If the site requires basic authentication, enter the password',
        ),
        'post_title' => array(
            'label' => 'Post Title',
            'placeholder' => 'Post Title',
            'description' => 'Enter the title for the screenshots post',
        ),
        'url_list' => array(
            'label' => 'URL List',
            'placeholder' => 'URL List',
            'description' => 'Enter the list of URLs to process',
        ),
    );

	var $baseUrl = "http://crossbrowsertesting.com/api/v3/screenshots";

	var $current_test = NULL;
	var $allTests = array();
	var $recordCout = 0;

	function __construct() {
        add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'action_admin_init' ) );
    }

   /*
    * Create Option Page
    * @uses add_submenu_page
    * @return null
    */
    function action_admin_menu(){
		add_submenu_page( 'options-general.php', $this->title, $this->title, 'administrator', $this->option_name, array( $this, 'form' ) );
	}

	/*
	 * Register option and validation callback
	 * @uses register_setting
	 * @action admin_init
	 * @return null
	 */
	function action_admin_init() {
		register_setting( $this->option_name, $this->option_name, array( $this, 'validate_options' ) );
        if( $_GET[ 'generate_screenshots' ] == 'y' ){
            $this->generate_screenshots();
        }
    }

    /**
	 * Admin Init
	 */
	function validate_options( $input ){
		foreach( $input as $field => $result ){
			switch( $field ){
				case 'username' :
					if( isset( $input[ 'username' ] )){
                        $input[ 'username' ] = sanitize_text_field( $input[ 'username' ] );
					}
					else {
						$this->errors( $field, 'Missing value / not array' );
					}
					break;
				case 'auth_key' :
					if( isset( $input[ 'auth_key' ] )){
                        $input[ 'auth_key' ] = sanitize_text_field( $input[ 'auth_key' ] );
					}
					else {
						$this->errors( $field, 'Missing value / not array' );
					}
					break;
				case 'browser_list' :
					if( isset( $input[ 'browser_list' ] )){
                        $input[ 'browser_list' ] = sanitize_text_field( $input[ 'browser_list' ] );
					}
					break;
				case 'basic_auth_id' :
					if( isset( $input[ 'basic_auth_id' ] )){
                        $input[ 'basic_auth_id' ] = sanitize_text_field( $input[ 'basic_auth_id' ] );
					}
					break;
				case 'basic_auth_pw' :
					if( isset( $input[ 'basic_auth_pw' ] )){
                        $input[ 'basic_auth_pw' ] = sanitize_text_field( $input[ 'basic_auth_pw' ] );
					}
					break;
				case 'post_title' :
					if( isset( $input[ 'post_title' ] )){
                        $input[ 'post_title' ] = sanitize_text_field( $input[ 'post_title' ] );
					}
					else {
						$this->errors( $field, 'Missing value / not array' );
					}
					break;
				case 'url_list' :
                    if( isset( $input[ 'url_list' ] ) ){
					}
					else {
						$this->errors( $field, 'Missing value / not array' );
					}
					break;
				default :

					break;
			}

		}
		if( !$this->errors )
			return $input;
	}

	/**
	 * Capture an error and return false
	 * @uses error_log
	 * @return string
	 */
	function error( $code = 'error', $string = "There was an error" ) {
		if( !$this->errors )
			$this->errors = new WP_Error( $code, $string );
		else
			$this->errors->add( $code, $string );

		error_log( "now have error: " . var_export( $this->errors, true ) );
		return false;
	}

	/**
	 * Display error by code
	 * @uses esc_html
	 * @return html
	 */
	function display_error( $code ) {
		if( !$this->errors )
			return;

		foreach( $this->errors->get_error_messages( $code ) as $message )
			echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Emit the option form
	 */
	function form() {
		$options = get_option( $this->option_name );
	?>
	<div class="wrap">
		<h2><?php echo $this->title; ?></h2>

        <p>
            <a href="<?php echo admin_url( 'options-general.php?page=cross-browser-screenshots' ); ?>&generate_screenshots=y">Click to Generate Sceenshots</a>
        </p>

		<form method="post" id="<?php echo $this->option_name ?>" action="options.php">

			<?php settings_fields( $this->option_name ); ?>
			<p>
				<label for="username">
					<strong><?php echo esc_html( $this->fields['username']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[username]" value="<?php  if( isset( $options[ 'username' ] ) ) echo $options[ 'username' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['username']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['username']['description'] ); ?></span>
			</p>
			<p>
				<label for="auth_key">
					<strong><?php echo esc_html( $this->fields['auth_key']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[auth_key]" value="<?php  if( isset( $options[ 'auth_key' ] ) ) echo $options[ 'auth_key' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['auth_key']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['auth_key']['description'] ); ?></span>
			</p>
			<p>
				<label for="browser_list">
					<strong><?php echo esc_html( $this->fields['browser_list']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[browser_list]" value="<?php  if( isset( $options[ 'browser_list' ] ) ) echo $options[ 'browser_list' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['browser_list']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['browser_list']['description'] ); ?></span>
			</p>
			<p>
				<label for="basic_auth_id">
					<strong><?php echo esc_html( $this->fields['basic_auth_id']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[basic_auth_id]" value="<?php  if( isset( $options[ 'basic_auth_id' ] ) ) echo $options[ 'basic_auth_id' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['basic_auth_id']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['basic_auth_id']['description'] ); ?></span>
			</p>
			<p>
				<label for="basic_auth_pw">
					<strong><?php echo esc_html( $this->fields['basic_auth_pw']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[basic_auth_pw]" value="<?php  if( isset( $options[ 'basic_auth_pw' ] ) ) echo $options[ 'basic_auth_pw' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['basic_auth_pw']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['basic_auth_pw']['description'] ); ?></span>
			</p>
			<p>
				<label for="post_title">
					<strong><?php echo esc_html( $this->fields['post_title']['label'] ); ?>:</strong>
                    <input type="text" name="<?php echo $this->option_name; ?>[post_title]" value="<?php  if( isset( $options[ 'post_title' ] ) ) echo $options[ 'post_title' ]; ?>" placeholder="<?php echo esc_attr( $this->fields['post_title']['placeholder'] ); ?>" />
				</label>
                <br />
				<span class="description"><?php echo esc_html( $this->fields['post_title']['description'] ); ?></span>
			</p>
			<p>
				<label for="url_list">
					<strong><?php echo esc_html( $this->fields['url_list']['label'] ); ?>:</strong><br />
					<span class="description"><?php echo esc_html( $this->fields['url_list']['description'] ); ?></span>
				</label>
			</p>
			<p>
                <textarea rows="25" cols="100" name="<?php echo $this->option_name; ?>[url_list]" placeholder="<?php echo esc_attr( $this->fields['url_list']['placeholder'] ); ?>"><?php if( isset( $options[ 'url_list' ] ) ) echo $options[ 'url_list' ]; ?></textarea>
			</p>
	<?php
			submit_button( "Save Screenshot Settings" );
		?>
		</form>
	</div>
	<?php
    }

    function start_new_test($params){
		$this->current_test = $this->call_api($this->baseUrl, 'POST', $params);
	}

    function call_api($api_url, $method = 'GET', $params = false){
        $options = get_option( $this->option_name );
		$apiResult = NULL;

	    $process = curl_init();

	    switch ($method){
	        case "POST":
	            curl_setopt($process, CURLOPT_POST, 1);

	            if ($params){
	                curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
	                curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
	            }
	            break;
	        case "PUT":
	            curl_setopt($process, CURLOPT_CUSTOMREQUEST, "PUT");
	            if ($params){
	                curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
	                curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
	            }
	            break;
	         case 'DELETE':
	         	curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
	         	break;
	        default:
	            if ($params){
	                $api_url = sprintf("%s?%s", $api_url, http_build_query($params));
	            }
	    }

	    // Optional Authentication:
	    curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    curl_setopt($process, CURLOPT_USERPWD, $options[ 'username' ] . ":" . $options[ 'auth_key' ] );

	    curl_setopt($process, CURLOPT_URL, $api_url);
	    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($process, CURLOPT_TIMEOUT, 30);

	    $apiResult->content = curl_exec($process);
		$apiResult->httpResponse = curl_getinfo($process);
		$apiResult->errorMessage =  curl_error($process);
		$apiResult->params = $params;

		curl_close($process);

		//print_r($apiResult);

		$paramsString = $params ? http_build_query($params) : '';
		$response = json_decode($apiResult->content);

		if ($apiResult->httpResponse['http_code'] != 200){
			$message = 'Error calling "' . $apiResult->httpResponse['url'] . '" ';
			$message .= (isset($paramsString) ? 'with params "'.$paramsString.'" ' : ' ');
			$message .= '. Returned HTTP status ' . $apiResult->httpResponse['http_code'] . ' ';
			$message .= (isset($apiResult->errorMessage) ? $apiResult->errorMessage : ' ');
			$message .= (isset($response->message) ? $response->message : ' ');
			die($message);
		}
		else {
			$response = json_decode($apiResult->content);
			if (isset($response->status)){
				die('Error calling "' . $apiResult->httpResponse['url'] . '"' .(isset($paramsString) ? 'with params "'.$paramsString.'"' : '') . '". ' . $response->message);
			}
		}

	    return $response;
    }

    function get_test_id(){
		return $this->current_test->screenshot_test_id;
	}

    function update_test_info(){
		$url = $this->baseUrl . "/" . $this->get_test_id();
		return $this->call_api($url, 'GET');
    }

    function is_test_complete(){
		$this->current_test = $this->update_test_info();
		return !$this->current_test->versions[0]->active;
	}

    function generate_screenshots(){

		$options = get_option( $this->option_name );

        //set browsers
        /*
        $params["browsers"] = array();
        $params["browsers"][] = "Win7x64-C2|IE10|1400x1050";
        $params["browsers"][] = "Mac10.9|Chrome36";
        $params["browsers"][] = "GalaxyNote3-And44|MblChrome36";
        */

        //other options
        $params["browser_list_name"] = $options[ 'browser_list' ];
        if( isset( $options[ 'basic_auth_id' ] ) && !empty( $options[ 'basic_auth_id' ] ) ){
            $params[ 'basic_username' ] = $options[ 'basic_auth_id' ]; //for basic auth urls only
            $params[ 'basic_password' ] = $options[ 'basic_auth_pw' ]; //for basic auth urls only
        }
        //$params[ 'delay' ] = 5; //delay for number of seconds to wait after page is loaded to start capturing screenshots

        $output = 'Screenshots for: <br />';

        $url_list = explode( "\n", $options[ 'url_list' ] );

        foreach( $url_list as $url ) {
            $params[ 'url' ] = $url;
            $this->start_new_test($params);
            $output .= '<strong>' .$url . ':</strong> <a href="http://app.crossbrowsertesting.com/screenshots/' . $this->get_test_id() .'">http://app.crossbrowsertesting.com/screenshots/' . $this->get_test_id() .'</a> <br />';
            $tries = 0;
            $maxTries = 200;
            while ($tries < $maxTries){
                if ($this->is_test_complete()){
                    break;
                }
                else{
                    sleep(2);
                    $tries += 1;
                }
            }
        }
        wp_insert_post( array(
            'post_title' => $options[ 'post_title' ],
            'post_content' => $output,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ) );
    }
}

Oomph_CBT::instance();
