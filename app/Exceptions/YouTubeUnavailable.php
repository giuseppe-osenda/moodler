<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * YouTube cannot be searched right now: no API key, the daily quota is used up, or the API failed.
 */
class YouTubeUnavailable extends RuntimeException {}
