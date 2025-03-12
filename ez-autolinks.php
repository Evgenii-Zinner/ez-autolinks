<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class EzAutolinksPlugin extends Plugin
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
        
        $page           = $event['page'];
        $html           = $page->getRawContent();
        $debug          = $this->config->get('plugins.ez-autolinks.debug', false);
        $currentPageUrl = $page->url();
        
        // Decode current page URL for Unicode matching.
        $decodedCurrentUrl = urldecode($currentPageUrl);
        if ($debug) {
            $this->grav['log']->info("EzAutolinks: Decoded currentPageUrl: {$decodedCurrentUrl}");
        }
        
        // Build configuration mapping: multiple words => URL.
        // Skip mapping if the (decoded and trimmed) configured URL is found in the decoded current page URL.
        $linksList = $this->config->get('plugins.ez-autolinks.links', []);
        $links = [];
        if (is_array($linksList)) {
            foreach ($linksList as $item) {
                if (is_array($item) && isset($item['words'], $item['url'])) {
                    // Decode mapping URL.
                    $decodedMappingUrl = urldecode($item['url']);
                    // Remove any leading "../" or "./" segments.
                    $trimMappingUrl = preg_replace('#^(?:\.\./)+#', '', $decodedMappingUrl);
                    
                    if ($debug) {
                        $this->grav['log']->info(
                            "EzAutolinks: currentPageUrl: {$decodedCurrentUrl}, mapping URL: {$decodedMappingUrl}, trimmed: {$trimMappingUrl}"
                        );
                    }
                    
                    // If the trimmed mapping URL is found in the current page URL, skip mapping.
                    if (mb_stripos($decodedCurrentUrl, $trimMappingUrl, 0, 'UTF-8') !== false) {
                        if ($debug) {
                            $this->grav['log']->info("EzAutolinks: Skipping mapping for URL {$item['url']} because '{$trimMappingUrl}' is part of the current page URL.");
                        }
                        continue;
                    }
                    
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
        if (empty($links)) {
            if ($debug) {
                $this->grav['log']->info('EzAutolinks: No valid links found, skipping processing.');
            }
            return;
        }
        
        // Sort words by descending length to avoid overlapping matches.
        $wordsList = array_keys($links);
        usort($wordsList, function($a, $b) {
            return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
        });
        
        // Build regex pattern.
        $patternParts = [];
        foreach ($wordsList as $word) {
            if (preg_match('/\s/', $word)) {
                $patternParts[] = '(' . preg_quote($word, '/') . ')';
            } else {
                $patternParts[] = '\b' . preg_quote($word, '/') . '\b';
            }
        }
        $pattern = '/(' . implode('|', $patternParts) . ')/u';
        
        // Load HTML into DOMDocument with proper UTF-8 handling.
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        } catch (\Exception $e) {
            if ($debug) {
                $this->grav['log']->error('EzAutolinks: Failed to load HTML: ' . $e->getMessage());
            }
            return;
        }
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        $totalReplacements   = 0;
        $replacementsSummary = []; // To track replaced words and counts.
        
        foreach ($xpath->query('//text()[normalize-space()]') as $textNode) {
            // Skip text nodes inside an anchor tag.
            if (strtolower($textNode->parentNode->nodeName) === 'a') {
                continue;
            }
            $originalText = $textNode->nodeValue;
            if (!preg_match($pattern, $originalText)) {
                continue;
            }
            
            $newText = preg_replace_callback($pattern, function($matches) use ($links, &$replacementsSummary, $debug) {
                $matchedWord = $matches[0];
                if (isset($links[$matchedWord])) {
                    if ($debug) {
                        if (!isset($replacementsSummary[$matchedWord])) {
                            $replacementsSummary[$matchedWord] = 0;
                        }
                        $replacementsSummary[$matchedWord]++;
                    }
                    return '<a href="' . $links[$matchedWord] . '">' . $matchedWord . '</a>';
                }
                return $matchedWord;
            }, $originalText);
            
            if ($newText !== $originalText) {
                $fragment = $dom->createDocumentFragment();
                // Suppress potential errors on malformed fragments.
                $fragment->appendXML($newText);
                $textNode->parentNode->replaceChild($fragment, $textNode);
                $totalReplacements++;
            }
        }
        
        // Extract inner HTML of the <body> and update the page.
        $body = $dom->getElementsByTagName('body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }
        $page->setRawContent($newHtml);
        
        // Log a summary if debug is enabled.
        if ($debug) {
            $summary = "EzAutolinks: Total replacements: $totalReplacements.";
            if (!empty($replacementsSummary)) {
                $parts = [];
                foreach ($replacementsSummary as $word => $count) {
                    $parts[] = "'$word': $count";
                }
                $summary .= " Words replaced: " . implode(', ', $parts) . ".";
            }
            $this->grav['log']->info($summary);
        }
    }
}
