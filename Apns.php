<?php
/**
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @link http://bryantan.info
 */

namespace bryglen\apnsgcm;

use Yii;
use yii\base\Application;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Class Apns
 *
 * @method Apns add(\ApnsPHP_Message $message)
 * @method Apns getQueue($bEmpty = true)
 * @method Apns getErrors($bEmpty = true)
 * @method Apns getSendRetryTimes()
 * @method Apns setSendRetryTimes($nRetryTimes)
 * @method Apns disconnect()
 * @method Apns connect()
 * @method Apns getSocketSelectTimeout()
 * @method Apns setSocketSelectTimeout($nSelectTimeout)
 *
 * @package bryglen\apnsgcm
 */
class Apns extends AbstractApnsGcm
{
    const ENVIRONMENT_SANDBOX = 'sandbox';
    const ENVIRONMENT_PRODUCTION = 'production';

    private $_client = null;

    public $environment;

    public $pemFile;

    /**
     * additional information for the push provider
     * @var array
     */
    public $options = [];

    public $logger = 'bryglen\apnsgcm\ApnsLog';

    public function init()
    {
        if (!in_array($this->environment, [self::ENVIRONMENT_SANDBOX, self::ENVIRONMENT_PRODUCTION])) {
            throw new InvalidConfigException('Environment is invalid.');
        }
        if (!$this->pemFile || !file_exists($this->pemFile)) {
            throw new InvalidConfigException('Invalid Pem file');
        }

        Yii::$app->on(
            Application::EVENT_AFTER_REQUEST,
            function ($event) {
                if ($this->getClient()) {
                    $this->getClient()->disconnect();
                }
            }
        );
    }

    public function closeConnection()
    {

    }

    /**
     * @return \ApnsPHP_Push|null
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new \ApnsPHP_Push(
                $this->environment == self::ENVIRONMENT_PRODUCTION ? \ApnsPHP_Push::ENVIRONMENT_PRODUCTION : \ApnsPHP_Push::ENVIRONMENT_SANDBOX,
                $this->pemFile
            );

            $this->options['logger'] = new $this->logger;
            if ($this->retryTimes) {
                $this->options['sendRetryTimes'] = $this->retryTimes;
            }
            foreach ($this->options as $key => $value) {
                $method = 'set' . ucfirst($key);
                $value = is_array($value) ? $value : [$value];

                call_user_func_array([$this->_client, $method], $value);
            }
            $this->_client->connect();
        }
        return $this->_client;
    }

    /**
     * send a push notification for ios using APNS client
     *
     * Usage 1:
     * <code>
     * $this->send(
     *  'some-valid-token',
     *  'some-message',
     *  [
     *    'custom_data_key_1'=>'custom_data_value_1',
     *    'custom_data_key_2'=>'custom_data_value_2',
     *  ],
     *  [
     *    'badge'=>2,
     *    'expiry'=>30
     *    'sound'=>'default',
     *  ]
     * );
     * </code>
     * @param string $token
     * @param string $text a message in sending push notification
     * @param array $payloadData The payload contains information about how the system should alert the user as well as any custom data you provide
     * @param array $args optional additional information in sending a message
     * @return ApnsPHP_Message|null
     * @tutorial https://github.com/duccio/ApnsPHP
     */
    public function send($token, $text, $payloadData = [], $args = [])
    {
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($token, $text, $payloadData, $args);
            $this->success = true;
            return null;
        }

        if (is_array($text)) {
            $message = new \ApnsPHP_Message_Custom($token);
            if (isset($text['title'])) {
                $message->setTitle($text['title']);
            }
            if (isset($text['body'])) {
                $message->setText($text['body']);
            }
        } else {
            $message = new \ApnsPHP_Message($token);
            $message->setText($text);
        }
        foreach ($args as $method => $value) {
            if (strpos($method, 'set') === false) {
                $method = 'set' . ucfirst($method);
            }
            $value = is_array($value) ? $value : [$value];
            call_user_func_array([$message, $method], $value);
        }
        // set a custom payload data
        foreach ($payloadData as $key => $value) {
            $message->setCustomProperty($key, $value);
        }
        // Add the message to the message queue
        $this->add($message);
        // send a message

        $this->getClient()->send();

        $this->errors = $this->getClient()->getErrors();
        $this->success = empty($this->errors) ? true : false;

        return $message;
    }

    /**
     * @param array|string $tokens
     * @param $text
     * @param array $payloadData
     * @param array $args
     * @return \ApnsPHP_Message|null
     */
    public function sendMulti($tokens, $text, $payloadData = [], $args = [])
    {
        $tokens = is_array($tokens) ? $tokens : [$tokens];
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($tokens, $text, $payloadData, $args = []);
            return null;
        }

        if (is_array($text)) {
            $message = new \ApnsPHP_Message_Custom();
            if (isset($text['title'])) {
                $message->setTitle($text['title']);
            }
            if (isset($text['body'])) {
                $message->setText($text['body']);
            }
        } else {
            $message = new \ApnsPHP_Message();
            $message->setText($text);
        }
        foreach ($tokens as $token) {
            $message->addRecipient($token);
        }
        foreach ($args as $method => $value) {
            if (strpos($message, 'set') === false) {
                $method = 'set' . ucfirst($method);
            }
            $value = is_array($value) ? $value : [$value];
            call_user_func_array([$message, $method], $value);
        }
        // set a custom payload data
        foreach ($payloadData as $key => $value) {
            $message->setCustomProperty($key, $value);
        }
        // Add the message to the message queue
        $this->add($message);
        // send a message

        $this->getClient()->send();

        $this->errors = $this->getClient()->getErrors();
        $this->success = empty($this->errors) ? true : false;

        return $message;
    }

    public function __call($method, $params)
    {
        $client = $this->getClient();
        if (method_exists($client, $method)) {
            return call_user_func_array([$client, $method], $params);
        }

        return parent::__call($method, $params);
    }
}
