<?php
/***************************************************************************
 * for license information see LICENSE.md
 ***************************************************************************/

/**
 * Class RSSParser
 */
class RSSParser
{
    /**
     * parse
     *
     * @param int $items number of feeditems to parse from feed
     * @param string $url url of the feed to parse
     * @param $timeout
     * @param bool $includetext
     * @return string $item feeditems as HTML-string
     */
    public static function parse($items, $url, $timeout, $includetext)
    {
        global $tpl;

        if ($items <= 0) {
            return '';
        }

        // error
        $rss = [];

        // check $url
        if (preg_match(
            '!^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\:\,\?\'\\\+&amp;%\$#\=~_\-]+))*$!',
            $url
        )) {
            $tpl->assign('includetext', $includetext);

            $arrContextOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];

            // get xml-data;
            // set short timeout to avoid that the start page gets blocked
            $save_timeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', $timeout);
            $data = @file_get_contents($url, false, stream_context_create($arrContextOptions));
            ini_set('default_socket_timeout', $save_timeout);

            // check data
            if ($data !== false && strpos($data, 'rss version=') !== false) {
                // parse XML
                try {
                    // get SimpleXML-object
                    $xml = new SimpleXMLElement($data);

                    $i = 0;
                    $headlines = [];
                    // walk through items
                    foreach ($xml->channel->item as $item) {
                        // check length
                        if ($items != 0 && $i >= $items) {
                            break;
                        }
                        // add html
                        if ($includetext) {
                            // fill array
                            $rss[] = [
                                    'pubDate' => date('Y-m-d', strtotime($item->pubDate)),
                                    'title' => $item->title,
                                    'link' => $item->link,
                                    'description' => $item->description,
                                ];
                            $i++;
                        // htmlspecialchars_decode() works around inconsistent HTML encoding
                                // e.g. in SMF Forum Threads
                        } elseif (strpos($item->title, 'VERSCHOBEN') === false &&
                                !in_array(htmlspecialchars_decode($item->title), $headlines)
                            ) { // hack to exclude forum thread-move messages
                            // fill array
                            $rss[] = [
                                    'pubDate' => date('Y-m-d', strtotime($item->pubDate)),
                                    'title' => $item->title,
                                    'link' => $item->link,
                                ];
                            $headlines[] = '' . htmlspecialchars_decode($item->title);
                            $i++;
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }

        // return
        return $rss;
    }
}
