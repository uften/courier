<?php

declare(strict_types=1);

namespace Uften\Courier\Exceptions;

use RuntimeException;

/**
 * Base exception for all uften/courier errors.
 * Catch this if you want to handle any courier-related failure.
 */
class CourierException extends RuntimeException {}
