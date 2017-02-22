<?php
            function get_web_page($url) {
                $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0'; // Firefox
                $useragent = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)'; // IE

                
                $options = array(
                    CURLOPT_RETURNTRANSFER => true,     // return web page
                    CURLOPT_HEADER         => false,    // don't return headers
                    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                    CURLOPT_ENCODING       => "",       // handle compressed
                    CURLOPT_USERAGENT      => $useragent, // who am i
                    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
                    CURLOPT_CONNECTTIMEOUT => 500,      // timeout on connect
                    CURLOPT_TIMEOUT        => 500,      // timeout on response
                    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                    CURLOPT_SSL_VERIFYPEER => 0,        // don't verify ssl
                );

                $ch      = curl_init( $url );
                curl_setopt_array( $ch, $options );
                $content = curl_exec( $ch );
                $err     = curl_errno( $ch );
                $errmsg  = curl_error( $ch );
                $header  = curl_getinfo( $ch );
                curl_close( $ch );

                $header['errno']   = $err;
                $header['errmsg']  = $errmsg;
                $header['content'] = $content;
                return $header;
            }

?>