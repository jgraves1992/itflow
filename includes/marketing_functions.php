<?php
/**
 * ITFlow Marketing Module - Helper Functions
 * Include this from any file that needs template variable processing.
 * The cron processor includes it automatically; agent pages get it via inc_all_custom.php
 * (add require_once __DIR__ . '/marketing_functions.php'; to inc_all_custom.php, or
 *  include it directly at the top of cron/custom/marketing_processor.php).
 */

/**
 * Replace {{variable}} placeholders in an email subject or body.
 *
 * @param string $template        Raw template text
 * @param array  $lead            Row from marketing_leads
 * @param string $unsubscribe_url Full URL to the unsubscribe page
 * @return string
 */
function processMarketingVars(string $template, array $lead, string $unsubscribe_url): string
{
    $first_name = explode(' ', trim($lead['lead_name']))[0];

    $vars = [
        '{{name}}'             => $lead['lead_name']    ?? '',
        '{{first_name}}'       => $first_name,
        '{{company}}'          => $lead['lead_company'] ?? '',
        '{{email}}'            => $lead['lead_email']   ?? '',
        '{{phone}}'            => $lead['lead_phone']   ?? '',
        '{{unsubscribe_link}}' => $unsubscribe_url,
        // HTML anchor version for rich-text bodies
        '{{unsubscribe_anchor}}' => '<a href="' . htmlspecialchars($unsubscribe_url, ENT_QUOTES) . '">unsubscribe</a>',
    ];

    return str_replace(array_keys($vars), array_values($vars), $template);
}
