<?php
	namespace ClipStack\Bootstrap;

	use ClipStack\Backbone\Version;
	use ClipStack\Backbone\Config;
	
	require 'backbone/config.php';
	require 'backbone/version.php';

	/**
	 * VALIDATE THAT THE SERVER'S PHP VERSION MEETS ClipStack'S REQUIREMENTS.
	 */
	$php_version = phpversion();

	if (version_compare($php_version, '8.2.0', '<')) {
		throw new \RuntimeException("Your PHP version ($php_version) is below the supported version. Please upgrade to at least 8.2.0!");
	}

	if (version_compare($php_version, '8.3.0', '>=')) {
		throw new \RuntimeException("Your PHP version ($php_version) exceeds the maximum supported version. The system supports up to PHP 8.2.X!");
	}

	/**
	 * SETTING SECURITY HEADERS:
	 *
	 * SECURITY HEADERS PLAY A VITAL ROLE IN SAFEGUARDING WEB APPLICATIONS BY INSTRUCTING BROWSERS ON HOW TO
	 * HANDLE CONTENT. PROPER CONFIGURATION CAN MITIGATE VARIOUS TYPES OF ATTACKS AND SECURITY VULNERABILITIES.
	 * BY SETTING THESE HEADERS, WE ENHANCE THE APPLICATION'S DEFENSE MECHANISMS AGAINST THREATS LIKE 
	 * CROSS-SITE SCRIPTING (XSS), CLICKJACKING, MIME TYPE SNIFFING, AND MORE. REGULARLY REVIEWING AND UPDATING
	 * THESE HEADERS IN ALIGNMENT WITH EVOLVING SECURITY PRACTICES ENSURES THE APPLICATION REMAINS RESILIENT
	 * AGAINST EMERGING THREATS.
	 */
	//header("Content-Security-Policy: default-src 'self';");
	//header("X-Frame-Options: SAMEORIGIN");
	//header("X-XSS-Protection: 1; mode=block");
	//header("X-Content-Type-Options: nosniff");
	//header("Referrer-Policy: no-referrer-when-downgrade");
	//header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

	/**
	 * ENSURE THAT THE WEB SERVER CONFIGURATIONS ARE AS EXPECTED.
	 */
	if (function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
		throw new \RuntimeException('mod_rewrite module is not enabled in Apache. Please enable mod_rewrite to continue.');
	}

	/**
	 * VERIFY IF THE REQUIRED PHP EXTENSIONS FOR ClipStack ARE LOADED.
	 */
	$required_extensions = ['pdo', 'mbstring', 'json', 'curl'];

	foreach ($required_extensions as $extension) {
		if (!extension_loaded($extension)) {
			throw new \RuntimeException("The {$extension} extension is not installed or enabled. This extension is required for ClipStack to function properly.");
		}
	}

	/**
	 * ENSURE ClipStack RUNS OVER A SECURE HTTPS CONNECTION.
	 */
	if ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') && $_SERVER['SERVER_PORT'] !== '443') {
		throw new \RuntimeException('ClipStack requires a secure HTTPS connection. Please ensure SSL/TLS is correctly configured.');
	}

	/**
	 * LOAD AND VALIDATE THE CONFIGURATION FILE FOR ClipStack.
	 */
	$config_array = require 'config.php';
	$config_instance = Config::getInstance($config_array);

	if (!is_array($config_array)) {
		throw new \RuntimeException('Configuration file did not return a valid array.');
	}

	/**
	 * VALIDATE THE ENVIRONMENT SETTING WITHIN THE ClipStack CONFIGURATION.
	 */
	$environment = $config_instance -> get('app.env');

	if (!in_array($environment, ['development', 'production'])) {
		$env_string = var_export($environment, true);
		throw new \RuntimeException("The specified environment setting '{$env_string}' is not recognized. Valid environments are 'development' or 'production'.");
	}

	/**
	 * NOTIFY THE USER IF THERE IS A NEWER VERSION OF ClipStack AVAILABLE.
	 */
	if ($environment == 'production') {
		//$new_version = Version::isNewVersionAvailable();

		//if ($new_version !== false) {
			//echo "There's a new version ($new_version) available for ClipStack! Consider updating for the latest features and security fixes.";
		//}
	}

	// REGISTER THE AUTOLOADER.
	spl_autoload_register(function ($class) {
		// PROJECT NAMESPACE PREFIX.
		$prefix = 'ClipStack\\Component\\';

		// BASE DIRECTORY FOR THE NAMESPACE PREFIX.
		$base_dir = 'components/';

		// CHECK IF THE CLASS USES THE NAMESPACE PREFIX.
		$len = strlen($prefix);
		if (strncmp($prefix, $class, $len) !== 0) {
			// MOVE TO THE NEXT REGISTERED AUTOLOADER.
			return;
		}

		// GET THE RELATIVE CLASS NAME.
		$relative_class = substr($class, $len);

		// REPLACE THE NAMESPACE PREFIX WITH THE BASE DIRECTORY, REPLACE NAMESPACE SEPARATORS WITH DIRECTORY SEPARATORS.
		$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

		// IF THE FILE EXISTS, THEN REQUIRE IT.
		if (file_exists($file)) {
			require $file;
		}
	});
