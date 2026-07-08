<?php
	
	return [
	
    /*
		|--------------------------------------------------------------------------
		| Third Party Services
		|--------------------------------------------------------------------------
		|
		| This file is for storing the credentials for third party services such
		| as Mailgun, Postmark, AWS and more. This file provides the de facto
		| location for this type of information, allowing packages to have
		| a conventional file to locate the various service credentials.
		|
	*/
	
    'postmark' => [
	'key' => env('POSTMARK_API_KEY'),
    ],
	
    'resend' => [
	'key' => env('RESEND_API_KEY'),
    ],
	
    'ses' => [
	'key' => env('AWS_ACCESS_KEY_ID'),
	'secret' => env('AWS_SECRET_ACCESS_KEY'),
	'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
	
    'slack' => [
	'notifications' => [
	'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
	'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
	],
    ],
	
	'eptw' => [
    'base_url' => env('EPTW_API_BASE_URL'),
    'token' => env('EPTW_API_TOKEN'),
	
    'permit_list_path' => env('EPTW_API_PERMIT_LIST_PATH', '/api/v1/permits'),
    'permit_detail_path' => env('EPTW_API_PERMIT_DETAIL_PATH', '/api/v1/permits/{id}'),
	
    'timeout' => (int) env('EPTW_API_TIMEOUT', 30),
    'retry' => (int) env('EPTW_API_RETRY', 2),
	
    /*
		* Used for scheduled/background sync audit logs.
		* Usually your first ADMIN user ID.
	*/
    'system_user_id' => (int) env('EPTW_SYSTEM_USER_ID', 1),
	],
	
	];
