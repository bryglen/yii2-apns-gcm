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
    public $options = array();

    public $logger = 'bryglen\apnsgcm\ApnsLog';

    public function init()
    {
        if (!in_array($this->environment, array(self::ENVIRONMENT_SANDBOX, self::ENVIRONMENT_PRODUCTION))) {
            throw new InvalidConfigException('Environment is invalid.');
        }
        if (!$this->pemFile || !file_exists($this->pemFile)) {
            throw new InvalidConfigException('Push SSL certificate is required.');
        }

        Yii::$app->on(Application::EVENT_AFTER_REQUEST, function($event) {
            if ($this->getClient()) {
                $this->getClient()->disconnect();
            }
        });
    }

    public function closeConnection()
    {

    }

    /**
     * @return ApnsPHP_Push|null
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new ApnsPHP_Push(
                $this->environment == self::ENVIRONMENT_PRODUCTION ? ApnsPHP_Push::ENVIRONMENT_PRODUCTION : ApnsPHP_Push::ENVIRONMENT_SANDBOX,
                $this->pemFile
            );

            $this->options['logger'] = new $this->logger;
            if ($this->retryTimes) {
                $this->options['sendRetryTimes'] = $this->retryTimes;
            }
            foreach ($this->options as $key => $value) {
                $method = 'set' . ucfirst($key);
                $value = is_array($value) ? $value : array($value);

                call_user_func_array(array($this->_client, $method), $value);
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
     * $this->send('some-valid-token','some-message',
     * array(
     *   'custom_data_key_1'=>'custom_data_value_1',
     *   'custom_data_key_2'=>'custom_data_value_2',
     * ),
     * array(
     *   'badge'=>2,
     *   'expiry'=>30
     *   'sound'=>'default',
     * )
     * );
     * </code>
     * @param string $token
     * @param string $text a message in sending push notification
     * @param array $payloadData The payload contains information about how the system should alert the user as well as any custom data you provide
     * @param array $args optional additional information in sending a message
     * @return ApnsPHP_Message|null
     * @tutorial https://github.com/duccio/ApnsPHP
     */
    public function send($token, $text, $payloadData = array(), $args = array())
    {
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($token, $text, $payloadData, $args = array());
            $this->success = true;
            return null;
        }

        $message = new ApnsPHP_Message($token);
        $message->setText($text);
        foreach($args as $method => $value) {
            if (strpos($message, 'set') === false) {
                $method = 'set' . ucfirst($method);
            }
            $value = is_array($value) ? $value : array($value);
            call_user_func_array(array($message, $method), $value);
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
        $this->success = $this->getClient()->getErrors() ? true : false;

        return $message;
    }

    /**
     * @param array|string $tokens
     * @param $text
     * @param array $payloadData
     * @param array $args
     * @return ApnsPHP_Message|null
     */
    public function sendMulti($tokens, $text, $payloadData = array(), $args = array())
    {
        $tokens = is_array($tokens) ? $tokens : array($tokens);
        // check if its dry run or not
        if ($this->dryRun === true) {
            $this->log($tokens, $text, $payloadData, $args = array());
            return null;
        }

        $message = new ApnsPHP_Message();
        foreach ($tokens as $token) {
            $message->addRecipient($token);
        }
        $message->setText($text);
        foreach($args as $method => $value) {
            if (strpos($message, 'set') === false) {
                $method = 'set' . ucfirst($method);
            }
            $value = is_array($value) ? $value : array($value);
            call_user_func_array(array($message, $method), $value);
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
        $this->success = $this->getClient()->getErrors() ? true : false;

        return $message;
    }

    public function __call($method, $params)
    {
        $client = $this->getClient();
        if (method_exists($client, $method))
            return call_user_func_array(array($client, $method), $params);

        return parent::__call($method, $params);
    }
}