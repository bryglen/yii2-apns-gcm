<?php
/**
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @link http://bryantan.info
 */

namespace bryglen\apnsgcm;

use Yii;
use yii\log\Logger;

class ApnsLog implements \ApnsPHP_Log_Interface
{
    /**
     * Logs a message.
     * @param  $sMessage @type string The message.
     */
    public function log($sMessage)
    {
        Yii::getLogger()->log($sMessage, Logger::LEVEL_INFO, 'bryglen/apns');
    }
} 