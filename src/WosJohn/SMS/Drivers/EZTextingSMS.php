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
use GuzzleHttp\Client;

class EZTextingSMS extends AbstractSMS implements DriverInterface
{

    /**
     * The Guzzle HTTP Client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The API's URL.
     *
     * @var string
     */
    protected $apiBase = 'https://app.eztexting.com';

    /**
     * The ending of the URL that all requests must have.
     *
     * @var array
     */
    protected $apiEnding = ['format' => 'json'];

    /**
     * Constructs a new instance.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Sends a SMS message.
     *
     * @param OutgoingMessage $message
     * @return void
     */
    public function send(OutgoingMessage $message)
    {
        $composedMessage = $message->composeMessage();

        $data = [
            'PhoneNumbers' => $message->getTo(),
            'Message' => $composedMessage
        ];

        $this->buildCall('/sending/messages');
        $this->buildBody($data);

        $this->postRequest();
    }

    /**
     * Checks the server for messages and returns their results.
     *
     * @param array $options
     * @return array
     */
    public function checkMessages(Array $options = array())
    {
        $this->buildCall('/incoming-messages');
        $this->buildBody($options);

        $rawMessages = $this->getRequest()->json();
        return $this->makeMessages($rawMessages['Response']['Entries']);
    }

    /**
     * Gets a single message by it's ID.
     *
     * @param $messageId
     * @return IncomingMessage
     */
    public function getMessage($messageId)
    {
        $this->buildCall('/incoming-messages');
        $this->buildCall('/' . $messageId);

        $rawMessage = $this->getRequest()->json();

        return $this->makeMessage($rawMessage['Response']['Entry']);
    }

    /**
     * Returns an IncomingMessage object with it's properties filled out.
     *
     * @param $rawMessage
     * @return mixed|\WosJohn\SMS\IncomingMessage
     */
    protected function processReceive($rawMessage)
    {
        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($rawMessage);
        $incomingMessage->setFrom($rawMessage['PhoneNumber']);
        $incomingMessage->setMessage($rawMessage['Message']);
        $incomingMessage->setId($rawMessage['ID']);
        $incomingMessage->setTo('313131');

        return $incomingMessage;
    }

    /**
     * Receives an incoming message via REST call.
     *
     * @param $raw
     * @return \WosJohn\SMS\IncomingMessage
     */
    public function receive($raw)
    {
        //Due to the way EZTexting handles Keyword Submits vs Replys
        //We must check both values.
        $from = $raw->get('PhoneNumber') ? $raw->get('PhoneNumber') : $raw->get('from');
        $message = $raw->get('Message') ? $raw->get('Message') : $raw->get('message');

        $incomingMessage = $this->createIncomingMessage();
        $incomingMessage->setRaw($raw->get());
        $incomingMessage->setFrom($from);
        $incomingMessage->setMessage($message);
        $incomingMessage->setTo('313131');

        return $incomingMessage;
    }
}
