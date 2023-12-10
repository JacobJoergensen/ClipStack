<?php
	return [
		'app' => [
			// THE NAME OF YOUR APPLICATION
			'name' => 'MyApp',

			// CURRENT ENVIRONMENT OF THE APP (E.G., 'LOCAL', 'STAGING', 'PRODUCTION')
			'env' => 'production',

			// DETERMINES IF MAINTENANCE MODE IS ON
			'maintenance_mode' => false,
			'maintenance_token' => 'your_secret_token',

			// THE DEFAULT CHARACTER SET FOR YOUR APPLICATION
			'charset' => 'UTF-8',

			// THE ROOT URL FOR YOUR APPLICATION
			'base_url' => 'https://example.com',

			// THE PATH TO THE SOURCE FILES OF YOUR APPLICATION
			'src_path' => __DIR__ . '/src'
		],

		'dateTime' => [
			// DEFAULT TIMEZONE FOR YOUR APPLICATION
			'timezone' => 'UTC',

			// DEFAULT DATE AND TIME FORMAT USED ACROSS THE APP
			'date_format' => 'Y-m-d H:i:s',

			// DEFAULT HOUR FORMAT USED ACROSS THE APP
			'hour_format' => 'H:i:s'
		],

		'database' => [
			// DATABASE MANAGEMENT SYSTEM (DBMS) IN USE
			'driver' => 'mysql',

			// DATABASE SERVER HOST
			'host' => 'localhost',
	
			// PORT FOR THE DATABASE SERVER
			'port' => '3306',

			// DATABASE USERNAME
			'username' => 'root',

			// DATABASE PASSWORD (CONSIDER STORING THIS SECURELY AND REFERENCING SECURELY)
			'password' => 'password',

			// THE NAME OF THE DATABASE YOUR APP CONNECTS TO
			'name' => 'my_database',

			// PREFIX FOR TABLES IN THE DATABASE
			'prefix' => 'cs_',

			// CHARACTER SET FOR THE DATABASE CONNECTION
			'charset' => 'utf8mb4',

			// COLLATION FOR THE DATABASE CONNECTION (IMPACTS TEXT SORTING)
			'collation' => 'utf8mb4_unicode_ci'
		],

		'encryption' => [
			// Encryption key for securing data. Must be a 32-byte string
			'key' => 'your_32_byte_key_here',

			// Cipher algorithm to use for encryption. Default is aes-256-cbc
			'cipher' => 'aes-256-cbc',

			'key_rotation' => [
				// SET TO TRUE TO ENABLE KEY ROTATION
				'enabled' => false,
				'keys' => [
					'primary' => 'your_primary_key_here',
					'secondary' => 'your_secondary_key_here'
				]
			]
		],

		'mail' => [
			// MAIL SENDING MECHANISM OR DRIVER (E.G., 'SMTP', 'SENDMAIL', 'MAILGUN')
			'driver' => 'smtp',

			// HOST ADDRESS OF YOUR MAIL SERVER
			'host' => 'smtp.yourmail.com',

			// PORT FOR THE MAIL SERVER
			'port' => '587',

			// USERNAME FOR SMTP AUTHENTICATION
			'username' => 'your-email@example.com',

			// PASSWORD FOR SMTP AUTHENTICATION (CONSIDER STORING THIS SECURELY)
			'password' => 'your-email-password',

			// ENCRYPTION MECHANISM (E.G., 'TLS', 'SSL')
			'encryption' => 'tls',

			// DEFAULT 'FROM' ADDRESS FOR YOUR EMAILS
			'from' => [
				'address' => 'no-reply@example.com',

				// DEFAULT 'FROM' NAME FOR YOUR EMAILS
				'name' => 'MyApp Support'
			]
		],

		'session' => [
			// NAME FOR THE SESSION COOKIE
			'session_name' => 'clipstack_sess',

			// SESSION COOKIE PREFIX
			'session_prefix' => 'clipstack_',

			// SESSION LIFETIME IN MINUTES
			'lifetime' => '120',

			// PREFIX FOR THE COOKIE
			'cookie_prefix' => 'clipstack_',

			// WHETHER THE COOKIE SHOULD BE SET ONLY OVER A SECURE HTTPS CONNECTION
			'cookie_secure' => false,

			// ENSURES THE COOKIE CAN ONLY BE ACCESSED VIA HTTP(S) NOT BY SCRIPTS
			'cookie_http_only' => true
		]
	];
