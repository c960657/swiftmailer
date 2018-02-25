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
class Swift_Transport_DirectEsmtpTransport implements Swift_Transport
{
    private $eventDispatcher;

    private $transport;

    public function __construct(Swift_Transport_EsmtpTransport $transport, Swift_AddressEncoder_IdnAddressEncoder $addressEncoder)
    {
        $this->transport = $transport;
        $this->addressEncoder = $addressEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->transport->stop();
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $to = (array) $message->getTo();
        $cc = (array) $message->getCc();
        $bcc = (array) $message->getBcc();
        $tos = array_merge($to, $cc, $bcc);

        $domains = $this->getDomains($tos);
        if (count($domains) > 1) {
            throw new Swift_TransportException(
                __CLASS__.' does not support sending to more than one domain at a time'
                );
        }

        $domain = reset($domains);
        $hosts = $this->getMxHosts($domain);

        foreach ($hosts as $host) {
            try {
                $transport = $this->getEsmtpTransport($host);
                if (!$transport->isStarted()) {
                    $transport->start();
                }
                return $transport->send($message, $failedRecipients);
            } catch (Swift_TransportException $e) {
            }
        }

        throw new Swift_TransportException('All MX hosts for '.$domain.' failed');
    }

    /**
     * Register a plugin.
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->transport->registerPlugin($plugin);
    }


    protected function getDomains(array $addresses): array
    {
        $domains = [];
        foreach ($addresses as $address => $name) {
            $address = $this->addressEncoder->encodeString($address);
            $i = strrpos($address, '@');
            $domains[] = substr($address, $i + 1);
        }
        return array_unique($domains);
    }

    /**
     * @return array Array of MX hostnames, sorted by priority.
     */
    protected function getMxHosts(string $domain): array
    {
        if ($this->getmxrr($domain, $hosts, $weights)) {
            $hostWeights = array_combine($hosts, $weights);
        } else {
            // RFC 5321, section 5.1:
            // If an empty list of MXs is returned, the address is treated as if
            // it was associated with an implicit MX RR, with a preference of 0,
            // pointing to that host.
            $hostWeights = [$domain => 0];
        }

        // RFC 5321, section 5.1:
        // If there are multiple destinations with the same preference and there
        // is no clear reason to favor one (e.g., by recognition of an easily
        // reached address), then the sender-SMTP MUST randomize them to spread
        // the load across multiple mail exchangers for a specific organization.
        $hostWeights = array_map(function ($priority) {
            return $priority + rand(0, 255) / 256;
        }, $hostWeights);

        asort($hostWeights, SORT_NUMERIC);

        return array_keys($hostWeights);
    }

    protected function getmxrr(string $domain, &$hosts, &$weights): bool
    {
        return getmxrr($domain, $hosts, $weights);
    }

    protected function getEsmtpTransport(string $host): Swift_Transport_EsmtpTransport
    {
        if ($this->transport->isStarted()) {
            try {
                $this->transport->stop();
            } catch (Exception $e) {
            }
        }

        $this->transport->setHost($host);

        return $this->transport;
    }
}
