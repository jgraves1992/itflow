<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

// Handles both pause and resume enrollment actions

if (isset($_POST['pause_marketing_enrollment'])) {
    $new_status = 'paused';
} elseif (isset($_POST['resume_marketing_enrollment'])) {
    $new_status = 'active';
} else {
    return;
}

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$enrollment_id = intval($_POST['enrollment_id'] ?? 0);
$lead_id       = intval($_POST['lead_id'] ?? 0);

if (!$enrollment_id) {
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

// When resuming, recalculate next_send_at based on enrolled_at + next step delay
if ($new_status === 'active') {
    $enroll = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT * FROM marketing_enrollments WHERE enrollment_id = $enrollment_id"));

    if ($enroll && $enroll['enrollment_next_step_id']) {
        $step = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT st.step_delay_days, seq.sequence_send_time
             FROM marketing_sequence_steps st
             JOIN marketing_sequences seq ON seq.sequence_id = st.step_sequence_id
             WHERE st.step_id = {$enroll['enrollment_next_step_id']}"));

        if ($step) {
            $delay_days    = intval($step['step_delay_days']);
            $send_time     = $step['sequence_send_time'] ?? '09:00:00';
            $enrolled_date = date('Y-m-d', strtotime($enroll['enrollment_enrolled_at']));
            $next_send_at  = date('Y-m-d', strtotime($enrolled_date . " +$delay_days days")) . ' ' . $send_time;

            // If the calculated send datetime is in the past, use today at the sequence send time
            if (strtotime($next_send_at) < time()) {
                $next_send_at = date('Y-m-d') . ' ' . $send_time;
            }

            mysqli_query($mysqli,
                "UPDATE marketing_enrollments
                 SET enrollment_status = 'active', enrollment_next_send_at = '$next_send_at'
                 WHERE enrollment_id = $enrollment_id");

            $_SESSION['success'] = 'Enrollment resumed.';
            header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
            exit;
        }
    }
}

mysqli_query($mysqli,
    "UPDATE marketing_enrollments SET enrollment_status = '$new_status' WHERE enrollment_id = $enrollment_id");

$_SESSION['success'] = 'Enrollment ' . ($new_status === 'paused' ? 'paused' : 'resumed') . '.';
header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
exit;
