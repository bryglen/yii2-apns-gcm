<?php
/**
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @link http://bryantan.info
 */

namespace bryglen\apnsgcm;

use yii\base\Component;

class ApnsGcm extends Component
{
    const TYPE_GCM = 'gcm';
    const TYPE_APNS = 'apns';

    /**
     * component name for apns
     *
     * @var string
     */
    public $apns = 'apns';

    /**
     * component name for gcm
     *
     * @var string
     */
    public $gcm = 'gcm';

    public $errors = [];
    public $success = false;

    private $_gcmClient;
    private $_apnsClient;

    /**
     * @return Gcm
     */
    public function getGcmClient()
    {
        if ($this->_gcmClient === null) {
            $component = $this->gcm;
            $client = \Yii::$app->get($component);

            $this->_gcmClient = $client;
        }

        return $this->_gcmClient;
    }

    /**
     * @return Apns
     */
    public function getApnsClient()
    {
        if ($this->_apnsClient === null) {
            $component = $this->apns;
            $client = \Yii::$app->get($component);

            $this->_apnsClient = $client;
        }

        return $this->_apnsClient;
    }

    /**
     * send a push notification depending on type
     * @param $type
     * @param $token
     * @param $text
     * @param array $payloadData
     * @param array $args
     * @return null|\PHP_GCM\Message
     */
    public function send($type, $token, $text, $payloadData = [], $args = [])
    {
        $client = null;
        $result = null;
        $this->errors = [];
        if ($type == self::TYPE_GCM) {
            $client = $this->getGcmClient();
            $result = $client->send($token, $text, $payloadData, $args);
            $this->success = $client->success;
        } elseif ($type == self::TYPE_APNS) {
            $client = $this->getApnsClient();
            $result = $client->send($token, $text, $payloadData, $args);

            $this->success = $client->success;
        }
        if (!$this->success) {
            $this->errors = $client->errors;
        }

        return $result;
    }

    public function sendMulti($type, $tokens, $text, $payloadData = [], $args = [])
    {
        $client = null;
        $result = null;
        $this->errors = [];
        if ($type == self::TYPE_GCM) {
            $client = $this->getGcmClient();
            $result = $client->sendMulti($tokens, $text, $payloadData, $args);
            $this->success = $client->success;
        } elseif ($type == self::TYPE_APNS) {
            $client = $this->getApnsClient();
            $result = $client->sendMulti($tokens, $text, $payloadData, $args);

            $this->success = $client->success;
        }
        if (!$this->success) {
            $this->errors = $client->errors;
        }

        return $result;
    }
}
