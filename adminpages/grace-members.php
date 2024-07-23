 <?php

require_once PMPRO_DIR . '/adminpages/admin_header.php';

$update_sql = "UPDATE wpaj_pmpro_memberships_users SET membership_id = '2', initial_payment = '385' WHERE membership_id = '4' AND user_id = '12'";
$update_sql = "UPDATE wpaj_pmpro_memberships_users SET membership_id = '3',initial_payment = '440'  WHERE membership_id = '5'";
$update_order_sql = "UPDATE wpaj_pmpro_membership_orders  SET membership_id = '2', subtotal='385',total='385' WHERE membership_id = '4'";
$update_order_sql = "UPDATE wpaj_pmpro_membership_orders  SET membership_id = '3',subtotal='440',total='440' WHERE membership_id = '5'";


$sql = "UPDATE wpaj_pmpro_membership_orders INNER JOIN `wpaj_usermeta` as usermeta ON `usermeta`.`user_id` = `wpaj_pmpro_membership_orders`.`user_id` AND `usermeta`.`meta_key` = 'pmpro_stripe_customerid' SET gateway = 'etsstripe' WHERE gateway = 'stripe'";

"SELECT * FROM wpaj_pmpro_membership_orders SET gateway = 'etsstripe', gateway_environment='live' WHERE gateway = 'stripe' AND gateway_environment='sandbox'";
            
 global $wpdb;
 $args = array(
    'number' => -1,
    //'role' => 'Subscriber',
    'meta_key' => 'grace_level',
    'meta_compare' => '=' ,
);
$all_users = new WP_User_Query( $args );

if ( ! empty( $all_users->get_results() ) ) {
    ?>
    <h3>Grace Members</h3>

    <table class="widefat grace-members">
        <thead>
            <tr class="thead">
                <th><?php _e( 'ID', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'First Name', 'pmpro-approvals' ); ?></th>        
                <th><?php _e( 'Last Name', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Email', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Membership', 'pmpro-approvals' ); ?></th>            
                <th><?php _e( 'Price', 'pmpro-approvals' ); ?></th>         
                <th><?php _e( 'Start Date', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'End Date', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Membership Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Order Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Stripe Status', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Gateway', 'pmpro-approvals' ); ?></th>
                <th><?php _e( 'Action', 'pmpro-approvals' ); ?></th>
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
                         <td><?php if(isset($stipe_active_members->enddate) && $stipe_active_members->enddate && $stipe_active_members->enddate != '0000-00-00 00:00:00'){
                            echo date('F d, Y', strtotime($stipe_active_members->enddate));

                            } else{
                                echo "Never";
                            } ?></td>
                        <td><?php echo $stipe_active_members->status; ?></td>
                        <td><?php echo $stipe_active_members_order->status; ?></td>

                        <td><?php if ( $status ) {
                            echo $status;
                        } else{
                            echo 'None';
                        }
                        ?></td>
                        <td><?php echo $stipe_active_members_order->gateway; ?></td>
                        <td>
                            <form method="post">
                                <button type="submit" name="ets_run_grace_fix"><?php echo __( 'Update', 'pmpro-approvals' );?></button>
                                <input type="hidden" name="ets_wp_user_id" value="<?php echo $user_id;?>">
                                <input type="hidden" name="ets_pmpro_level_id" value="<?php echo $stipe_active_members->membership_id;?>">
                            </form>
                        </td>
                        
                    </tr>
                <?php
                
            }
            ?>
            </tbody>
            <?php
            if (isset( $_POST['ets_wp_user_id'] ) && isset( $_POST['ets_pmpro_level_id'] ) ) {
            	global $wpdb;
                $user_id = $_POST['ets_wp_user_id'];
                $pmpro_level_id = $_POST['ets_pmpro_level_id'];
                $last_order = new MemberOrder();
                $last_order->getLastMemberOrder( $user_id, 'cancelled' );
                $last_order->status = 'success';
                $last_order->saveOrder();
				$level_data = pmpro_getLevel($pmpro_level_id);
				$initial_payment = $level_data->initial_payment;
				$billing_amount = $level_data->billing_amount;
				$cycle_number = $level_data->cycle_number;
				$cycle_period = $level_data->cycle_period;
				$sql = "SELECT startdate FROM $wpdb->pmpro_memberships_users WHERE user_id=$user_id AND membership_id = $pmpro_level_id AND status='expired' order by id DESC LIMIT 1";
            	$res = $wpdb->get_row($sql);
            	if ($res) {
					$startdate = $res->startdate;
            	}
            	$update_sql = "UPDATE $wpdb->pmpro_memberships_users SET initial_payment= $initial_payment,billing_amount = $billing_amount,cycle_number = $cycle_number, cycle_period = '$cycle_period',startdate='$startdate' WHERE user_id=$user_id AND membership_id = $pmpro_level_id AND status='active'";
            	$success = $wpdb->query($update_sql);
            }
}?>

<script type="text/javascript">
    jQuery('.grace-members').DataTable();
</script>
<style type="text/css">
    #wpfooter{
        display: none;
    }
</style>
<?php
    //require_once PMPRO_DIR . '/adminpages/admin_footer.php';
?>