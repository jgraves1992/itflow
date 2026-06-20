<?php

require_once '../../../includes/modal_header.php';

$client_id = intval($_GET['client_id'] ?? 0);

$sql_sync_sources = mysqli_query($mysqli, "
    SELECT software_sync_source,
        COUNT(*) AS client_count,
        COALESCE(SUM(software_seats), 0) AS total_seats,
        COALESCE(SUM(CASE WHEN software_billing_exempt = 1 THEN software_seats ELSE 0 END), 0) AS exempt_seats
    FROM software
    WHERE software_sync_source IS NOT NULL
    AND software_sync_source != ''
    AND software_archived_at IS NULL
    GROUP BY software_sync_source
    ORDER BY software_sync_source ASC
");

ob_start();

?>
<div class="modal-header bg-dark">
    <h5 class="modal-title"><i class="fa fa-fw fa-clock mr-2"></i>Create Recurring Expense</h5>
    <button type="button" class="close text-white" data-dismiss="modal">
        <span>&times;</span>
    </button>
</div>
<form action="post.php" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="modal-body">

        <div class="form-row">

            <div class="form-group col-md">
                <label>Frequency <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-sync-alt"></i></span>
                    </div>
                    <select class="form-control select2" name="frequency" required>
                        <option value="1">Monthly</option>
                        <option value="2">Annually</option>
                    </select>
                </div>
            </div>

            <div class="form-group col-md">
                <label>Month <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                    </div>
                    <select class="form-control select2" name="month" required>
                        <option value="">- Select a Month -</option>
                        <option value="1">01 - January</option>
                        <option value="2">02 - February</option>
                        <option value="3">03 - March</option>
                        <option value="4">04 - April</option>
                        <option value="5">05 - May</option>
                        <option value="6">06 - June</option>
                        <option value="7">07 - July</option>
                        <option value="8">08 - August</option>
                        <option value="9">09 - September</option>
                        <option value="10">10 - October</option>
                        <option value="11">11 - November</option>
                        <option value="12">12 - December</option>
                    </select>
                </div>
            </div>

            <div class="form-group col-md">
                <label>Day <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-calendar"></i></span>
                    </div>
                    <input type="text" class="form-control" inputmode="numeric" pattern="(1[0-9]|2[0-8]|[1-9])" name="day" placeholder="Enter a day (1-28)" required>
                </div>
            </div>

        </div>

        <div class="form-row">
            <div class="form-group col-md">
                <label>Amount <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                    </div>
                    <input type="text" class="form-control" inputmode="decimal" pattern="-?[0-9]*\.?[0-9]{0,2}" name="amount" placeholder="0.00" required>
                </div>
            </div>
        </div>

        <?php if (mysqli_num_rows($sql_sync_sources) > 0): ?>
        <div class="form-row">
            <div class="form-group col-md">
                <label>Vendor Sync Source <small class="text-muted">(optional &mdash; for one consolidated bill across all clients)</small></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-link"></i></span>
                    </div>
                    <select class="form-control select2" name="sync_source">
                        <option value="">- None -</option>
                        <?php while ($src = mysqli_fetch_assoc($sql_sync_sources)) {
                            $src_name     = nullable_htmlentities($src['software_sync_source']);
                            $client_count = intval($src['client_count']);
                            $total_seats  = intval($src['total_seats']);
                            $exempt_seats = intval($src['exempt_seats']);
                            $billable     = $total_seats - $exempt_seats;
                        ?>
                            <option value="<?= $src_name ?>">
                                <?= $src_name ?> — <?= $billable ?> billable seat(s) across <?= $client_count ?> client(s)<?= $exempt_seats ? " ($exempt_seats exempt)" : '' ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-group col-md">
                <label>Unit Cost <small class="text-muted">(per seat)</small></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-dollar-sign"></i></span>
                    </div>
                    <input type="text" class="form-control" inputmode="decimal" pattern="-?[0-9]*\.?[0-9]{0,2}" name="unit_cost" placeholder="0.00">
                </div>
            </div>
        </div>
        <small class="text-muted d-block mb-3">When set, the Amount above is recalculated automatically (total billable seats across every client &times; unit cost) each time seats sync. Mark a license "billing exempt" on the software record to exclude free/partner seats from the total.</small>
        <?php endif; ?>

        <div class="form-row">

            <div class="form-group col-md">
                <label>Account <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-piggy-bank"></i></span>
                    </div>
                    <select class="form-control select2" name="account" required>
                        <option value="">- Account -</option>
                        <?php

                        $sql = mysqli_query($mysqli, "SELECT account_id, account_name, opening_balance FROM accounts WHERE account_archived_at IS NULL ORDER BY account_name ASC");
                        while ($row = mysqli_fetch_assoc($sql)) {
                            $account_id = intval($row['account_id']);
                            $account_name = nullable_htmlentities($row['account_name']);
                            $opening_balance = floatval($row['opening_balance']);

                            $sql_payments = mysqli_query($mysqli, "SELECT SUM(payment_amount) AS total_payments FROM payments WHERE payment_account_id = $account_id");
                            $row = mysqli_fetch_assoc($sql_payments);
                            $total_payments = floatval($row['total_payments']);

                            $sql_revenues = mysqli_query($mysqli, "SELECT SUM(revenue_amount) AS total_revenues FROM revenues WHERE revenue_account_id = $account_id");
                            $row = mysqli_fetch_assoc($sql_revenues);
                            $total_revenues = floatval($row['total_revenues']);

                            $sql_expenses = mysqli_query($mysqli, "SELECT SUM(expense_amount) AS total_expenses FROM expenses WHERE expense_account_id = $account_id");
                            $row = mysqli_fetch_assoc($sql_expenses);
                            $total_expenses = floatval($row['total_expenses']);

                            $balance = $opening_balance + $total_payments + $total_revenues - $total_expenses;

                            ?>
                            <option <?php if ($config_default_expense_account == $account_id) { echo "selected"; } ?> value="<?php echo $account_id; ?>"><div class="float-left"><?php echo $account_name; ?></div><div class="float-right"> [$<?php echo number_format($balance, 2); ?>]</div></option>

                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group col-md">
                <label>Vendor <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-building"></i></span>
                    </div>
                    <select class="form-control select2" name="vendor" required>
                        <option value="">- Vendor -</option>
                        <?php

                        $sql = mysqli_query($mysqli, "SELECT vendor_id, vendor_name FROM vendors WHERE vendor_client_id = 0 AND vendor_archived_at IS NULL ORDER BY vendor_name ASC");
                        while ($row = mysqli_fetch_assoc($sql)) {
                            $vendor_id = intval($row['vendor_id']);
                            $vendor_name = nullable_htmlentities($row['vendor_name']);
                            ?>
                            <option value="<?php echo $vendor_id; ?>"><?php echo $vendor_name; ?></option>

                            <?php
                        }
                        ?>
                    </select>
                    <div class="input-group-append">
                        <a class="btn btn-secondary" href="vendors.php" target="_blank"><i class="fas fa-fw fa-plus"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Description <strong class="text-danger">*</strong></label>
            <textarea class="form-control" rows="6" name="description" placeholder="Enter a description" required></textarea>
        </div>

        <div class="form-group">
            <label>Reference</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-fw fa-file-alt"></i></span>
                </div>
                <input type="text" class="form-control" name="reference" placeholder="Enter a reference" maxlength="200">
            </div>
        </div>

        <div class="form-row">

            <div class="form-group col-md">
                <label>Category <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-list"></i></span>
                    </div>
                    <select class="form-control select2" name="category" required>
                        <option value="">- Category -</option>
                        <?php

                        $sql = mysqli_query($mysqli, "SELECT category_id, category_name FROM categories WHERE category_type = 'Expense' AND category_archived_at IS NULL ORDER BY category_name ASC");
                        while ($row = mysqli_fetch_assoc($sql)) {
                            $category_id = intval($row['category_id']);
                            $category_name = nullable_htmlentities($row['category_name']);
                            ?>
                            <option value="<?php echo $category_id; ?>"><?php echo $category_name; ?></option>

                            <?php
                        }
                        ?>
                    </select>
                    <div class="input-group-append">
                        <button class="btn btn-secondary ajax-modal" type="button"
                            data-modal-url="../admin/modals/category/category_add.php?category=Expense">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>


            </div>

            <?php if ($client_id) { ?>
                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
            <?php } else { ?>

                <div class="form-group col-md">
                    <label>Client</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-user"></i></span>
                        </div>
                        <select class="form-control select2" name="client_id" required>
                            <option value="0">- Client (Optional) -</option>
                            <?php

                            $sql = mysqli_query($mysqli, "SELECT client_id, client_name FROM clients ORDER BY client_name ASC");
                            while ($row = mysqli_fetch_assoc($sql)) {
                                $client_id_select = intval($row['client_id']);
                                $client_name = nullable_htmlentities($row['client_name']);
                                ?>
                                <option value="<?php echo $client_id_select; ?>"><?php echo $client_name; ?></option>

                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>

            <?php } ?>

        </div>

    </div>
    <div class="modal-footer">
        <button type="submit" name="create_recurring_expense" class="btn btn-primary text-bold"><i class="fa fa-fw fa-check mr-2"></i>Create</button>
        <button type="button" class="btn btn-light" data-dismiss="modal"><i class="fa fa-times mr-2"></i>Cancel</button>
    </div>
</form>

<?php
require_once '../../../includes/modal_footer.php';
