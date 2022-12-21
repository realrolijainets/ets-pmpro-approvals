 <?php
require_once PMPRO_DIR . '/adminpages/admin_header.php';

$update_sql = "UPDATE wpaj_pmpro_memberships_users SET membership_id = '2', initial_payment = '385' WHERE membership_id = '4' AND user_id = '12'";
$update_sql = "UPDATE wpaj_pmpro_memberships_users SET membership_id = '3',initial_payment = '440'  WHERE membership_id = '5'";
$update_order_sql = "UPDATE wpaj_pmpro_membership_orders  SET membership_id = '2', subtotal='385',total='385' WHERE membership_id = '4'";
$update_order_sql = "UPDATE wpaj_pmpro_membership_orders  SET membership_id = '3',subtotal='440',total='440' WHERE membership_id = '5'";
//$wpdb->query($update_sql);
 global $wpdb;
 $args = array(
    'number' => -1,
    'role' => 'Subscriber',
    'meta_key' => 'pmpro_stripe_customerid',
    'meta_compare' => '=' ,
);
$all_users = new WP_User_Query( $args );
$select_sql = "SELECT * FROM wp_users AS wu
            LEFT JOIN wp_usermeta AS wum ON wu.ID = wum.user_id AND wum.meta_key = 'pmpro_stripe_customerid'  
            LEFT JOIN wp_pmpro_memberships_users AS `pmu`  ON `pmu`.`user_id` = wu.ID
            LEFT JOIN wp_pmpro_membership_orders AS `pmo`  ON `pmo`.`user_id` = wu.ID
            WHERE wum.meta_key = 'pmpro_stripe_customerid'
            GROUP BY  `pmo`.`user_id`";
//$stipe_members = $wpdb->get_results( $select_sql );
if ( ! empty( $all_users->get_results() ) ) {
    ?>
    <h3>Stripe Connected Members</h3>

    <table class="widefat stipe-members">
        <thead>
            <tr class="thead">
                <th><?php _e( 'ID', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'First Name', 'pmpro-approvals' ); ?></th>        
                <th><?php _e( 'Last Name', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Email', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Membership', 'pmpro-approvals' ); ?></th>            
                <th><?php _e( 'Price', 'pmpro-approvals' ); ?></th>         
                <th><?php _e( 'Start Date', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Membership Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Order Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Stripe Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'API Action', 'pmpro-approvals' ); ?></th>
            </tr>
        </thead>
        <tbody id="users" class="list:user user-list">
            <?php
            $end_date = 'Never';
            foreach ( $all_users->get_results() as $user ) {
                $user_id = $user->ID;
                $active_level = pmpro_getMembershipLevelForUser( $user_id );
                $member_sql = "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id ='".$user_id."' ORDER BY id DESC LIMIT 1; ";
                $stipe_active_members = $wpdb->get_row( $member_sql );
                $member_order_sql = "SELECT * FROM $wpdb->pmpro_membership_orders WHERE user_id ='".$user_id."' ORDER BY id DESC LIMIT 1; ";
                $stipe_active_members_order = $wpdb->get_row( $member_order_sql );
                $membership_level = new PMPro_Membership_Level($stipe_active_members->membership_id);
                $last_order = new MemberOrder();
                $last_order->getLastMemberOrder( $user_id );
                $subscription_transaction_id = $last_order->subscription_transaction_id;
                $status = $last_order->Gateway->getSubscriptionStatus($last_order);
                ?>
                    <tr>
                        <td><?php echo $user_id; ?></td>
                        <td><?php echo get_user_meta($user_id, 'first_name', true); ?></td>
                        <td><?php echo get_user_meta($user_id, 'last_name', true); ?></td>
                        <td><a href="<?php echo get_edit_user_link($user_id); ?>"><?php echo $user->user_email;?></a></td>
                        <td><?php echo $membership_level->name; ?></td>
                        <td><?php echo $stipe_active_members->initial_payment; ?></td>
                        <td><?php echo date('F d, Y', strtotime($stipe_active_members->startdate)); ?></td>
                        <td><?php echo $stipe_active_members->status; ?></td>
                        <td><?php echo $stipe_active_members_order->status; ?></td>
                        <td><?php if ( $status ) {
                            echo $status;
                        } else{
                            echo 'None';
                        }
                        ?></td>
                        <td>
                            <form method="post">
                                <button type="submit" name="ets_run_stripe_api"><?php echo __( 'RUN API', 'pmpro-approvals' );?></button>
                                <input type="hidden" name="wp_user_id" value="<?php echo $user_id;?>">
                                <input type="hidden" name="pmpro_level_id" value="<?php echo $stipe_active_members->membership_id;?>">
                            </form>
                        </td>
                        
                    </tr>
                <?php
                
            }
            ?>
            </tbody>
            <?php
            if (isset( $_POST['wp_user_id'] ) && isset( $_POST['pmpro_level_id'] ) ) {
                $user_id = $_POST['wp_user_id'];
                $pmpro_level_id = $_POST['pmpro_level_id'];
                $last_order = new MemberOrder();
                $last_order->getLastMemberOrder( $user_id );
                //Cancel old subscription
               /* if ( ! empty( $last_order ) && ! empty( $last_order->subscription_transaction_id ) ) {
                    $subscription = $last_order->Gateway->get_subscription( $last_order->subscription_transaction_id );
                    var_dump( $last_order->Gateway );
                    if ( ! empty( $subscription ) ) {
                    //$last_order->Gateway->cancelSubscriptionAtGateway( $subscription, true );
                    }
                }*/
                if (! empty( $last_order )) {
                    $user_level = new PMPro_Membership_Level($pmpro_level_id);
                    $customer_id = get_user_meta($user_id, 'pmpro_stripe_customerid', true);
                    if ( ! empty( $last_order ) && pmpro_isLevelRecurring( $user_level ) && $customer_id ) {
                        $last_order->PaymentAmount = $user_level->billing_amount;
                        $last_order->BillingPeriod    = $user_level->cycle_period;
                        $last_order->BillingFrequency = $user_level->cycle_number;
                        try{
                            $subscription = $last_order->Gateway->create_subscription_for_customer_from_order($customer_id, $last_order );
                            update_pmpro_membership_order_meta( $last_order->id, 'ets_check_subscription', $subscription );
                        }
                        catch ( Stripe\Error\Base $e ) {
                            $msgt = $e->getMessage();
                            update_pmpro_membership_order_meta( $last_order->id, 'ets_check_error_msg', $msgt );
                            
                        } catch ( \Throwable $e ) {
                            $msgt = $e->getMessage();
                            update_pmpro_membership_order_meta( $last_order->id, 'ets_check_error_msg_1', $msgt ); 
                        } catch ( \Exception $e ) {
                            $msgt = $e->getMessage();
                            update_pmpro_membership_order_meta( $last_order->id, 'ets_check_error_msg_2', $msgt ); 
                        }
                        if ( empty( $subscription ) ) {
                            // There was an issue creating the subscription.
                            $msgt = __( 'Error creating subscription for customer.', 'paid-memberships-pro' );
                            update_pmpro_membership_order_meta( $last_order->id, 'ets_check_error_msg_empty', $msgt );
                            $last_order->error      = __( 'Error creating subscription for customer.', 'paid-memberships-pro' );
                            $last_order->shorterror = $last_order->error;
                            return false;
                        }
                        // Successfully created a subscription.
                        $subscription_transaction_id = $subscription->id;
                        $last_order->subscription_transaction_id = $subscription_transaction_id;
                        //$last_order->Gateway->create_subscription()
                        //$this->create_subscription( $order );
                    }
                    $last_order->saveOrder();
                }
            }
}?>

<script type="text/javascript">
    jQuery('.stipe-members').DataTable();
</script>
<style type="text/css">
    #wpfooter{
        display: none;
    }
</style>
<?php
    //require_once PMPRO_DIR . '/adminpages/admin_footer.php';
?>