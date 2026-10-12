<?php

namespace MorningMedley\Application\Exceptions;

use ErrorException;

/**
 * A PHP warning (E_WARNING, E_USER_WARNING) that is reported but not thrown.
 *
 * It exists so reporting tools such as Bugsnag show it as a warning
 * instead of an ErrorException.
 */
class PhpWarning extends ErrorException {}
