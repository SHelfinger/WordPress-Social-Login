<?php
	session_start();

	// Rewrite Diagnostics ?
	if( isset( $_GET["testing"] ) ){
		require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

		$testing = "";
		$msg = "";
		$testing = $_REQUEST['testing'];

		if ( $testing == "http://www.example.com" ) {
			$msg = "<b style='color:green;'>Test was successful!</b><br/><br/> The rewrite rules on your server appear to be setup correctly for WordPress Social Login to work.";
		}
		else { 
			$msg = sprintf( '<b style="color:red;">Test was successful!</b><br/><br/> Expected "http://www.example.com", received "%s".', $testing );
		}
?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
</head>
<body>
	<div class="wrap">
		<h2>WordPress Social Login Diagnostics</h2>
		<p><?php echo $msg; ?></p>
	</div>  
</body>
</html>
<?php
		die( );
	} // end Rewrite Diagnostics

	// let display a loading message. should be better than a white screen
	if( isset( $_GET["provider"] ) && ! isset( $_GET["redirect_to_provider"] )){
		// selected provider 
		$provider = @ trim( strip_tags( $_GET["provider"] ) ); 
?>
<table width="100%" border="0">
  <tr>
    <td align="center" height="200px" valign="middle"><img src="assets/img/icons/loading.gif" /></td>
  </tr>
  <tr>
    <td align="center"><br /><h3>Loading...</h3><br /></td> 
  </tr>
  <tr>
    <td align="center">Contacting <b><?php echo ucfirst( $provider ) ; ?></b>, please wait...</td> 
  </tr> 
</table>
<script> 
	setTimeout( function(){window.location.href = window.location.href + "&redirect_to_provider=ture"}, 750 );
</script>
<?php
		die();
	} // end display loading 

	// if user select a provider to login with 
	// and redirect_to_provider eq ture
	if( isset( $_GET["provider"] ) && isset( $_GET["redirect_to_provider"] )){
		try{
			// load hybridauth
			require_once( dirname(__FILE__) . "/hybridauth/Hybrid/Auth.php" );

			// load wp-load.php
			require_once( dirname( dirname( dirname( dirname( __FILE__ )))) . '/wp-load.php' );

			// selected provider name 
			$provider = @ trim( strip_tags( $_GET["provider"] ) );

			// build required configuratoin for this provider
			if( ! get_option( 'wsl_settings_' . $provider . '_enabled' ) ){
				throw new Exception( 'Unknown or disabled provider' );
			}

			$config = array();
			$config["base_url"]  = plugins_url() . '/' . basename( dirname( __FILE__ ) ) . '/hybridauth/';
			$config["providers"] = array();
			$config["providers"][$provider] = array();
			$config["providers"][$provider]["enabled"] = true;

			// provider application id ?
			if( get_option( 'wsl_settings_' . $provider . '_app_id' ) ){
				$config["providers"][$provider]["keys"]["id"] = get_option( 'wsl_settings_' . $provider . '_app_id' );
			}

			// provider application key ?
			if( get_option( 'wsl_settings_' . $provider . '_app_key' ) ){
				$config["providers"][$provider]["keys"]["key"] = get_option( 'wsl_settings_' . $provider . '_app_key' );
			}

			// provider application secret ?
			if( get_option( 'wsl_settings_' . $provider . '_app_secret' ) ){
				$config["providers"][$provider]["keys"]["secret"] = get_option( 'wsl_settings_' . $provider . '_app_secret' );
			}

			// create an instance for Hybridauth
			$hybridauth = new Hybrid_Auth( $config );

			// try to authenticate the selected $provider
			$adapter = $hybridauth->authenticate( $provider );
?>
<html>
<head>
<script>
function init() {
	window.opener.wsl_wordpress_social_login({
		'action'   : 'wordpress_social_login',
		'provider' : '<?php echo $provider ?>'
	});

	window.close();
}
</script>
</head>
<body onload="init();">
</body>
</html>
<?php
		}
		catch( Exception $e ){
			$message = "Unspecified error!"; 

			switch( $e->getCode() ){
				case 0 : $message = "Unspecified error."; break;
				case 1 : $message = "Hybriauth configuration error."; break;
				case 2 : $message = "Provider not properly configured."; break;
				case 3 : $message = "Unknown or disabled provider."; break;
				case 4 : $message = "Missing provider application credentials."; break;
				case 5 : $message = "Authentification failed. The user has canceled the authentication or the provider refused the connection."; break; 
			}  
?>
<style> 
HR {
	width:100%;
	border: 0;
	border-bottom: 1px solid #ccc; 
	padding: 50px;
}
</style>
<table width="100%" border="0">
  <tr>
    <td align="center"><br /><br /><img src="assets/img/icons/alert.png" /></td>
  </tr>
  <tr>
    <td align="center"><br /><br /><h3>Something bad happen!</h3><br /></td> 
  </tr>
  <tr>
    <td align="center">&nbsp;<?php echo $message ; ?><pre style="text-align:left;"><?php print_r( $config ); print_r( $e ); ?></td> 
  </tr>
</table> 
<?php 
			// diplay error and RIP
			die();
		}
    }
?>