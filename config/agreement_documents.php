<?php
	
	return [
    /*
		* OCR is intentionally disabled by default.
		* Enabling this only allows files to enter PENDING status.
		* A future approved processor still needs to process pending records.
	*/
    'ocr' => [
	'enabled' => env('AGREEMENT_OCR_ENABLED', false),
	'engine' => env('AGREEMENT_OCR_ENGINE'),
	'language' => env('AGREEMENT_OCR_LANGUAGE', 'eng'),
    ],
];