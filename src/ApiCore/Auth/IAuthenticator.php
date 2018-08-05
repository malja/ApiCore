<?php

namespace core\Auth;

use \core\Request;
use \JsonSerializable;

/**
 * Interface for all authenticators.
 */
interface IAuthenticator extends JsonSerializable
{

    /**
     * Method takes request and checks if it is signed properly.
     * @param \core\Request $request Request data.
     */
    public function authenticate(Request $request);

    /**
     * Get list of all error messages from the last execution of authenticate method.
     * @see authenticate
     * @return array List of error messages, or empty array.
     */
    public function getErrorMessages(): array;
}
