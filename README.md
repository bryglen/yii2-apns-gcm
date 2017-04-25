Yii 2 Apns Gcm
==============
Yii 2 use Apns and Gcm together with common methods like `send()` and `sendMulti()`

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bryglen/yii2-apns-gcm "1.0.5"
```

or add

```
"bryglen/yii2-apns-gcm": "1.0.5"
```

to the require section of your `composer.json` file.

----------

in your main.php your configuration would look like this

```php
'components' => [
	'apns' => [
		'class' => 'bryglen\apnsgcm\Apns',
		'environment' => \bryglen\apnsgcm\Apns::ENVIRONMENT_SANDBOX,
		'pemFile' => dirname(__FILE__).'/apnssert/apns-dev.pem',
		// 'retryTimes' => 3,
		'options' => [
			'sendRetryTimes' => 5
		]
	],
	'gcm' => [
		'class' => 'bryglen\apnsgcm\Gcm',
		'apiKey' => 'your_api_key',
	],
	// using both gcm and apns, make sure you have 'gcm' and 'apns' in your component
	'apnsGcm' => [
		'class' => 'bryglen\apnsgcm\ApnsGcm',
		// custom name for the component, by default we will use 'gcm' and 'apns'
		//'gcm' => 'gcm',
		//'apns' => 'apns',
	]
]
```

Online Tester
-------------
Please visit the link for online tester [http://apns-gcm.bryantan.info](http://apns-gcm.bryantan.info)

Usage
-----

**Usage using APNS only**

```php
/* @var $apnsGcm \bryglen\apnsgcm\Apns */
$apns = Yii::$app->apns;
$apns->send($push_tokens, $message,
  [
    'customProperty_1' => 'Hello',
    'customProperty_2' => 'World'
  ],
  [
    'sound' => 'default',
    'badge' => 1
  ]
);
```

**Usage using GCM only**

```php
/* @var $apnsGcm \bryglen\apnsgcm\Gcm */
$gcm = Yii::$app->gcm;
$gcm->send($push_tokens, $message,
  [
    'customerProperty' => 1,
  ],
  [
    'timeToLive' => 3
  ],
);
```

### Usage using APNS and GCM Together

**Send using Google Cloud Messaging**

```php
/* @var $apnsGcm \bryglen\apnsgcm\ApnsGcm */
$apnsGcm = Yii::$app->apnsGcm;
$apnsGcm->send(\bryglen\apnsgcm\ApnsGcm::TYPE_GCM, $push_tokens, $message,
  [
    'customerProperty' => 1
  ],
  [
    'timeToLive' => 3
  ],
)
```

**Send using Apple push notification service**

```php
/* @var $apnsGcm \bryglen\apnsgcm\ApnsGcm */
$apnsGcm = Yii::$app->apnsGcm;
$apnsGcm->send(\bryglen\apnsgcm\ApnsGcm::TYPE_APNS, $push_tokens, $message,
  [
    'customerProperty' => 1
  ],
  [
    'sound' => 'default',
  	'badge' => 1
  ],
)
```
