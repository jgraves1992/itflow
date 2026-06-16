<?php
require_once "includes/inc_all_admin.php";

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT config_huntress_api_key, config_huntress_api_secret FROM settings WHERE company_id = 1"));
$config_huntress_api_key    = nullable_htmlentities($row['config_huntress_api_key'] ?? '');
$config_huntress_api_secret = nullable_htmlentities($row['config_huntress_api_secret'] ?? '');
?>

<div class="card card-dark">
    <div class="card-header py-3">
        <h3 class="card-title"><i class="fas fa-fw fa-plug mr-2"></i>Integrations</h3>
    </div>
    <div class="card-body">

        <h5 class="mb-1"><i class="fas fa-shield-alt mr-2 text-muted"></i>Huntress</h5>
        <p class="text-muted small mb-3">Enables automatic seat-count sync from Huntress into software license records. Runs on each cron execution.</p>

        <form action="post.php" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label>API Key</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-key"></i></span>
                    </div>
                    <input type="text" class="form-control" name="config_huntress_api_key"
                        placeholder="Huntress API Key" value="<?= $config_huntress_api_key ?>">
                </div>
            </div>

            <div class="form-group">
                <label>API Secret</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control" name="config_huntress_api_secret"
                        placeholder="Huntress API Secret" value="<?= $config_huntress_api_secret ?>">
                </div>
                <small class="text-muted">Found in Huntress portal under Account &rarr; API Credentials.</small>
            </div>

            <button type="submit" name="save_settings_huntress" class="btn btn-primary">
                <i class="fa fa-check mr-2"></i>Save
            </button>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
