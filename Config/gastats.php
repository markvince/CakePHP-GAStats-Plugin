<?php
/**
 * Config for Gastats
 *
 * @link https://github.com/markvince/CakePHP-GAStats-Plugin
 * @link https://github.com/wanze/Google-Analytics-API-PHP
 *
 * Copy this config file to your application and fill in the details.
 *   Follow setup steps for API Service Account on:
 *     https://github.com/wanze/Google-Analytics-API-PHP
 *
 * Create a Project in the Google APIs Console:
 *   https://cloud.google.com/console/
 *     Create a Project
 *     Enable the Analytics API under Services
 *     OAuth2 > Create a new Client
 *       choose "Service Account"
 *     Download the private key (.p12 file)
 *   https://code.google.com/apis/console/ (old)
 *     Enable the Analytics API under Services
 *     Under API Access: Create an Oauth 2.0 Client-ID
 *     Give a Product-Name, choose "Service Account"
 *     Download the private key (.p12 file)
 *
 * cp ~/Downloads/*.p12 app/Config/gastats_api_privatekey.p12
 *
 * fill in the config details
 * fill in the default account
 */
$config = array(
	'Gastats' => array(
		// ------------------------
		'auth' => array(
			// GA OAuth2 config
			// ------------------------
			// From the APIs console
			'clientId' => '0000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com',
			'email' => '000000000000-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx@developer.gserviceaccount.com',
			// Path to the .p12 file
			//   download from the API and move to this path in your
			//   application (not this plugin)
			'privateKey' => APP . 'Config/gastats_api_privatekey.p12',
		),
		// ------------------------
		'defaults' => array(
			// REQUIRED default account, even to list profiles/accounts
			'accountId' => 'ga:00000000', // << -------
			// can enter any query parameters here
		),
		// ------------------------
		// other config for gastats
		'ids' => 10224332, //default GA id to be used, others can be specified with custom stat_types
		'stat_types' => array( //(optional) custom stat_types can be made here.  default types exisit in gastats_raw.php
			'webstats' => array(
				'ids' => 0,
			),
		),
		'channels' => 'Corp/slug/is_active:1',
		// (optional if not using webchannels) model/field where webchannel urls are found in main apps database
		// optional third value is an active/enabled field with value to check
	)
);
