<?php
/**
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @link http://bryantan.info
 */

namespace bryglen\apnsgcm;

use Yii;
use yii\base\Component;
use yii\log\Logger;

abstract class AbstractApnsGcm extends Component
{
    public $retryTimes = 3;
    public $dryRun = false;
    public $enableLogging = false;
    public $errors = [];
    public $success = false;

    public function log($tokens, $text, $payloadData = [], $args = [])
    {
        $payloadData = http_build_query($payloadData);
        $args = http_build_query($args);
        $tokens = is_array($tokens) ? implode(', ', $tokens) : $tokens;
        $msg = "Sending push notifications to " . $tokens . "\n" .
            "message: {$text}\n" .
            "payload data: " . str_replace('&', ', ', $payloadData) . "\n" .
            "arguments: " . str_replace('&', ', ', $args);
        Yii::getLogger()->log($msg, Logger::LEVEL_INFO, 'bryglen/apnsgcm');
    }

    abstract public function send($token, $text, $payloadData = [], $args = []);

    abstract public function sendMulti($tokens, $text, $payloadData = [], $args = []);
} 