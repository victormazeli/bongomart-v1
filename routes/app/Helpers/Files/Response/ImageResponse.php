<?php
/**
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Helpers\Files\Response;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;

class ImageResponse
{
	/**
	 * Create response for previewing specified image.
	 * Optionally resize image to specified size.
	 *
	 * @param $disk
	 * @param $filePath
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
	 */
	public static function create($disk, $filePath)
	{
		if (!$disk instanceof FilesystemAdapter) {
			abort(Response::HTTP_INTERNAL_SERVER_ERROR);
		}
		
		if (!$disk->exists($filePath)) {
			abort(Response::HTTP_NOT_FOUND);
		}
		
		$mime = $disk->getMimetype($filePath);
		$content = $disk->get($filePath);
		
		return response($content, Response::HTTP_OK, ['Content-Type' => $mime]);
	}
}
