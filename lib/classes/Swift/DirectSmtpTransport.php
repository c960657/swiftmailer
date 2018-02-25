<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2018 Christian Schmidt
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Send Messages directly to recipient's incoming SMTP server.
 *
 * @author Christian Schmidt
 */
class Swift_DirectSmtpTransport extends Swift_Transport_DirectEsmtpTransport
{
    public function __construct()
    {
        call_user_func_array(
            [$this, 'Swift_Transport_DirectEsmtpTransport::__construct'],
            Swift_DependencyContainer::getInstance()
                ->createDependenciesFor('transport.directsmtp')
            );
    }
}
