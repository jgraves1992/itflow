<?php
require_once "includes/inc_all_admin.php";

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT config_huntress_api_key, config_huntress_api_secret, config_levelio_api_key, config_sherweb_client_id, config_sherweb_client_secret, config_sherweb_subscription_key FROM settings WHERE company_id = 1"));
$config_huntress_api_key    = nullable_htmlentities($row['config_huntress_api_key'] ?? '');
$config_huntress_api_secret = nullable_htmlentities($row['config_huntress_api_secret'] ?? '');
$config_levelio_api_key     = nullable_htmlentities($row['config_levelio_api_key'] ?? '');
$config_sherweb_client_id        = nullable_htmlentities($row['config_sherweb_client_id'] ?? '');
$config_sherweb_client_secret    = nullable_htmlentities($row['config_sherweb_client_secret'] ?? '');
$config_sherweb_subscription_key = nullable_htmlentities($row['config_sherweb_subscription_key'] ?? '');
?>

<div class="card card-dark">
    <div class="card-header py-3">
        <h3 class="card-title"><i class="fas fa-fw fa-plug mr-2"></i>Integrations</h3>
    </div>
    <div class="card-body">

        <!-- Huntress -->
        <h5 class="mb-1"><i class="fas fa-shield-alt mr-2 text-muted"></i>Huntress</h5>
        <p class="text-muted small mb-3">Syncs MDR, SAT, and ITDR seat counts into software license records on each cron run.</p>

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

        <hr>

        <!-- Level.io -->
        <h5 class="mb-1 mt-3"><i class="fas fa-laptop mr-2 text-muted"></i>Level.io</h5>
        <p class="text-muted small mb-3">Syncs device counts per client group into software license records on each cron run.</p>

        <form action="post.php" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label>API Key</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-key"></i></span>
                    </div>
                    <input type="text" class="form-control" name="config_levelio_api_key"
                        placeholder="Level.io API Key" value="<?= $config_levelio_api_key ?>">
                </div>
                <small class="text-muted">Found in Level.io portal under Settings &rarr; API.</small>
            </div>

            <button type="submit" name="save_settings_levelio" class="btn btn-primary">
                <i class="fa fa-check mr-2"></i>Save
            </button>
        </form>

        <hr>

        <!-- Sherweb -->
        <h5 class="mb-1 mt-3"><i class="fas fa-cloud mr-2 text-muted"></i>Sherweb</h5>
        <p class="text-muted small mb-3">Syncs per-subscription seat counts into software license records, and rolls up your distributor bill into a consolidated recurring expense, on each cron run.</p>

        <form action="post.php" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label>Client ID</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-id-badge"></i></span>
                    </div>
                    <input type="text" class="form-control" name="config_sherweb_client_id"
                        placeholder="Sherweb API Client ID" value="<?= $config_sherweb_client_id ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Client Secret</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control" name="config_sherweb_client_secret"
                        placeholder="Sherweb API Client Secret" value="<?= $config_sherweb_client_secret ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Subscription Key</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-key"></i></span>
                    </div>
                    <input type="password" class="form-control" name="config_sherweb_subscription_key"
                        placeholder="Ocp-Apim-Subscription-Key" value="<?= $config_sherweb_subscription_key ?>">
                </div>
                <small class="text-muted">Found in your Sherweb developer portal under API credentials.</small>
            </div>

            <button type="submit" name="save_settings_sherweb" class="btn btn-primary">
                <i class="fa fa-check mr-2"></i>Save
            </button>
        </form>

    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
