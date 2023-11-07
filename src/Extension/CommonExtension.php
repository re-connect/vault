<?php

namespace App\Extension;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommonExtension extends AbstractExtension
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getClass', [$this, 'getClass']),
            new TwigFunction('isOfClass', [$this, 'isOfClass']),
            new TwigFunction('max', [$this, 'max']),
            new TwigFunction('min', [$this, 'min']),
            new TwigFunction('is_string', [$this, 'is_string']),
            new TwigFunction('fileExists', [$this, 'fileExists']),
            new TwigFunction('isAdmin', [$this, 'isAdmin']),
            new TwigFunction('getYoutubeCodeFromLink', [$this, 'getYoutubeCodeFromLink']),
            new TwigFunction('canDoThingsOnEntity', [$this, 'canDoThingsOnEntity']),
            new TwigFunction('getMailtoLink', [$this, 'getMailtoLink']),
            new TwigFunction('baseEncodeFile', [$this, 'baseEncodeFile']),
            new TwigFunction('dateTimePickerJavascript', [$this, 'dateTimePickerJavascript'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('videoEmbed', [$this, 'videoEmbed'], ['is_safe' => ['html']]),
            new TwigFilter('videoIframe', [$this, 'videoIframe'], ['is_safe' => ['html']]),
            new TwigFilter('ucwords', [$this, 'ucwords']),
            new TwigFilter('reDate', [$this, 'reDate']),
            new TwigFilter('timeAgo', [$this, 'timeAgo']),
            new TwigFilter('timestamp', [$this, 'timestamp']),
            new TwigFilter('truncateHtmlPreserveTags', [$this, 'truncateHtmlPreserveTags'], ['is_safe' => ['html']]),
            new TwigFilter('tidyHtml', [$this, 'tidyHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function isOfClass($object, $classname): bool
    {
        if ($classname == $this->getClass($object)) {
            return true;
        }

        return false;
    }

    public function getClass($string): string
    {
        return ClassUtils::getRealClass(get_class($string));
    }

    public function getName(): string
    {
        return 're.extension.common';
    }

    public function canDoThingsOnEntity($entity, $voterAttribute): bool
    {
        return true === $this->authorizationChecker->isGranted($voterAttribute, $entity);
    }

    public function getMailtoLink($email = null, $subject = null, $body = null): string
    {
        $str = sprintf('mailto:%s', $email);
        if ($subject) {
            $str .= sprintf('?subject=%s', $subject);
            if ($body) {
                $str .= sprintf('&body=%s', $body);
            }
        } elseif ($body) {
            $str .= sprintf('?body=%s', $body);
        }

        return $str;
    }

    public function videoEmbed($link): ?string
    {
        $str = null;
        if (null != $link && preg_match('#youtube#', $link)) {
            $code = preg_replace('#.+([a-zA-Z0-9-_]{11})$#', '$1', $link);

            $str = sprintf('https://www.youtube.com/embed/%s', $code);
        } elseif (null != $link && preg_match('#youtu\.be#', $link)) {
            $code = preg_replace('#.+([a-zA-Z0-9-_]{11})$#', '$1', $link);
            $str = sprintf('https://www.youtube.com/embed/%s', $code);
        } elseif (null != $link && preg_match('#dailymotion#', $link)) {
            $code = preg_replace('#.+video/([a-zA-Z0-9]{7})_.+$#', '$1', $link);
            $str = sprintf('https://www.dailymotion.com/embed/video/%s', $code);
        } elseif (null != $link && preg_match('#vimeo#', $link)) {
            $code = preg_replace('#.*vimeo.com/.*?(\d{8,10}).*$#', '$1', $link);
            $str = sprintf('https://player.vimeo.com/video/%s', $code);
        }

        return $str;
    }

    public function getYoutubeCodeFromLink($link): ?string
    {
        $code = null;
        if (preg_match('#youtube#', $link)) {
            $code = preg_replace('#.+([a-zA-Z0-9-_]{11})$#', '$1', $link);
        }

        return $code;
    }

    public function videoIframe($link): ?string
    {
        $str = null;
        if (null != $link && preg_match('#youtube#', $link)) {
            $code = preg_replace('#.+([a-zA-Z0-9-_]{11})$#', '$1', $link);

            $str = sprintf('<iframe  style="" height="330" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>', $code);
        } elseif (null != $link && preg_match('#youtu\.be#', $link)) {
            $code = preg_replace('#.+([a-zA-Z0-9-_]{11})$#', '$1', $link);
            $str = sprintf('<iframe  style="" height="330" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>', $code);
        } elseif (null != $link && preg_match('#dailymotion#', $link)) {
            $code = preg_replace('#.+video/([a-zA-Z0-9]{7})_.+$#', '$1', $link);

            $str = sprintf('<iframe style="" frameborder="0" height="330" src="https://www.dailymotion.com/embed/video/%s"></iframe>', $code);
        } elseif (null != $link && preg_match('#vimeo#', $link)) {
            $code = preg_replace('#.*vimeo.com/.*?(\d{8,10}).*$#', '$1', $link);

            $str = sprintf('<iframe style="" src="https://player.vimeo.com/video/%s" height="250" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>', $code);
        }

        return $str;
    }

    public function tidyHtml($str): ?string
    {
        return tidy_repair_string($str, [], 'utf8');
    }

    public function isAdmin($user): ?string
    {
        if (null == $user || is_string($user)) {
            return false;
        }

        return $this->authorizationChecker->isGranted('ROLE_ADMIN', $user);
    }

    public function min($nb1, $nb2)
    {
        return min($nb1, $nb2);
    }

    public function max($nb1, $nb2)
    {
        return max($nb1, $nb2);
    }

    public function is_string($element)
    {
        return is_string($element);
    }

    public function fileExists($url)
    {
        return file_exists($url);
    }

    public function ucwords($str)
    {
        return ucwords($str);
    }

    public function reDate($d, $format = '%d %B %Y')
    {
        setlocale(LC_TIME, 'fr_FR.ISO-8859-1', 'fra');

        if (is_string($d)) {
            $d = new \DateTime($d);
        }
        if ($d instanceof \DateTime) {
            $d = $d->getTimestamp();
        }
        setlocale(LC_TIME, 'fr_FR.ISO-8859-1', 'fra');

        return mb_convert_encoding(date($format, $d), 'UTF-8', 'ISO-8859-1');
    }

    public function timeAgo($date)
    {
        if (is_string($date) || !is_object($date)) {
            $date = new \DateTime($date);
        }

        $timeDiff = time() - $date->getTimestamp();
        if ($timeDiff < 60) {
            return sprintf('%s secondes', $timeDiff);
        } elseif ($timeDiff < 60 * 60) {
            return sprintf('%s minutes', round($timeDiff / 60));
        } elseif ($timeDiff < 60 * 60 * 24) {
            return sprintf('%s heures', round($timeDiff / (60 * 60)));
        } else {
            return sprintf('%s jours', round($timeDiff / (60 * 60 * 24)));
        }
    }

    public function timestamp($date)
    {
        if (null == $date) {
            return -1;
        }
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->getTimestamp();
    }

    public function baseEncodeFile($path)
    {
        return base64_encode(file_get_contents($path));
    }

    public function dateTimePickerJavascript()
    {
        return "
		$('.re_datetimepicker').each(function(){

            $(this).datetimepicker({
                dateFormat:         'dd/mm/yy',
                timeFormat: 		'hh:mm',
                changeMonth:        true,
                changeYear:         true,
                showButtonPanel:    true,
                showMillisec:    false,
                showMicrosec:    false,
                showTimezone:    false,
                firstDay:           1,
                buttonImageOnly:    true,
                buttonImage:        $(this).attr('data-datepicker-icon')
            });
        })";
    }

    public function truncateHtmlPreserveTags($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = [];
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if (false !== $pos) {
                            unset($open_tags[$pos]);
                        }
                    // if tag is an opening tag
                    } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                --$left;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            // ...and cut the text in this position
            $truncate = substr($truncate, 0, $spacepos);
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</'.$tag.'>';
            }
        }

        return $truncate;
    }
}
