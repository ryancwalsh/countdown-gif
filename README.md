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
        "astrotomic/countdown-gif": "^1.1",
        }
```

Install the PHP extension, and update your Composer dependencies (see [hint](https://stackoverflow.com/a/13500676/470749)):

```
sudo apt-get update && sudo apt-get install -y imagemagick php-imagick && sudo service php7.2-fpm restart && sudo service nginx restart
composer update
```


## Example

```php
    /**
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCountdownGif(Request $request) {
        //Get all the inputs:
        $tz = $request->get('tz', 'America/New_York');
        $t = $request->get('t', 'tomorrow');
        $r = $request->get('r');
        $defaultText = $request->get('d', 'Expired');
        $format = $request->get('f', '{h}:{m}:{s}'); //{d}:{h}:{m}:{s}        
        $bgColor = $request->get('bg', 'white'); //must not be transparent because each frame overlaps each other
        $fontFamily = $request->get('f', 'Digital_7_mono');
        $fontColor = $request->get('c', 'red');
        $fontSize = $request->get('fs', 40);

        $timezone = timezone_open((string) $tz);
        $nowCarbon = Carbon::now($timezone);
        $endTimeCarbon = is_numeric($t) ? Carbon::createFromTimestamp($t) : Carbon::parse($t, $timezone); //https://carbon.nesbot.com/docs/#api-instantiation
        //Log::debug('$endTime = ' . $endTimeCarbon);
        $diffInSeconds = $endTimeCarbon->diffInSeconds($nowCarbon);
        //Log::debug('$diffInSeconds = ' . $diffInSeconds);
        $minFrames = 1;
        $maxFrames = 30; //originally was 300
        $desiredFrames = $r ?? $diffInSeconds;
        $runtime = max($minFrames, min($maxFrames, $desiredFrames)); //runtime is number of frames?
        //Log::debug('$runtime = ' . $runtime);        
        $fontSizeInt = intval($fontSize);
        $formatter = new \Astrotomic\CountdownGif\Helper\Formatter($format);
        $font = new \Astrotomic\CountdownGif\Helper\Font($fontFamily, $fontSizeInt, $fontColor, [
            'Digital_7_mono' => resource_path('assets/fonts/Digital_7_mono.ttf')
        ]);
        $countDownGifGenerator = new \Astrotomic\CountdownGif\CountdownGif($nowCarbon, $endTimeCarbon, $runtime, $formatter, $bgColor, $font, $defaultText);
        $gif = $countDownGifGenerator->generate();

        $headers = [
            'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified' => '' . gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Cache-Control' => 'post-check=0, pre-check=0', false,
            'Pragma' => 'no-cache',
            'Content-Type' => 'image/gif'
        ]; //https://laravel.com/docs/5.7/responses#attaching-headers-to-responses        
        return response()->make($gif->getImagesBlob(), 200, $headers);
    }
```

## Contact Me

Open an issue to tell me that you're using this repo. Say hi!  And I'm open to any suggestions.
