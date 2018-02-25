<?php

class Swift_Transport_DirectEsmtpTransportTest extends \SwiftMailerTestCase
{
    public function testMxHostFailover()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getMxHosts')
            ->once()
            ->with('example.com')
            ->andReturn(['smtp1.example.com', 'smtp2.example.com', 'smtp3.example.com']);

        $smtp->shouldReceive('setHost')
            ->with('smtp1.example.com')
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp2.example.com')
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp3.example.com')
            ->never();

        $smtp->shouldReceive('isStarted')
            ->zeroOrMoreTimes()
            ->andReturn(false);
        $smtp->shouldReceive('start')
            ->twice();
        $smtp->shouldReceive('stop')
            ->never();

        $i = 0;
        $smtp->shouldReceive('send')
            ->with($message, \Mockery::any())
            ->twice()
            ->andReturnUsing(function () use (&$i) {
                if ($i++ == 0) {
                    throw new Swift_TransportException('smtp1 is down');
                } else {
                    return 1;
                }
            });

        $sent = $direct->send($message, $failedRecipients);

        $this->assertSame(1, $sent);
        $this->assertEmpty($failedRecipients);
    }

    public function testAllMxHostsFail()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getMxHosts')
            ->once()
            ->with('example.com')
            ->andReturn(['smtp1.example.com', 'smtp2.example.com', 'smtp3.example.com']);

        $smtp->shouldReceive('setHost')
            ->with('smtp1.example.com')
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp2.example.com')
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp3.example.com')
            ->once();

        $smtp->shouldReceive('isStarted')
            ->zeroOrMoreTimes()
            ->andReturn(false);
        $smtp->shouldReceive('start')
            ->times(3);

        $i = 0;
        $smtp->shouldReceive('send')
            ->with($message, \Mockery::any())
            ->times(3)
            ->andThrow(new Swift_TransportException('MX is down'));

        try {
            $direct->send($message);
            $this->fail('Should throw Swift_TransportException');
        } catch (Swift_TransportException $e) {
        }
    }

    public function testSingleAddressFail()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);
        $message->shouldReceive('getCc')
            ->zeroOrMoreTimes()
            ->andReturn(['bob@example.com' => 'Bob']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getMxHosts')
            ->once()
            ->with('example.com')
            ->andReturn(['smtp1.example.com', 'smtp2.example.com']);

        $smtp->shouldReceive('setHost')
            ->with('smtp1.example.com')
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp2.example.com')
            ->never();

        $smtp->shouldReceive('isStarted')
            ->zeroOrMoreTimes()
            ->andReturn(false);
        $smtp->shouldReceive('start')
            ->once();

        $i = 0;
        $smtp->shouldReceive('send')
            ->with($message, \Mockery::any())
            ->once()
            ->andReturnUsing(function ($message, &$failedRecipients) use (&$i) {
                $failedRecipients[] = 'bob@example.com';
                return 1;
            });

        $sent = $direct->send($message, $failedRecipients);

        $this->assertSame(1, $sent);
        $this->assertSame(['bob@example.com'], $failedRecipients);
    }

    public function testSendingToMultipleDomains()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);
        $message->shouldReceive('getBcc')
            ->zeroOrMoreTimes()
            ->andReturn(['bob@example.org' => 'Bob']);

        $direct = $this->getTransport();

        try {
            $direct->send($message);
            $this->fail('Should reject sending to multiple domains');
        } catch (Swift_TransportException $e) {
        }
    }

    public function testGetMxHosts()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getmxrr')
            ->with('example.com', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturnUsing(function ($domain, &$hosts, &$weights) {
                $hosts = ['smtp2.example.com', 'smtp1.example.com'];
                $weights = [20, 10];
                return true;
            });

        $smtp->shouldReceive('setHost')
            ->with('smtp1.example.com')
            ->ordered()
            ->once();
        $smtp->shouldReceive('setHost')
            ->with('smtp2.example.com')
            ->ordered()
            ->never();

        $direct->send($message);
    }

    public function testGetMxHostsForDomainWithoutMxHosts()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getmxrr')
            ->with('example.com', \Mockery::any(), \Mockery::any())
            ->once()
            ->andReturn(false);

        $smtp->shouldReceive('setHost')
            ->with('example.com')
            ->once();

        $direct->send($message);
    }

    public function testGetMxHostsForUtf8Domain()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@exämple.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getmxrr')
            ->with('xn--exmple-cua.com', \Mockery::any(), \Mockery::any())
            ->once()
             ->andReturnUsing(function ($domain, &$hosts, &$weights) {
                $hosts = ['smtp.example.com'];
                $weights = [10];
                return true;
            });

        $smtp->shouldReceive('setHost')
            ->with('smtp.example.com')
            ->once();

        $direct->send($message);
    }

    public function testEsmtpTransportIsStoppedIfStarted()
    {
        $message = $this->createMessage();
        $message->shouldReceive('getTo')
            ->zeroOrMoreTimes()
            ->andReturn(['alice@example.com' => 'Alice']);

        $smtp = $this->createEsmtpTransport();
        $direct = $this->getTransport($smtp);

        $direct->shouldReceive('getMxHosts')
            ->once()
            ->with('example.com')
            ->andReturn(['smtp.example.com']);

        $smtp->shouldReceive('isStarted')
            ->once()
            ->ordered()
            ->andReturn(true);
        $smtp->shouldReceive('stop')
            ->ordered()
            ->once();

        $smtp->shouldReceive('setHost')
            ->with('smtp.example.com')
            ->ordered()
            ->once();

        $smtp->shouldReceive('isStarted')
            ->once()
            ->ordered()
            ->andReturn(false);
        $smtp->shouldReceive('start')
            ->once();

        $direct->send($message);
    }

    public function testRegisterPluginDelegatesToEsmtpTransport()
    {
        $plugin = $this->getMockery('Swift_Events_EventListener');

        $smtp = $this->createEsmtpTransport();
        $smtp->shouldReceive('registerPlugin')
            ->once()
            ->with($plugin);

        $transport = $this->getTransport($smtp);
        $transport->registerPlugin($plugin);
    }

    protected function getTransport(Swift_Transport_EsmtpTransport $smtp = null)
    {
        $smtp = $smtp ?? $this->createEsmtpTransport();
        $addressEncoder = new Swift_AddressEncoder_IdnAddressEncoder();

        return $this->getMockery(
                'Swift_Transport_DirectEsmtpTransport',
                [$smtp, $addressEncoder]
            )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    protected function createEsmtpTransport(): Swift_Transport_EsmtpTransport
    {
        return $this->getMockery('Swift_Transport_EsmtpTransport')->shouldIgnoreMissing();
    }

    protected function createMessage(): Swift_Mime_SimpleMessage
    {
        return $this->getMockery('Swift_Mime_SimpleMessage')->shouldIgnoreMissing();
    }
}
