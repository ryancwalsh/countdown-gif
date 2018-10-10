## Installation

Add the appropriate lines to your project's `composer.json`:

```
"repositories": [
        {
            "type": "git",
            "url": "https://github.com/ryancwalsh/countdown-gif"
        }
    ],
"require": {
        "Astrotomic/countdown-gif": "dev-master",
        }
```

Install the PHP extension, and update your Composer dependencies (see [hint](https://stackoverflow.com/a/13500676/470749)):

```
sudo apt-get update && sudo apt-get install -y imagemagick php-imagick && sudo service php7.2-fpm restart && sudo service nginx restart
composer update
```


## Example

```php
$request = \Illuminate\Http\Request::createFromGlobals();
$timezone = timezone_open((string) $request->get('tz', 'Europe/Berlin'));
$now = new DateTime('now', $timezone);
$target = new DateTime($request->get('t', 'now'), $timezone);
$runtime = max(0, min(300, $request->get('r', 10)));
$default = $request->get('d');
$format = $request->get('f', '{d}:{h}:{m}:{s}');

$width = intval($request->get('w', 500));
$height = intval($request->get('h', 50));
$bgColor = '#'.$request->get('bg', 'ffffff');

$fontType = $request->get('ft');
$fontSize = intval($request->get('fs', 48));
$fontColor = $request->get('fc', '#ff0000');

$formatter = new \Astrotomic\CountdownGif\Helper\Formatter($format);

$background = new Imagick();
$background->setFormat('png');
$background->newImage($width, $height, $bgColor);

$font = new \Astrotomic\CountdownGif\Helper\Font($fontType, $fontSize, $fontColor, [
    'lato' => resource_path('lato-regular.ttf'),
]);

$redis = new \Redis();
$config = app('config')->get('database.redis.default');
$redis->connect($config['host'], $config['port']);
$redisPool = new \Cache\Adapter\Redis\RedisCachePool($redis);

$countDownGif = new \Astrotomic\CountdownGif\CountdownGif($now, $target, $runtime, $formatter, $background, $font, $default, $redisPool, \Cache\Adapter\Common\CacheItem::class);
$gif = $countDownGif->generate($background->getImageWidth() / 2, $background->getImageHeight() / 2);

header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: image/gif');
echo $gif->getImagesBlob();
```
