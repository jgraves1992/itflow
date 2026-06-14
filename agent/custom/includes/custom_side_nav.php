<?php
// Marketing module nav items — add these to your custom_side_nav.php
// or replace this file if you have no other custom nav entries.
?>
<li class="nav-item">
    <a href="marketing_leads.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'marketing_leads.php' || basename($_SERVER['PHP_SELF']) == 'marketing_lead_details.php') echo 'active'; ?>">
        <i class="fas fa-user-tag nav-icon"></i>
        <p>Leads</p>
    </a>
</li>
<li class="nav-item">
    <a href="marketing_sequences.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'marketing_sequences.php' || basename($_SERVER['PHP_SELF']) == 'marketing_sequence_details.php') echo 'active'; ?>">
        <i class="fas fa-envelope-open-text nav-icon"></i>
        <p>Email Sequences</p>
    </a>
</li>
