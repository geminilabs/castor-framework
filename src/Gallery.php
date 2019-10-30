<?php

namespace GeminiLabs\Castor;

use GeminiLabs\Castor\Helpers\PostMeta;
use GeminiLabs\Castor\Helpers\Theme;
use GeminiLabs\Castor\Helpers\Utility;
use WP_Post;
use WP_Query;

class Gallery
{
    public $gallery;

    protected $args;
    protected $image;
    protected $postmeta;
    protected $theme;
    protected $utility;

    public function __construct(Image $image, PostMeta $postmeta, Theme $theme, Utility $utility)
    {
        $this->image = $image;
        $this->postmeta = $postmeta;
        $this->theme = $theme;
        $this->utility = $utility;
    }

    /**
     * @return static
     */
    public function get(array $args = [])
    {
        $this->normalizeArgs($args);

        $this->gallery = new WP_Query([
            'orderby' => 'post__in',
            'paged' => $this->getPaged(),
            'post__in' => $this->args['media'],
            'post_mime_type' => 'image',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => $this->args['images_per_page'],
        ]);
        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (empty($this->args['media'])) {
            return;
        }
        $images = array_reduce($this->gallery->posts, function ($images, $attachment) {
            return $images.$this->renderImage($attachment);
        });
        return sprintf('<div class="gallery-images" itemscope itemtype="http://schema.org/ImageGallery">%s</div>%s',
            $images,
            $this->renderPagination()
        );
    }

    /**
     * @return string|null
     */
    public function renderImage(WP_Post $attachment)
    {
        $image = $this->image->get($attachment->ID)->image;

        if (!$image) {
            return;
        }
        return sprintf(
            '<figure class="gallery-image" data-w="%s" data-h="%s" data-ps=\'%s\' itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">'.
                '%s%s'.
            '</figure>',
            $image->thumbnail['width'],
            $image->thumbnail['height'],
            $this->getPhotoswipeData($image),
            $this->renderImageTag($image),
            $this->renderImageCaption($image)
        );
    }

    /**
     * @return string|null
     */
    public function renderPagination()
    {
        if (!$this->args['pagination']) {
            return;
        }
        return paginate_links([
            'before_page_number' => '<span class="screen-reader-text">'.__('Page', 'castor').' </span>',
            'current' => $this->gallery->query['paged'],
            'mid_size' => 1,
            'next_text' => __('Next', 'castor'),
            'prev_text' => __('Previous', 'castor'),
            'total' => $this->gallery->max_num_pages,
        ]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    protected function getBoolValue($key, $value = null)
    {
        $bool = $this->getValue($key, $value);

        if (is_null(filter_var($bool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
            $bool = $this->postmeta->get($bool);
        }
        return wp_validate_boolean($bool);
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    protected function getGalleryArg($value = null)
    {
        $gallery = $this->getValue('gallery', $value);

        if (!is_numeric($gallery) && is_string($gallery)) {
            $gallery = intval($this->postmeta->get($gallery));
        }
        return !is_null(get_post($gallery))
            ? intval($gallery)
            : 0;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    protected function getImagesPerPageArg($value = null)
    {
        $perPage = $this->getValue('images_per_page', $value);

        if (!is_numeric($perPage) && is_string($perPage)) {
            $perPage = $this->postmeta->get($perPage);
        }
        return (bool) intval($perPage)
            ? $perPage
            : -1;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function getLazyloadArg($value = null)
    {
        return $this->getBoolValue('lazyload', $value);
    }

    /**
     * @param mixed $value
     *
     * @return array
     */
    protected function getMediaArg($value = null)
    {
        $media = $this->getValue('media', $value);

        if (is_string($media)) {
            $media = $this->postmeta->get($media, [
                'ID' => $this->getGalleryArg(),
                'single' => false,
            ]);
        }
        return is_array($media)
            ? wp_parse_id_list($media)
            : [];
    }

    /**
     * @return int
     */
    protected function getPaged()
    {
        return intval(get_query_var((is_front_page() ? 'page' : 'paged'))) ?: 1;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function getPaginationArg($value = null)
    {
        return $this->getBoolValue('pagination', $value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function getPermalinksArg($value = null)
    {
        return $this->getBoolValue('permalinks', $value);
    }

    /**
     * @return string
     */
    protected function getPhotoswipeData($image)
    {
        return sprintf('{"l":{"src":"%s","w":%d,"h":%d},"m":{"src":"%s","w":%d,"h":%d}}',
            $image->large['url'],
            $image->large['width'],
            $image->large['height'],
            $image->medium['url'],
            $image->medium['width'],
            $image->medium['height']
        );
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function getValue($key, $value = null)
    {
        if (is_null($value) && isset($this->args[$key])) {
            $value = $this->args[$key];
        }
        return $value;
    }

    /**
     * @return array
     */
    protected function normalizeArgs(array $args = [])
    {
        $defaults = [
            'gallery',         // (string) meta_key | (int) post_id
            'lazyload',        // (string) meta_key | (bool)
            'media',           // (string) meta_key | (array) post_ids
            'pagination',      // (string) meta_key | (bool)
            'images_per_page', // (string) meta_key | (int) number
            'permalinks',      // (string) meta_key | (bool)
        ];

        $this->args = shortcode_atts(array_combine($defaults, $defaults), $args);

        array_walk($this->args, function (&$value, $key) {
            $method = $this->utility->buildMethodName($key.'_arg');
            if (method_exists($this, $method)) {
                $value = call_user_func([$this, $method], $value);
            }
        });

        return $this->args;
    }

    /**
     * @param object $image
     * @return string|null
     */
    protected function renderImageCaption($image)
    {
        if (!empty($image->copyright)) {
            $image->caption .= sprintf(' <span itemprop="copyrightHolder">%s</span>', $image->copyright);
        }
        if (empty($image->caption)) {
            return;
        }
        return sprintf('<figcaption itemprop="caption description">%s</figcaption>', $image->caption);
    }

    /**
     * @param object $image
     * @return string|null
     */
    protected function renderImageTag($image)
    {
        $imgSrc = $this->getLazyloadArg()
            ? $this->theme->imageUri('blank.gif')
            : $image->thumbnail['url'];

        $imgTag = sprintf('<img src="%s" data-src="%s" itemprop="thumbnail" alt="%s"/>',
            $imgSrc,
            $image->thumbnail['url'],
            $image->alt
        );

        return $this->getPermalinksArg()
            ? sprintf('<a href="%s" itemprop="contentUrl">%s</a>', $image->permalink, $imgTag)
            : $imgTag;
    }
}
