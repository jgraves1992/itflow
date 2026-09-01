<!-- Main Sidebar Container -->
<aside class="app-sidebar shadow d-print-none" data-bs-theme="dark">

    <div class="sidebar-brand">
        <a class="brand-link" href="../<?= $config_start_page ?>">
            <i class="fas fa-arrow-left me-2"></i>
            <span class="brand-text h5 mb-0">Back | <strong>Custom</strong></span>
        </a>
    </div>

    <!-- Sidebar -->
    <div class="sidebar-wrapper">

        <!-- Sidebar Menu -->
        <nav>

            <ul class="nav nav-pills sidebar-menu flex-column mt-2" data-lte-toggle="treeview" data-accordion="false">

                <li class="nav-header">MARKETING</li>

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

            </ul>

        </nav>
        <!-- /.sidebar-menu -->

        <div class="sidebar-custom mb-3">

        </div>

    </div>
    <!-- /.sidebar -->
</aside>
