<?php

namespace GeminiLabs\Castor;

use GeminiLabs\Castor\Helpers\PostMeta;
use GeminiLabs\Castor\Helpers\Theme;
use GeminiLabs\Castor\Helpers\Utility;

class Video
{
    public $video;

    protected $args;
    protected $image;
    protected $oembed;
    protected $postmeta;
    protected $supported = ['youtube'];
    protected $theme;
    protected $utility;

    public function __construct(Image $image, Oembed $oembed, PostMeta $postmeta, Theme $theme, Utility $utility)
    {
        $this->image = $image;
        $this->oembed = $oembed;
        $this->postmeta = $postmeta;
        $this->theme = $theme;
        $this->utility = $utility;
    }

    public function get($args = [])
    {
        $args = $this->normalize($args);
        $embed = $this->oembed->request($args['url'], $args['player']);
        if (isset($embed->type) && 'video' == $embed->type) {
            $this->video = $embed;
        }
        return $this;
    }

    public function render()
    {
        if (!isset($this->video->html)) {
            return;
        }
        return sprintf(
            '<div class="video embed">%s%s</div>',
            $this->renderScreenshot(),
            $this->video->html
        );
    }

    public function renderPlayButton()
    {
        return sprintf(
            '<div class="video-play">'.
                '<div class="video-play-pulse pulse1"></div>'.
                '<div class="video-play-pulse pulse2"></div>'.
                '<div class="video-play-pulse pulse3"></div>'.
                '<a href="%s" class="video-play-btn">%s</a>'.
            '</div>',
            $this->args['url'],
            $this->theme->svg('play.svg')
        );
    }

    public function renderScreenshot()
    {
        if ($this->args['image']
            && in_array(strtolower($this->video->provider_name), $this->supported)) {
            return sprintf('%s<div class="video-poster" style="background-image: url(%s)">%s</div>',
                $this->renderSpinner(),
                $this->args['image'],
                $this->renderPlayButton()
            );
        }
    }

    public function renderSpinner()
    {
        return sprintf(
            '<div class="video-spinner">'.
                '<div class="spinner"><div class="spinner-dots">%s</div></div>'.
            '</div>',
            implode('', array_fill(0, 8, '<div class="spinner-dot"></div>'))
        );
    }

    protected function setImage($image)
    {
        $image = $this->image->get($image)->image;
        $this->args['image'] = isset($image->large)
            ? $image->large['url']
            : null;
    }

    protected function setUrl($url)
    {
        $this->args['url'] = !filter_var($url, FILTER_VALIDATE_URL)
            ? $this->postmeta->get($url)
            : $url;
    }

    protected function normalize($args)
    {
        if (is_string($args)) {
            $args = ['url' => $args];
        }

        $this->args = shortcode_atts([
            'image' => '', // string || int
            'player' => '', // string || array
            'url' => '', // string
        ], $args);

        foreach ($this->args as $key => $value) {
            $method = $this->utility->buildMethodName($key, 'set');
            if (!method_exists($this, $method)) {
                continue;
            }
            call_user_func([$this, $method], $value);
        }
        return $this->args;
    }
}
