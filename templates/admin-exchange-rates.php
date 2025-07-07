<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="exchange-rates-container">
        <div class="current-rate-card">
            <h2>Current Exchange Rate</h2>
            <?php 
            $current_rate = WP_Xendit_Currency_Converter::get_exchange_rate();
            $last_update = get_option('wp_xendit_last_rate_update', 'Never');
            ?>
            <div class="rate-display">
                <div class="rate-value">
                    <span class="currency-from">1 USD</span>
                    <span class="equals">=</span>
                    <span class="currency-to"><?php echo WP_Xendit_Currency_Converter::format_currency($current_rate, 'IDR'); ?></span>
                </div>
                <div class="rate-meta">
                    <p><strong>Last Updated:</strong> <?php echo esc_html($last_update); ?></p>
                </div>
            </div>
            
            <div class="rate-actions">
                <button type="button" class="button button-primary" id="update-rate-api">Update from API</button>
                <button type="button" class="button button-secondary" id="toggle-manual-update">Manual Update</button>
            </div>
            
            <div id="manual-update-form" style="display: none; margin-top: 20px;">
                <h3>Manual Rate Update</h3>
                <div class="manual-rate-input">
                    <label for="manual_exchange_rate">1 USD = ? IDR</label>
                    <input type="number" id="manual_exchange_rate" step="0.01" min="1" placeholder="<?php echo esc_attr($current_rate); ?>" />
                    <button type="button" class="button button-primary" id="save-manual-rate">Save</button>
                    <button type="button" class="button button-secondary" id="cancel-manual-update">Cancel</button>
                </div>
            </div>
        </div>

        <div class="rate-history-card">
            <h2>Exchange Rate History</h2>
            <?php 
            $rate_history = WP_Xendit_Exchange_Rate_API::get_rate_history(20);
            if (!empty($rate_history)): 
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Rate (1 USD = ? IDR)</th>
                        <th>Source</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rate_history as $rate): ?>
                    <tr>
                        <td><?php echo esc_html($rate->created_at); ?></td>
                        <td><?php echo WP_Xendit_Currency_Converter::format_currency($rate->rate, 'IDR'); ?></td>
                        <td><?php echo esc_html($rate->source); ?></td>
                        <td>
                            <span class="rate-type-<?php echo $rate->is_manual ? 'manual' : 'auto'; ?>">
                                <?php echo $rate->is_manual ? 'Manual' : 'Auto'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No exchange rate history found.</p>
            <?php endif; ?>
        </div>

        <div class="rate-settings-card">
            <h2>Auto-Update Settings</h2>
            <?php 
            $auto_update = get_option('wp_xendit_donation_auto_update_rate', 1);
            $update_interval = get_option('wp_xendit_donation_update_interval', 6);
            $exchange_api = get_option('wp_xendit_donation_exchange_api', 'exchangerate-api');
            ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('wp-xendit-donation'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto-Update</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wp_xendit_donation_auto_update_rate" value="1" <?php checked($auto_update, 1); ?> />
                                Enable automatic exchange rate updates
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Update Interval</th>
                        <td>
                            <select name="wp_xendit_donation_update_interval">
                                <option value="1" <?php selected($update_interval, 1); ?>>Every hour</option>
                                <option value="6" <?php selected($update_interval, 6); ?>>Every 6 hours</option>
                                <option value="12" <?php selected($update_interval, 12); ?>>Every 12 hours</option>
                                <option value="24" <?php selected($update_interval, 24); ?>>Daily</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Source</th>
                        <td>
                            <select name="wp_xendit_donation_exchange_api">
                                <option value="exchangerate-api" <?php selected($exchange_api, 'exchangerate-api'); ?>>ExchangeRate-API (Free)</option>
                                <option value="fixer" <?php selected($exchange_api, 'fixer'); ?>>Fixer.io</option>
                                <option value="bank-indonesia" <?php selected($exchange_api, 'bank-indonesia'); ?>>Bank Indonesia</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>

        <div class="cron-status-card">
            <h2>Cron Job Status</h2>
            <?php 
            $next_update = wp_next_scheduled('wp_xendit_update_exchange_rates');
            ?>
            <div class="cron-info">
                <p><strong>Status:</strong> 
                    <span class="status-<?php echo $next_update ? 'active' : 'inactive'; ?>">
                        <?php echo $next_update ? 'Active' : 'Inactive'; ?>
                    </span>
                </p>
                <?php if ($next_update): ?>
                <p><strong>Next Update:</strong> <?php echo date('Y-m-d H:i:s', $next_update); ?></p>
                <?php endif; ?>
                
                <div class="cron-actions">
                    <button type="button" class="button button-secondary" id="reschedule-cron">Reschedule Cron</button>
                    <?php if ($next_update): ?>
                    <button type="button" class="button button-secondary" id="clear-cron">Clear Cron</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update rate from API
    $('#update-rate-api').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('Updating...');
        
        $.post(ajaxurl, {
            action: 'update_exchange_rate',
            nonce: wp_xendit_admin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            button.prop('disabled', false).text('Update from API');
        });
    });
    
    // Toggle manual update form
    $('#toggle-manual-update').on('click', function() {
        $('#manual-update-form').toggle();
    });
    
    // Save manual rate
    $('#save-manual-rate').on('click', function() {
        var rate = $('#manual_exchange_rate').val();
        
        if (!rate || rate <= 0) {
            alert('Please enter a valid rate');
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('Saving...');
        
        $.post(ajaxurl, {
            action: 'manual_rate_update',
            rate: rate,
            nonce: wp_xendit_admin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            button.prop('disabled', false).text('Save');
        });
    });
    
    // Cancel manual update
    $('#cancel-manual-update').on('click', function() {
        $('#manual-update-form').hide();
        $('#manual_exchange_rate').val('');
    });
});
</script>