<?php

namespace malja\ApiCore\Auth;

/**
 * Exception thrown when authentication method fails.
 */
class AuthFailedException extends \Exception
{

    /**
     * Pointer to authenticator.
     */
    protected $auth;

    /**
     * @param object $auth Pointer to authenticator, which failed.
     */
    public function __construct($auth)
    {
        parent::__construct("Authentication failed");
        $this->auth = $auth;
    }

    /**
     * Get authenticator class, which failed.
     * @return object Authenticator instance.
     */
    public function getAuth()
    {
        return $this->auth;
    }
}
