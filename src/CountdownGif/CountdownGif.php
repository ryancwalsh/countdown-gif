<?php

namespace Astrotomic\CountdownGif;

use Astrotomic\CountdownGif\Helper\Font;
use Astrotomic\CountdownGif\Helper\Formatter;
use Cache; //https://laravel.com/docs/5.7/cache
use DateTime;
use Imagick;
use ImagickDraw;
use Log;

class CountdownGif {

    /**
     * @var DateTime
     */
    protected $now;

    /**
     * @var DateTime
     */
    protected $target;

    /**
     * @var int
     */
    protected $runtime;

    /**
     * @var string
     */
    protected $default;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var Imagick
     */
    protected $background;

    /**
     * @var Font
     */
    protected $font;

    /**
     * CountdownGif constructor.
     * @param DateTime $now
     * @param DateTime $target
     * @param int $runtime
     * @param Formatter $formatter
     * @param Imagick $background
     * @param Font $font
     * @param string $default
     */
    public function __construct(DateTime $now, DateTime $target, $runtime, Formatter $formatter, Imagick $background, Font $font, $default = null) {
        $this->now = $now;
        $this->target = $target;
        $this->runtime = $runtime;
        $this->default = $default;
        $this->formatter = $formatter;
        $this->background = $background;
        $this->font = $font;
    }

    /**
     * @param int $posX
     * @param int $posY
     * @return Imagick
     */
    public function generate($posX, $posY) {
        $gif = new Imagick();
        $gif->setFormat('gif');
        $draw = $this->font->getImagickDraw();
        for ($i = 0; $i <= $this->getRuntime(); $i++) {
            $frame = $this->generateFrame($draw, $posX, $posY, $this->getDiff() - $i);
            $delay = ($i == $this->getRuntime()) ? 90000 : 100; //pauses for a long time on the final frame (e.g. to show a message such as "Expired")
            $frame->setImageDelay($delay);
            $gif->addImage($frame);
        }
        return $gif;
    }

    /**
     * @param ImagickDraw $draw
     * @param int $posX
     * @param int $posY
     * @param int $seconds
     * @return Imagick
     */
    protected function generateFrame($draw, $posX, $posY, $seconds) {
        $secondsPositive = max(0, $seconds);
        $key = $this->getKey($secondsPositive);
        if (Cache::has($key)) {
            //Log::debug('found ' . $key);
            $frame = new Imagick();
            $frame->readImageBlob(Cache::get($key));
            return $frame;
        }
        $text = $this->default;
        if (empty($text) || $secondsPositive > 0) {
            $text = $this->formatter->getFormatted($secondsPositive);
        }
        $frame = clone $this->background;
        $dimensions = $frame->queryFontMetrics($draw, $text);
        $posYAdjusted = $posY + $dimensions['textHeight'] * 0.65 / 2;
        $frame->annotateImage($draw, $posX, $posYAdjusted, 0, $text);
        $this->cacheFrame($frame, $secondsPositive);
        return $frame;
    }

    /**
     * @return int
     */
    protected function getDiff() {
        return $this->target->getTimestamp() - $this->now->getTimestamp();
    }

    /**
     * @return int
     */
    protected function getRuntime() {
        return min($this->runtime, max(0, $this->getDiff()));
    }

    /**
     * 
     * @param int $seconds
     * @return string
     */
    protected function getKey($seconds) {
        $colorBg = (clone $this->background);
        $colorBg->resizeImage(1, 1, Imagick::FILTER_UNDEFINED, 1);
        $array = [
//            'target' => [
//                'timestamp' => $this->target->getTimestamp(),
//                'timezone' => $this->target->getTimezone()->getName(),
//            ],
            'default' => $this->default,
            'formatter' => [
                'format' => $this->formatter->getFormat(),
                'pads' => $this->formatter->getPads(),
            ],
            'background' => [
                'width' => $this->background->getImageWidth(),
                'height' => $this->background->getImageHeight(),
                'color' => $colorBg->getImagePixelColor(1, 1)->getColorAsString(),
            ],
            'font' => [
                'family' => $this->font->getFamily(),
                'size' => $this->font->getSize(),
                'color' => $this->font->getColor(),
            ],
        ];
        $json = json_encode($array);
        $hash = hash('sha256', $json);

        return $hash . '_' . $seconds;
    }

    /**
     * @param Imagick $frame
     * @param int $seconds
     * @return bool
     */
    protected function cacheFrame(Imagick $frame, $seconds) {
        $key = $this->getKey($seconds);
        $expires = clone $this->now;
        $expires->modify('+ ' . ($seconds + 1) . ' seconds');
        //Log::debug('save to cache ' . $key);
        return Cache::put($key, $frame->getImageBlob(), $expires);
    }

}
