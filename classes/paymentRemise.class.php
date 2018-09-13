<?php
/*
Remise
Version: 1.0.0
Author: Collne Inc.
*/

class REMISE_SETTLEMENT
{
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	protected $paymod_id;
	protected $pay_method;
	protected $unavailable_method;

	public function __construct() {

		$this->paymod_id = 'remise';

		$this->initialize_data();

		if( $this->is_validity_acting( 'card' ) ) {
			add_filter( 'usces_filter_template_redirect', array( $this, 'member_update_settlement' ), 1 );
			add_action( 'usces_action_member_submenu_list', array( $this, 'e_update_settlement' ) );
			add_filter( 'usces_filter_member_submenu_list', array( $this, 'update_settlement' ), 10, 2 );
		}
	}

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Initialize
	 */
	public function initialize_data() {

		//$options = get_option( 'usces' );
	}

	/**
	 * 決済有効判定
	 * 引数が指定されたとき、支払方法で使用している場合に「有効」とする
	 * @param  ($type)
	 * @return boolean
	 */
	public function is_validity_acting( $type = '' ) {

		$acting_opts = $this->get_acting_settings();
		if( empty( $acting_opts ) ) {
			return false;
		}

		$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
		$method = false;

		switch( $type ) {
		case 'card':
			foreach( $payment_method as $payment ) {
				if( 'acting_remise_card' == $payment['settlement'] && 'activate' == $payment['use'] ) {
					$method = true;
					break;
				}
			}
			if( $method && $this->is_activate_card() ) {
				return true;
			} else {
				return false;
			}
			break;

		case 'conv':
			foreach( $payment_method as $payment ) {
				if( 'acting_remise_conv' == $payment['settlement'] && 'activate' == $payment['use'] ) {
					$method = true;
					break;
				}
			}
			if( $method && $this->is_activate_conv() ) {
				return true;
			} else {
				return false;
			}
			break;

		default:
			if( 'on' == $acting_opts['activate'] ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * クレジットカード決済有効判定
	 * @param  -
	 * @return boolean $res
	 */
	public function is_activate_card() {

		$acting_opts = $this->get_acting_settings();
		if( ( isset( $acting_opts['activate'] ) && 'on' == $acting_opts['activate'] ) && 
			( isset( $acting_opts['card_activate'] ) && ( 'on' == $acting_opts['card_activate'] ) ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**
	 * コンビニ・電子マネー決済サービス有効判定
	 * @param  -
	 * @return boolean $res
	 */
	public function is_activate_conv() {

		$acting_opts = $this->get_acting_settings();
		if( ( isset( $acting_opts['activate'] ) && 'on' == $acting_opts['activate'] ) && 
			( isset( $acting_opts['conv_activate'] ) && 'on' == $acting_opts['conv_activate'] ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**
	 * usces_filter_template_redirect
	 * クレジットカード登録・変更ページ表示
	 * @param  -
	 * @return -
	 */
	public function member_update_settlement() {
		global $usces;

		if( $usces->is_member_page( $_SERVER['REQUEST_URI'] ) ) {
			if( !usces_is_membersystem_state() || !usces_is_login() ) {
				return;
			}

			$acting_opts = $this->get_acting_settings();
			if( 'on' != $acting_opts['payquick'] ) {
				return;
			}

			if( isset( $_REQUEST['page'] ) && 'member_update_settlement' == $_REQUEST['page'] ) {
				$usces->page = 'member_update_settlement';
				$this->member_update_settlement_form();
				exit();
			}
		}
		return false;
	}

	/**
	 * usces_action_member_submenu_list
	 * クレジットカード登録・変更ページリンク
	 * @param  -
	 * @return -
	 * @echo   update_settlement()
	 */
	public function e_update_settlement() {
		global $usces;

		$member = $usces->get_member();
		$html = $this->update_settlement( '', $member );
		echo $html;
	}

	/**
	 * usces_filter_member_submenu_list
	 * クレジットカード登録・変更ページリンク
	 * @param  $html $member
	 * @return string $html
	 */
	public function update_settlement( $html, $member ) {
		global $usces;

		$acting_opts = $this->get_acting_settings();
		if( 'on' == $acting_opts['payquick'] ) {
			$member = $usces->get_member();
			$pcid = $usces->get_member_meta_value( 'remise_pcid', $member['ID'] );
			if( !empty( $pcid ) ) {
				$update_settlement_url = add_query_arg( array( 'page' => 'member_update_settlement', 're-enter' => 1 ), USCES_MEMBER_URL );
				$html .= '
				<div class="gotoedit">
				<a href="'.$update_settlement_url.'">'.__( "Change the credit card is here >>", 'usces' ).'</a>
				</div>';
			}
		}
		return $html;
	}

	/**
	 * クレジットカード登録・変更ページ
	 * @param  -
	 * @return -
	 * @echo   html
	 */
	public function member_update_settlement_form() {
		global $usces;

		$member = $usces->get_member();
		$member_id = $member['ID'];
		$update_settlement_url = add_query_arg( array( 'page' => 'member_update_settlement', 'settlement' => 1, 're-enter' => 1 ), USCES_MEMBER_URL );
		$acting_opts = $this->get_acting_settings();
		$send_url = ( 'public' == $acting_opts['card_pc_ope'] ) ? $acting_opts['send_url_pc'] : $acting_opts['send_url_pc_test'];
		$rand = '0000000'.$member_id;
		$partofcard = $usces->get_member_meta_value( 'partofcard', $member_id );
		$limitofcard = $usces->get_member_meta_value( 'limitofcard', $member_id );

		ob_start();
		get_header();
?>
<div id="content" class="two-column">
<div class="catbox">
<?php if( have_posts() ): usces_remove_filter(); ?>
<div class="post" id="wc_<?php usces_page_name(); ?>">
<h1 class="member_page_title"><?php _e( 'Credit card update', 'usces' ); ?></h1>
<div class="entry">
<div id="memberpages">
<div class="whitebox">
	<div id="memberinfo">
	<div class="header_explanation">
<?php do_action( 'usces_action_member_update_settlement_page_header' ); ?>
	</div>
	<div class="error_message"><?php usces_error_message(); ?></div>
	<div><?php echo __( 'Since the transition to the page of the settlement company by clicking the "Update", please fill out the information for the new card.<br />In addition, this process is intended to update the card information such as credit card expiration date, it is not in your contract renewal of service.<br />To check the current contract, please refer to the member page.', 'dlseller' ); ?><br /><br /></div>
<?php if( !empty( $partofcard ) && !empty( $limitofcard ) ): ?>
	<table>
		<tbody>
		<tr>
			<th scope="row"><?php _e( 'The last four digits of your card number', 'usces' ); ?></th><td><?php echo esc_html( $partofcard ); ?></div></td>
			<th scope="row"><?php _e( 'Expiration date', 'usces' ); ?></th><td><?php echo esc_html( $limitofcard ); ?></td>
		</tr>
		</tbody>
	</table>
<?php endif; ?>
	<form id="member-card-info" action="<?php echo esc_attr( $send_url ); ?>" method="post" onKeyDown="if(event.keyCode == 13){return false;}" accept-charset="Shift_JIS">
		<input type="hidden" name="SHOPCO" value="<?php echo esc_attr( $acting_opts['SHOPCO'] ); ?>" />
		<input type="hidden" name="HOSTID" value="<?php echo esc_attr( $acting_opts['HOSTID'] ); ?>" />
		<input type="hidden" name="REMARKS3" value="<?php echo $acting_opts['REMARKS3']; ?>" />
		<input type="hidden" name="S_TORIHIKI_NO" value="<?php echo $rand; ?>" />
		<input type="hidden" name="JOB" value="CHECK" />
		<input type="hidden" name="MAIL" value="<?php echo esc_attr( $member['mailaddress1'] ); ?>" />
		<input type="hidden" name="ITEM" value="0000990" />
		<input type="hidden" name="RETURL" value="<?php echo esc_attr( $update_settlement_url ); ?>" />
		<input type="hidden" name="NG_RETURL" value="<?php echo esc_attr( $update_settlement_url ); ?>" />
		<input type="hidden" name="EXITURL" value="<?php echo esc_attr( $update_settlement_url ); ?>" />
		<input type="hidden" name="OPT" value="welcart_card_update" />
		<input type="hidden" name="PAYQUICK" value="1" />
		<input type="hidden" name="dummy" value="&#65533;" />
		<div class="send">
			<input type="submit" name="purchase" class="checkout_button" value="<?php echo __( 'Update', 'dlseller' ); ?>" onclick="document.charset='Shift_JIS';" />
			<input type="button" name="back" value="<?php _e( 'Back to the member page.', 'usces' ); ?>" onclick="location.href='<?php echo USCES_MEMBER_URL; ?>'" />
			<input type="button" name="top" value="<?php _e( 'Back to the top page.', 'usces' ); ?>" onclick="location.href='<?php echo home_url(); ?>'" />
		</div>
	</form>
	<div class="footer_explanation">
<?php do_action( 'usces_action_member_update_settlement_page_footer' ); ?>
	</div>
	</div><!-- end of memberinfo -->
</div><!-- end of whitebox -->
</div><!-- end of memberpages -->
</div><!-- end of entry -->
</div><!-- end of post -->
<?php else: ?>
<p><?php _e( 'Sorry, no posts matched your criteria.', 'usces' ); ?></p>
<?php endif; ?>
</div><!-- end of catbox -->
</div><!-- end of content -->
<?php
		$sidebar = apply_filters( 'usces_filter_member_update_settlement_page_sidebar', 'cartmember' );
		if( !empty( $sidebar ) ) get_sidebar( $sidebar );

		get_footer();
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;
	}

	/**
	 * 決済オプション取得
	 * @param  -
	 * @return array $acting_settings
	 */
	protected function get_acting_settings() {
		global $usces;

		$acting_settings = ( isset( $usces->options['acting_settings']['remise'] ) ) ? $usces->options['acting_settings']['remise'] : array();
		return $acting_settings;
	}

	/**
	 * 
	 * @param  
	 * @return 
	 */
	protected function have_member_continue_order( $member_id ) {
		global $wpdb;

		$continue = 0;
		$continuation_table = $wpdb->prefix.'usces_continuation';
		$query = $wpdb->prepare( "SELECT * FROM {$continuation_table} WHERE `con_member_id` = %d AND `con_status` = 'continuation' ORDER BY `con_price` DESC", $member_id );
		$continue_order = $wpdb->get_results( $query, ARRAY_A );
		if( 0 < count( $continue_order ) ) {
			$continue = $continue_order[0]['con_order_id'];
		}
		return $continue;
	}
}
