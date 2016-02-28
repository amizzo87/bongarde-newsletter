<div class='wrap'>
    <?php do_action('admin_notices'); ?>
    <h2>OHS Newsletter Settings</h2>

    <form id="saveohsnewsletter" method="post" action="<?php echo admin_url('options-general.php?page=ohs-newsletter'); ?>" name="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label>Sendgrid API Key</label></th>
                <td>
                    <input name="ohs_newsletter_sendgrid_api" class="regular-text" value="<?php echo get_option('ohs_newsletter_sendgrid_api'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Sendgrid List ID</label></th>
                <td>
                    <input name="ohs_newsletter_sendgrid_list" class="regular-text" value="<?php echo get_option('ohs_newsletter_sendgrid_list'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Redirect page</label></th>
                <td>
                    <input name="ohs_newsletter_redirect" class="regular-text" value="<?php echo get_option('ohs_newsletter_redirect'); ?>">
                    <p>Users will be redirected to this page after they confirm their email successfully.</p>
                </td>
            </tr>
            </tbody>
        </table>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
    </form>
</div>