<?php

namespace User\Service;

use IntlDateFormatter;
use Laminas\Escaper\Escaper;
use NumberFormatter;
use Pi;
use function _escape;
use function class_exists;
use function method_exists;
use function preg_replace;
use function str_replace;
use function strip_tags;
use function ucfirst;

class UtilityService implements ServiceInterface
{
    public function __construct()
    {

    }

    /**
     * Load date formatter
     *
     * @param string $date
     * @param array  $params
     *
     * @return string
     * @see IntlDateFormatter
     * Valid values: 'NULL', 'FULL', 'LONG', 'MEDIUM', 'SHORT'
     *
     */
    public function date(string $date = '', array $params = []): string
    {
        $date = empty($date) ? time() : $date;

        if (!class_exists('IntlDateFormatter')) {
            return date('Y-m-d H:i:s', $date);
        }

        /* if (!$locale) {
            $locale = $this->getLocale();
        } elseif (strpos($locale, '@')) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        }

        if (null === $calendar) {
            $calendar = Pi::config('date_calendar');
            if (!$calendar) {
                $calendar = IntlDateFormatter::GREGORIAN;
            }
        }
        if ($calendar && !is_numeric($calendar)) {
            $locale   .= '@calendar=' . $calendar;
            $calendar = IntlDateFormatter::TRADITIONAL;
        }
        if (null === $calendar) {
            $calendar = IntlDateFormatter::GREGORIAN;
        }

        $datetype = constant(
            'IntlDateFormatter::'
            . strtoupper($datetype ?: Pi::config('date_datetype'))
        );
        $timetype =  constant(
            'IntlDateFormatter::'
            . strtoupper($timetype ?: Pi::config('date_timetype'))
        );
        $timezone = $timezone ?: Pi::config('timezone'); */

        $local    = $params['local'] ?? 'fa_IR@calendar=persian';
        $datetype = IntlDateFormatter::SHORT;
        $timetype = IntlDateFormatter::NONE;
        $timezone = $params['timezone'] ?? 'Asia/Tehran';
        $calendar = IntlDateFormatter::TRADITIONAL;
        $pattern  = $params['pattern'] ?? 'yyyy/MM/dd HH:mm:ss';

        $formatter = new IntlDateFormatter($local, $datetype, $timetype, $timezone, $calendar, $pattern);
        return $formatter->format($date);
    }

    /**
     * Escape a string for corresponding context
     *
     * @param string $value
     * @param string $context
     *      String context, valid value: html, htmlAttr, js, url, css
     *
     * @return string
     * @see \Laminas\Escaper\Escaper
     */
    public function escape(string $value, string $context = 'html'): string
    {
        $context = $context ? ucfirst($context) : 'Html';
        $method  = 'escape' . $context;
        $escaper = new Escaper(Pi::service('i18n')->getCharset());
        if (method_exists($escaper, $method)) {
            $value = $escaper->{$method}($value);
        }

        return $value;
    }

    /**
     * Clean a string by stripping HTML tags and removing unrecognizable characters
     *
     * @param string      $text        Text to be cleaned
     * @param string|null $replacement Replacement for stripped characters
     * @param array       $pattern     Custom pattern array
     *
     * @return string
     */
    public function strip(string $text, string|null $replacement = null, array $pattern = []): string
    {
        if (empty($pattern)) {
            $pattern = [
                "\t", "\r\n", "\r", "\n", "'", "\\",
                '&nbsp;', ',', '.', ';', ':', ')', '(',
                '"', '?', '!', '{', '}', '[', ']', '<', '>', '/', '+', '-', '_',
                '*', '=', '@', '#', '$', '%', '^', '&',
            ];
        }
        $replacement = (null === $replacement) ? ' ' : $replacement;

        // Strip HTML tags
        $text = $text ? strip_tags($text) : '';
        // Sanitize
        $text = $text ? _escape($text) : '';

        // Clean up
        $text = $text ? preg_replace('`\[.*\]`U', '', $text) : '';
        $text = $text ? preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '', $text) : '';
        $text = $text
            ? preg_replace(
                '/&([a-z])'
                . '(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);/i',
                '\\1',
                $text
            )
            : '';
        return $text ? str_replace($pattern, $replacement, $text) : '';
    }

    public function getConfig()
    {

    }

    /**
     * Locale-dependent formatting/parsing of number
     * using pattern strings and/or canned patterns
     *
     * @param int|float   $value
     * @param string|null $currency
     * @param string|null $locale
     *
     * @return string
     */
    public function setCurrency($value, $currency = null, $locale = null)
    {
        $result   = $value;
        $currency = (null === $currency) ? 'IRR' : $currency;
        if ($currency) {
            $style     = 'CURRENCY';
            $formatter = $this->getNumberFormatter($style, $locale);
            $result    = $formatter->formatCurrency((int)$value, $currency);
        }

        return $result;
    }

    /**
     * Load number formatter
     *
     * @param string|null $style
     * @param string|null $pattern
     * @param string|null $locale
     *
     * @return NumberFormatter|null
     * @see NumberFormatter
     *
     */
    public function getNumberFormatter($style = null, $pattern = null, $locale = null) {
        if (!class_exists('NumberFormatter')) {
            return null;
        }

        $locale    = $locale ?: 'fa_IR';
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        if ($pattern) {
            $formatter->setPattern($pattern);
        }

        return $formatter;
    }
}