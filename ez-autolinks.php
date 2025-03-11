<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class EZAutolinksPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onPageContentProcessed' => ['onPageContentProcessed', 0]
        ];
    }
    
    public function onPageContentProcessed(Event $event)
    {
        if ($this->isAdmin()) {
            return;
        }
        
        $page = $event['page'];
        $html = $page->getRawContent();

        // Get the list from configuration and convert it to an associative array.
        $linksList = $this->config->get('plugins.autolinks.links', []);
        $links = [];
        if (is_array($linksList)) {
            foreach ($linksList as $item) {
                if (is_array($item) && isset($item['words'], $item['url'])) {
                    // Split the words by comma and trim extra whitespace.
                    $words = array_map('trim', explode(',', $item['words']));
                    foreach ($words as $word) {
                        if (!empty($word)) {
                            $links[$word] = $item['url'];
                        }
                    }
                }
            }
        }
        
        // Load HTML into DOMDocument.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        // Process all text nodes that are not inside an anchor tag.
        foreach ($xpath->query('//text()[normalize-space()]') as $textNode) {
            if ($textNode->parentNode->nodeName === 'a') {
                continue;
            }
            $originalText = $textNode->nodeValue;
            $newText = $originalText;
            
            // Replace each word from the configuration with its anchor tag.
            foreach ($links as $word => $url) {
                $pattern = '/\b' . preg_quote($word, '/') . '\b/';
                $newText = preg_replace_callback($pattern, function ($matches) use ($url) {
                    return '<a href="' . $url . '">' . $matches[0] . '</a>';
                }, $newText);
            }
            
            // Replace the text node if changes occurred.
            if ($newText !== $originalText) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($newText);
                $textNode->parentNode->replaceChild($fragment, $textNode);
            }
        }
        
        // Extract inner HTML of the <body> and update the page.
        $body = $dom->getElementsByTagName('body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }
        $page->setRawContent($newHtml);
    }
}

