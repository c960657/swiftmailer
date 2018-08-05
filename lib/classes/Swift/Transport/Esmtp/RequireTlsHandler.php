<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2018 Christian Schmidt
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An ESMTP handler for REQUIRETLS support.
 *
 * REQUIRETLS is an emerging standard for ensuring end-to-end encryption when
 * a message is delivered through multiple SMTP servers.
 *
 * @author Christian Schmidt
 *
 * @see  https://tools.ietf.org/html/draft-ietf-uta-smtp-require-tls-03
 */
class Swift_Transport_Esmtp_RequireTlsHandler implements Swift_Transport_EsmtpHandler
{
    public function __construct()
    {
    }

    /**
     * Get the name of the ESMTP extension this handles.
     *
     * @return string
     */
    public function getHandledKeyword()
    {
        return 'REQUIRETLS';
    }

    /**
     * Not used.
     */
    public function setKeywordParams(array $parameters)
    {
    }

    /**
     * Not used.
     */
    public function afterEhlo(Swift_Transport_SmtpAgent $agent)
    {
    }

    /**
     * Get params which are appended to MAIL FROM:<>.
     *
     * @return string[]
     */
    public function getMailParams()
    {
        return ['REQUIRETLS'];
    }

    /**
     * Not used.
     */
    public function getRcptParams()
    {
        return [];
    }

    /**
     * Not used.
     */
    public function onCommand(Swift_Transport_SmtpAgent $agent, $command, $codes = array(), &$failedRecipients = null, &$stop = false)
    {
    }

    /**
     * Returns +1, -1 or 0 according to the rules for usort().
     *
     * This method is called to ensure extensions can be execute in an appropriate order.
     *
     * @param string $esmtpKeyword to compare with
     *
     * @return int
     */
    public function getPriorityOver($esmtpKeyword)
    {
        return 0;
    }

    /**
     * Not used.
     */
    public function exposeMixinMethods()
    {
        return [];
    }

    /**
     * Not used.
     */
    public function resetState()
    {
    }
}
