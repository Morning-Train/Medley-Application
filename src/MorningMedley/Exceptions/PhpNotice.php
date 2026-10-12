<?php

namespace MorningMedley\Application\Exceptions;

use ErrorException;

/**
 * A PHP notice (E_NOTICE, E_USER_NOTICE) that is reported but not thrown.
 *
 * It exists so reporting tools such as Bugsnag show it as a notice
 * instead of an ErrorException.
 */
class PhpNotice extends ErrorException {}
