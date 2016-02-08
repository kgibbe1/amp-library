<?php

namespace Lullabot\AMP\Pass;

use QueryPath\DOMQuery;

use Lullabot\AMP\Warning;
use Lullabot\AMP\WarningType;
use Lullabot\AMP\ActionTaken;

/**
 * Class FixImgTagsPass
 * @package Lullabot\AMP\Pass
 *
 * Transform all <img> tags to <amp-img> which don't have noscript as an ancestor
 * - height and width are obtained by trying to look at the image file itself via getimagesize()
 * - Currently the layout is set to responsive
 */
class FixImgTagsPass extends FixBasePass
{
    function pass()
    {
        // @todo deal with animated gifs
        $all_a = $this->q->find(':not(noscript) img');
        /** @var \DOMElement $dom_el */
        foreach ($all_a->get() as $dom_el) {
            $lineno = $dom_el->getLineNo();

            $new_el = $this->renameDomElement($dom_el, 'amp-img');
            $this->setAmpImgAttributes($new_el);

            $this->addWarning(new Warning('img', WarningType::IMG_CONVERTED_AMP_IMG, ActionTaken::TAG_RENAMED, $lineno));
        }

        return $this->warnings;
    }

    // @todo deal with failure
    protected function getImageWidthHeight($src)
    {
        // Try obtaining image size
        $size = getimagesize($src);

        if (empty($size)) {
            $src = $this->options['base_uri'] . $src;
            $size = getimagesize($src);
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }

    // @todo should this call out to externally registered callbacks?
    protected function setAmpImgAttributes(\DOMElement $el)
    {
        // If height or image is not set, get it from the image
        if (!$el->getAttribute('width') || !$el->getAttribute('height')) {
            $dimensions = $this->getImageWidthHeight($el->getAttribute('src'));
            $el->setAttribute('width', $dimensions['width']);
            $el->setAttribute('height', $dimensions['height']);
        }

        // Sane default for now
        if (!$el->getAttribute('layout')) {
            $el->setAttribute('layout', 'responsive');
        }
    }
}
