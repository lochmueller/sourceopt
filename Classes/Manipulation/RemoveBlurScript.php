<?php
/**
 * RemoveBlurScript
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Manipulation;

/**
 * TYPO3 adds to each page a small script:
 *                <script language="javascript">
 *                <!--
 *                browserName = navigator.appName;
 *                browserVer = parseInt(navigator.appVersion);
 *                var msie4 = (browserName == "Microsoft Internet Explorer" && browserVer >= 4);
 *                if ((browserName == "Netscape" && browserVer >= 3) || msie4 || browserName=="Konqueror") {version = "n3";} else {version = "n2";}
 *                function blurLink(theObject){
 *                if (msie4){theObject.blur();}
 *                }
 *                // -->
 *                </script>
 * Obviously used for client-side browserdetection - but thats not necessary if your page doesn't use JS
 */
class RemoveBlurScript implements ManipulationInterface
{

    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate($html, array $configuration = [])
    {
        if (strlen($html) < 100000) {
            $pattern = '/<script (type="text\/javascript"|language="javascript")>.+?Konqueror.+function blurLink.+theObject.blur.+?<\/script>/is';
            $html = preg_replace($pattern, '', $html); // in head
        }
        return str_replace(' onfocus="blurLink(this);"', '', $html); // in body
    }
}
