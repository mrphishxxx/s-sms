<?php namespace WosJohn\SMS\Drivers;

/**
 * Simple-SMS
 * Simple-SMS is a package made for Laravel to send/receive (polling/pushing) text messages.
 *
 * @link http://www.simplesoftware.io
 * @author SimpleSoftware support@simplesoftware.io
 *
 */

use WosJohn\SMS\OutgoingMessage;
use CCP;

class CCPSMS extends AbstractSMS implements DriverInterface {

    /**
     * The CCP SDK
     *
     * @var CCP
     */
    protected $ccp;

    /**
     * Holds the CCP auth token.
     *
     * @var string
     */
    protected $authToken;

    /**
     * Holds the request URL to verify a CCP push.
     *
     * @var string
     */
    protected $url;

    /**
     * Determines if requests should be checked to be authentic.
     *
     * @var boolean
     */
    protected $verify;

    /**
     * Constructs the CCP object.
     *
     * @param CCP $CCP
     * @param $authToken
     * @param $url
     * @param bool $verify
     */
    public function __construct(CCP $ccp, $authToken, $url, $verify = false)
    {
        $this->ccp = $ccp;
        $this->authToken = $authToken;
        $this->url = $url;
        $this->verify = $verify;
    }

    /**
     * Sends a SMS message.
     *
     * @param OutgoingMessage $message The OutgoingMessage instance.
     */
    public function send(OutgoingMessage $message)
    {
        $from = $message->getFrom();
        $composeMessage = $message->composeMessage();

        foreach ($message->getTo() as $to) {
            $this->message = $this->ccp->account(config('sms.ccp.account_sid'),config('sms.ccp.auth_token'))->appid(config('sms.ccp.app_id'))->create($to,$composeMessage,$tempId);
        }
    }

    /**
     * Processing the raw information from a request and inputs it into the IncomingMessage object.
     *
     * @param $raw
     * @return void
     */
    protected function processReceive($raw)
    {
        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($raw);
        $incomingMessage->setMessage($raw->body);
        $incomingMessage->setFrom($raw->from);
        $incomingMessage->setId($raw->sid);
        $incomingMessage->setTo($raw->to);
    }

    /**
     * Returns an array full of IncomingMessage objects.
     *
     * @param array $options The options of filters to pass onto CCP.  Options are To, From, and DateSent
     * @return array
     */
    public function checkMessages(Array $options = array())
    {


        $rawMessages = $this->message;

        foreach ($rawMessages as $rawMessage)
        {
            $incomingMessage = $this->createIncomingMessage();
            $this->processReceive($incomingMessage, $rawMessage);
            $incomingMessages[] = $incomingMessage;
        }

        return $incomingMessages;
    }

    /**
     * Gets a message by messageId.
     *
     * @param $messageId The requested messageId.
     * @return IncomingMessage
     */
    public function getMessage($messageId)
    {
        $rawMessage = $this->message;
        $incomingMessage = $this->createIncomingMessage();
        $this->processReceive($incomingMessage, $rawMessage);
        return $incomingMessage;
    }

    /**
     * Push receives.  This method will take a request and convert it into an IncomingMessage object.
     *
     * @param $raw The raw data.
     * @return IncomingMessage
     */
    public function receive($raw)
    {
        if ($this->verify) $this->validateRequest();

        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($raw->get());
        $incomingMessage->setMessage($raw->get('Body'));
        $incomingMessage->setFrom($raw->get('From'));
        $incomingMessage->setId($raw->get('MessageSid'));
        $incomingMessage->setTo($raw->get('To'));

        return $incomingMessage;
    }

}
