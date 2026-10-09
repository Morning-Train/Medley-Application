<?php

namespace MorningMedley\Application\Exceptions;

use ErrorException;

/**
 * A PHP deprecation (E_DEPRECATED, E_USER_DEPRECATED) that is reported but not thrown.
 *
 * It exists so reporting tools such as Bugsnag show it as a deprecation
 * instead of an ErrorException.
 */
class PhpDeprecation extends ErrorException {}
