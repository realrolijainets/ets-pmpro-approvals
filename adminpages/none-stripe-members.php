<?php
require_once PMPRO_DIR . '/adminpages/admin_header.php';
global $wpdb;
/*"SELECT * FROM wp_users AS wu 
 LEFT JOIN wp_usermeta ON ( wu.ID = wp_usermeta.user_id AND wp_usermeta.meta_key = 'pmpro_stripe_customerid' ) 
 LEFT JOIN wp_pmpro_memberships_users AS `pmu`  ON `pmu`.`user_id` = `um`.`user_id`  
 WHERE `um`.`meta_key` != `pmpro_stripe_customerid` AND `pmu`.`status` = 'active'";*/
//LEFT JOIN $wpdb->usermeta AS `um`  ON `wu`.`ID` = `um`.`user_id` 
 //LEFT JOIN $wpdb->usermeta AS `um`  ON `wu`.`ID` = `um`.`user_id` 
$args = array(
    'number' => -1,
    //'role' => 'Subscriber',
    'meta_key' => 'pmpro_stripe_customerid',
    'meta_compare' => 'NOT EXISTS',
);
$all_users = new WP_User_Query( $args );
?>
<h3>Not Stripe Connected Members</h3>
<br>
<form method="post">
	<label>Set Expiry Date</label>
	<input type="date" name="set_expiry_date" <?php if (isset($_POST['set_expiry_date'])) echo $_POST['set_expiry_date'];?>
		
	<?php  ?>>
	<button type="submit" >Submit</button>
</form>
<?php

if ( ! empty( $all_users->get_results() ) ) {
    ?>
	<table class="widefat non-stipe-members">
		<thead>
			<tr class="thead">
				<th><?php _e( 'ID', 'pmpro-approvals' ); ?></th>
				<th><?php _e( 'First Name', 'pmpro-approvals' ); ?></th>		
				<th><?php _e( 'Last Name', 'pmpro-approvals' ); ?></th>
				<th><?php _e( 'Email', 'pmpro-approvals' ); ?></th>
				<th><?php _e( 'Membership', 'pmpro-approvals' ); ?></th>			
				<th><?php _e( 'Price', 'pmpro-approvals' ); ?></th>			
				<th><?php _e( 'Start Date', 'pmpro-approvals' ); ?></th>
				<th><?php _e( 'Expiry Date', 'pmpro-approvals' ); ?></th>
			</tr>
		</thead>
		<tbody id="users" class="list:user user-list">
			<?php
			
			foreach ( $all_users->get_results() as $user ) {
		    	$user_id = $user->ID;
		    	$end_date = 'Never';
		    	$active_level = pmpro_getMembershipLevelForUser( $user_id );
		    	if ($active_level) {
		    		if ($active_level->enddate) {
		    			$end_date = date('F d, Y',$active_level->enddate);
		    		}
		    		if (isset($_POST['set_expiry_date'])) {
						$set_expiry_date = $_POST['set_expiry_date'];
						$update_sql = "UPDATE $wpdb->pmpro_memberships_users SET enddate = '".$set_expiry_date."' WHERE user_id = $user_id AND status = 'active'";
						$row = $wpdb->query($update_sql);
			    		$end_date = date('F d, Y',strtotime($set_expiry_date));
					}
		    		?>
		    		<tr>
						<td><?php echo $user_id; ?></td>
						<td><?php echo get_user_meta($user_id, 'first_name', true); ?></td>
						<td><?php echo get_user_meta($user_id, 'last_name', true); ?></td>
						<td><a href="<?php echo get_edit_user_link($user_id); ?>"><?php echo $user->user_email;?></a></td>
						<td><?php echo $active_level->name; ?></td>
						<td><?php echo $active_level->initial_payment; ?></td>
						<td><?php echo date('F d, Y', $active_level->startdate); ?></td>
						<td><?php echo $end_date; ?></td>
					</tr>
				<?php
			    }
		    }
		    ?>
		    </tbody>
		    <?php
}?>

<script type="text/javascript">
	jQuery('.non-stipe-members').DataTable();
</script>
<style type="text/css">
    #wpfooter{
        display: none;
    }
</style>
<?php
	//require_once PMPRO_DIR . '/adminpages/admin_footer.php';
?>