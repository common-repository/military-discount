<?php
/********************************
Form submit
********************************/
if (isset($_POST['gr_private_key'])){
	//Only letters & numbers
	update_option("gr_private_key", preg_replace('~[^a-zA-Z0-9]+~', '', $_POST['gr_private_key']));
	//Only letters
	update_option("gr_type", preg_replace('/\PL/u', '', $_POST['gr_type']));
	update_option("gr_show_checkout", preg_replace('/\PL/u', '', $_POST['gr_show_checkout']));
	//Only numbers
	update_option("gr_amount", preg_replace("/[^0-9]/","",$_POST['gr_amount']));
	update_option("gr_show_cart", preg_replace("/[^0-9]/","",$_POST['gr_show_cart']));
	//Only 0 or 1
	$v = $_POST['gr_show_cart_button'];
	if ($v == 'on'){$v='1';}else{$v='0';}
	update_option("gr_show_cart_button", $v);

	$post = [
		'private_key' 		=> $_POST['gr_private_key'],
		'display_name' 		=> stripslashes(trim($_POST['display_name'])),
		'display_message' 	=> stripslashes(trim($_POST['display_message'])),
		'domain'   			=> $_POST['domain'],
		'fetch_url'			=> $_POST['fetch_url'] . '?action=gr_get_code'
	];
	$ch = curl_init('https://my.id.services/account/plan/update/remote');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	curl_close($ch);
	$is_saved = true;
}

/********************************
Get Data
********************************/
$display_name = get_bloginfo('name');
$display_message = 'Thanks for your service! Use this discount code to receive 15% off your order.';
$domain = preg_replace("(^https?://)", "", get_bloginfo('url'));
$fetch_url = $domain . '/wp-admin/admin-ajax.php';
if (get_option("gr_private_key")){
	//Fetch from IDS
	$post = [
		'private_key' 		=> get_option("gr_private_key")
	];
	$ch = curl_init('https://my.id.services/account/plan/get/remote');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);
	curl_close($ch);
	if (json_decode($response)){
		$response = json_decode($response);
		$display_name = $response->display_name;
		$display_message = $response->display_message;
		$domain = $response->domain;
		$fetch_url = str_replace("?action=gr_get_code", "", $response->fetch_url);
		$is_setup = true;
	}
}

?>

<div class="wrap">

<?=($is_saved ? '<div id="message" class="updated fade"><p><b>Your settings have been saved.</b></p></div>' : '')?>

<?=($is_setup ? '<h3>ID Services (Active)</h3>' : '<h3>Connect an ID Services Account</h3>')?>

<?php
if (!$is_setup && get_option('gr_private_key')){
	echo '<font style="color:red">The attached key is not active.</font>';
}
?>

<form method="post" action="admin.php?page=idsgr.php">

<table class="form-table">

        <tr valign="top">
        <th scope="row"><img src="https://cdn.id.services/i/logo-circle.png" style="float:left;width:19px;height:19px;" />&nbsp;&nbsp;Private Key</th>
        <td>
                <input size="40" type="text" name="gr_private_key" value="<?php echo get_option('gr_private_key') ; ?>" />
				<?php
				if (!$is_setup){
					submit_button('Connect', 'primary', 'submit', false);
					?>
					<h4>Create Private Key</h4>
					<ol>
						<li>Create an <a href="https://my.id.services/account" target="_blank">ID Services</a> Account.</li>
						<li>Click the <a href="https://my.id.services/account/api" target="_blank">API</a> tab and copy your <b>Private Key</b> into the box above.</li>
						<li>Make sure Coupons are enabled in <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout">Checkout Settings</a>.
					</ol>
					<?php
				}
				?>
        </td>
        </tr>

				<!--Store Name-->
				<tr valign="top">
				<th scope="row"><img src="https://cdn.id.services/i/logo-circle.png" style="float:left;width:19px;height:19px;" />&nbsp;&nbsp;Support</th>
				<td>woocommerce@id.services</td>
				</tr>

</table>

<hr>

<h3>General Settings</h3>
<table class="form-table">

		<!--Store Name-->
		<tr valign="top">
		<th scope="row">Store Name</th>
		<td><input type="text" size="60" name="display_name" value="<?=$display_name?>" <?=($is_setup ? '' : 'readonly')?> /></td>
		</tr>

		<!--Domain-->
		<tr valign="top">
		<th scope="row">Domain</th>
		<td><input type="text" size="60" name="domain" value="<?=$domain?>" <?=($is_setup ? '' : 'readonly')?> /></td>
		</tr>

		<!--AJAX File-->
		<tr valign="top">
		<th scope="row">admin-ajax.php location:</th>
			<td>
				<input type="text" size="60" name="fetch_url" value="<?=$fetch_url?>" <?=($is_setup ? '' : 'readonly')?> />
				<?php
				if ($fetch_url){
					?>
					<p>This file generates a discount code in your store when a positive verification is made.</p>
					<p>In most cases, you don't need to edit this URL. However, if your Wordpress installation isn't in a default
					directory, you might need to update it. A quick way to test is to visit <b><?=$fetch_url?></b>. If you got a blank
					white page with the number "0", you're good-to-go. If not, you'll need to paste the URL of your admin-ajax.php file location.</p>
					<?php
				}
				?>
			</td>
		</tr>

</table>

<hr>

<h3>Discount Settings</h3>
<table class="form-table">

		<!--Discount Description-->
		<tr valign="top">
		<th scope="row">Display Message</th>
		<td><textarea name="display_message" <?=($is_setup ? '' : 'readonly')?> cols="60" style="resize: none;"><?=$display_message?></textarea></td>
		</tr>

		<!--Type (percent or fixed)-->
    <tr valign="top">
    <th scope="row">Type</th>
    <td>
			<select name="gr_type" <?=($is_setup ? '' : 'disabled')?>>
				<option value="percent" <?php echo (get_option('gr_type') =='percent' ? 'selected' : ''); ?>>Percent</option>
				<option value="fixed_cart" <?php echo (get_option('gr_type') =='fixed_cart' ? 'selected' : ''); ?>>Fixed</options>
			</select>
    </td>
    </tr>

		<!--Amount-->
    <tr valign="top">
    <th scope="row">Amount</th>
    <td>
			<input type="number" min="1" max="999" maxlength="5" size="5" name="gr_amount" value="<?php echo esc_attr( get_option('gr_amount') ); ?>" <?=($is_setup ? '' : 'readonly')?> />
    </td>
    </tr>
</table>

<hr>

<h3>Auto-Install</h3>
<p>Pre-made banners on the cart or checkout page are most popular option.
	If you'd like to use another option, like a button	or text,
	<a href="https://my.id.services/account/integrated" target="_blank">use HTML to create it</a>.
</p>

<table class="form-table">

		<!--Button on Cart Page-->
		<tr valign="top">
		<th scope="row">Add Button to Cart</th>
		<td>
			<input type="checkbox" name="gr_show_cart_button" <?php echo (get_option('gr_show_cart_button') =='1' ? 'checked="checked"' : ''); ?>>
		</td>
		</tr>

		<!--Banner on Cart Page-->
		<tr valign="top">
		<th scope="row">Add Banner to Cart</th>
		<td>
			<select name="gr_show_cart" <?=($is_setup ? '' : 'disabled')?>>
				<option value="0" <?php echo (get_option('gr_show_cart') =='0' ? 'selected' : ''); ?>>None</option>
				<option value="https://cdn.id.services/m/i/halfciv.png" <?php echo (get_option('gr_show_cart') =='https://cdn.id.services/m/i/halfciv.png' ? 'selected' : ''); ?>>halfciv.png</option>
				<option value="https://cdn.id.services/m/i/soldier1.png" <?php echo (get_option('gr_show_cart') =='https://cdn.id.services/m/i/soldier1.png' ? 'selected' : ''); ?>>soldier1.png</option>
				<option value="https://cdn.id.services/m/i/soldier2.png" <?php echo (get_option('gr_show_cart') =='https://cdn.id.services/m/i/soldier2.png' ? 'selected' : ''); ?>>soldier2.png</option>
				<option value="https://cdn.id.services/m/i/soldier3.png" <?php echo (get_option('gr_show_cart') =='https://cdn.id.services/m/i/soldier3.png' ? 'selected' : ''); ?>>soldier3.png</option>
				<option value="https://cdn.id.services/m/i/soldier4.jpg" <?php echo (get_option('gr_show_cart') =='https://cdn.id.services/m/i/soldier4.jpg' ? 'selected' : ''); ?>>soldier4.jpg</option>
			</select>
		</td>
		</tr>

		<!--Banner on Checkout Page-->
		<tr valign="top">
		<th scope="row">Add Banner to Checkout</th>
		<td>
			<select name="gr_show_checkout" <?=($is_setup ? '' : 'disabled')?>>
				<option value="0" <?php echo (get_option('gr_show_checkout') =='0' ? 'selected' : ''); ?>>None</option>
				<option value="https://cdn.id.services/m/i/halfciv.png" <?php echo (get_option('gr_show_checkout') =='https://cdn.id.services/m/i/halfciv.png' ? 'selected' : ''); ?>>halfciv.png</option>
				<option value="https://cdn.id.services/m/i/soldier1.png" <?php echo (get_option('gr_show_checkout') =='https://cdn.id.services/m/i/soldier1.png' ? 'selected' : ''); ?>>soldier1.png</option>
				<option value="https://cdn.id.services/m/i/soldier2.png" <?php echo (get_option('gr_show_checkout') =='https://cdn.id.services/m/i/soldier2.png' ? 'selected' : ''); ?>>soldier2.png</option>
				<option value="https://cdn.id.services/m/i/soldier3.png" <?php echo (get_option('gr_show_checkout') =='https://cdn.id.services/m/i/soldier3.png' ? 'selected' : ''); ?>>soldier3.png</option>
				<option value="https://cdn.id.services/m/i/soldier4.jpg" <?php echo (get_option('gr_show_checkout') =='https://cdn.id.services/m/i/soldier4.jpg' ? 'selected' : ''); ?>>soldier4.jpg</option>
			</select>
		</td>
		</tr>

		<tr>
			<td>
				<i>halfciv.png</i><br><img class="gr_image" src="https://cdn.id.services/m/i/halfciv.png" />
			</td>
			<td>
				<i>soldier1.png</i><br><img class="gr_image" src="https://cdn.id.services/m/i/soldier1.png" />
			</td>
			<td>
				<i>soldier2.png</i><br><img class="gr_image" src="https://cdn.id.services/m/i/soldier2.png" />
			</td>
			<td>
				<i>soldier3.png</i><br><img class="gr_image" src="https://cdn.id.services/m/i/soldier3.png" />
			</td>
			<td>
				<i>soldier4.jpg</i><br><img class="gr_image" src="https://cdn.id.services/m/i/soldier4.jpg" />
			</td>
		</tr>

</table>

<?php
if ($is_setup){
	submit_button();
}
?>

</form>

</div>
<style>
.gr_image{
	width:200px;
}
</style>
