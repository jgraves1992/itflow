<?php
/**
 * ITFlow Marketing Module - Sequence Processor
 *
 * Finds active enrollments where next_send_at is due, queues the email
 * via ITFlow's email_queue table, logs it, and advances to the next step.
 *
 * Add to your crontab (runs every hour is recommended):
 *   0 * * * * php /path/to/itflow/cron/custom/marketing_processor.php
 *
 * Or add to cron/cron.php if ITFlow calls custom cron files from there.
 */

define('FROM_CRON', true);

$base_path = __DIR__ . '/../../';
require_once $base_path . 'config.php';
require_once $base_path . 'functions.php';
require_once $base_path . 'includes/marketing_functions.php';

// Lock file to prevent overlapping runs
$lock_file = sys_get_temp_dir() . '/itflow_marketing_processor.lock';

if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 600) {
    exit('Already running');
}

file_put_contents($lock_file, getmypid());

// Read system default sender settings
$settings_row = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT c.company_name, c.company_email, s.config_mail_from_name, s.config_mail_from_email
     FROM companies c
     JOIN settings s ON s.company_id = c.company_id
     WHERE c.company_id = 1
     LIMIT 1"));

$default_from_name  = $settings_row['config_mail_from_name']  ?: ($settings_row['company_name']  ?? 'Your Company');
$default_from_email = $settings_row['config_mail_from_email'] ?: ($settings_row['company_email'] ?? '');
$base_url           = rtrim($config_base_url ?? ('https://' . $_SERVER['SERVER_NAME']), '/');

// Fetch due enrollments
$enrollments = mysqli_query($mysqli,
    "SELECT e.*,
            l.lead_name, l.lead_email, l.lead_company, l.lead_phone,
            l.lead_unsubscribed, l.lead_unsubscribe_token,
            seq.sequence_name, seq.sequence_active,
            seq.sequence_from_name, seq.sequence_from_email,
            st.step_id, st.step_order, st.step_subject, st.step_body,
            st.step_delay_days, st.step_sequence_id
     FROM marketing_enrollments e
     JOIN marketing_leads l          ON l.lead_id       = e.enrollment_lead_id
     JOIN marketing_sequences seq    ON seq.sequence_id = e.enrollment_sequence_id
     JOIN marketing_sequence_steps st ON st.step_id     = e.enrollment_next_step_id
     WHERE e.enrollment_status       = 'active'
       AND e.enrollment_next_send_at <= NOW()
       AND l.lead_unsubscribed       = 0
       AND l.lead_archived_at        IS NULL
       AND seq.sequence_active       = 1
       AND seq.sequence_archived_at  IS NULL
     ORDER BY e.enrollment_next_send_at ASC
     LIMIT 100");

$processed = 0;
$errors    = 0;

while ($row = mysqli_fetch_assoc($enrollments)) {
    $enrollment_id = intval($row['enrollment_id']);
    $lead_id       = intval($row['enrollment_lead_id']);
    $sequence_id   = intval($row['enrollment_sequence_id']);
    $step_id       = intval($row['step_id']);

    $from_name  = $row['sequence_from_name']  ?: $default_from_name;
    $from_email = $row['sequence_from_email'] ?: $default_from_email;

    if (!$from_email) {
        $errors++;
        continue;
    }

    $unsubscribe_url = $base_url . '/guest/marketing_unsubscribe.php?token=' . urlencode($row['lead_unsubscribe_token']);

    $subject = processMarketingVars($row['step_subject'], $row, $unsubscribe_url);
    $body    = processMarketingVars($row['step_body'],    $row, $unsubscribe_url);

    // Append unsubscribe footer if not already in body
    if (strpos($body, $unsubscribe_url) === false) {
        $body .= "\n\n---\n<small>To stop receiving these emails, <a href=\"{$unsubscribe_url}\">click here to unsubscribe</a>.</small>";
    }

    // addToMailQueue() builds raw SQL without escaping — pre-escape values for it
    addToMailQueue([
        [
            'from'           => mysqli_real_escape_string($mysqli, $from_email),
            'from_name'      => mysqli_real_escape_string($mysqli, $from_name),
            'recipient'      => mysqli_real_escape_string($mysqli, $row['lead_email']),
            'recipient_name' => mysqli_real_escape_string($mysqli, $row['lead_name']),
            'subject'        => mysqli_real_escape_string($mysqli, $subject),
            'body'           => mysqli_real_escape_string($mysqli, $body),
        ]
    ]);

    // Log the send
    $recipient_escaped = mysqli_real_escape_string($mysqli, $row['lead_email']);
    $subject_escaped   = mysqli_real_escape_string($mysqli, $subject);

    mysqli_query($mysqli,
        "INSERT INTO marketing_email_log
            (log_lead_id, log_enrollment_id, log_sequence_id, log_step_id, log_recipient_email, log_subject)
         VALUES ($lead_id, $enrollment_id, $sequence_id, $step_id, '$recipient_escaped', '$subject_escaped')");

    // Find next step
    $next_step = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT * FROM marketing_sequence_steps
         WHERE step_sequence_id = $sequence_id AND step_order > {$row['step_order']}
         ORDER BY step_order ASC LIMIT 1"));

    if ($next_step) {
        $next_step_id  = intval($next_step['step_id']);
        $delay_days    = intval($next_step['step_delay_days']);
        $send_time     = $row['sequence_send_time'] ?? '09:00:00';
        $enrolled_date = date('Y-m-d', strtotime($row['enrollment_enrolled_at']));
        $next_send_at  = date('Y-m-d', strtotime($enrolled_date . " +$delay_days days")) . ' ' . $send_time;

        mysqli_query($mysqli,
            "UPDATE marketing_enrollments
             SET enrollment_next_step_id = $next_step_id,
                 enrollment_next_send_at = '$next_send_at'
             WHERE enrollment_id = $enrollment_id");
    } else {
        // All steps sent — mark as completed
        mysqli_query($mysqli,
            "UPDATE marketing_enrollments
             SET enrollment_status = 'completed',
                 enrollment_completed_at = NOW(),
                 enrollment_next_send_at = NULL
             WHERE enrollment_id = $enrollment_id");
    }

    $processed++;
}

@unlink($lock_file);

echo "Done: $processed processed, $errors errors\n";
