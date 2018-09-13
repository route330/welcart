<?php
/*
e-SCOTT Smart
Version: 1.0.0
Author: Collne Inc.
*/

class ESCOTT_SETTLEMENT extends ESCOTT_MAIN
{
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	public function __construct() {

		$this->acting_name = 'e-SCOTT';
		$this->acting_formal_name = 'e-SCOTT Smart';

		$this->acting_card = 'escott_card';
		$this->acting_conv = 'escott_conv';
		$this->acting_atodene = 'escott_atodene';

		$this->acting_flg_card = 'acting_escott_card';
		$this->acting_flg_conv = 'acting_escott_conv';
		$this->acting_flg_atodene = 'acting_escott_atodene';

		$this->pay_method = array(
			'acting_escott_card',
			'acting_escott_conv',
		);
		$this->merchantfree3 = 'wc1collne';
		$this->quick_key_pre = 'escott';

		parent::__construct( 'escott' );

		$this->initialize_data();
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

	/**********************************************
	* Initialize
	***********************************************/
	public function initialize_data() {

		$options = get_option( 'usces' );
		if( !in_array( 'escott', $options['acting_settings'] ) ) {
			$options['acting_settings']['escott']['merchant_id'] = ( isset($options['acting_settings']['escott']['merchant_id']) ) ? $options['acting_settings']['escott']['merchant_id'] : '';
			$options['acting_settings']['escott']['merchant_pass'] = ( isset($options['acting_settings']['escott']['merchant_pass']) ) ? $options['acting_settings']['escott']['merchant_pass'] : '';
			$options['acting_settings']['escott']['tenant_id'] = ( isset($options['acting_settings']['escott']['tenant_id']) ) ? $options['acting_settings']['escott']['tenant_id'] : '0001';
			$options['acting_settings']['escott']['ope'] = ( isset($options['acting_settings']['escott']['ope']) ) ? $options['acting_settings']['escott']['ope'] : 'test';
			$options['acting_settings']['escott']['card_activate'] = ( isset($options['acting_settings']['escott']['card_activate']) ) ? $options['acting_settings']['escott']['card_activate'] : 'off';
			$options['acting_settings']['escott']['seccd'] = ( isset($options['acting_settings']['escott']['seccd']) ) ? $options['acting_settings']['escott']['seccd'] : 'on';
			$options['acting_settings']['escott']['token_code'] = ( isset($options['acting_settings']['escott']['token_code']) ) ? $options['acting_settings']['escott']['token_code'] : '';
			$options['acting_settings']['escott']['quickpay'] = ( isset($options['acting_settings']['escott']['quickpay']) ) ? $options['acting_settings']['escott']['quickpay'] : 'off';
			$options['acting_settings']['escott']['chooseable_quickpay'] = ( isset($options['acting_settings']['escott']['chooseable_quickpay']) ) ? $options['acting_settings']['escott']['chooseable_quickpay'] : 'on';
			$options['acting_settings']['escott']['operateid'] = ( isset($options['acting_settings']['escott']['operateid']) ) ? $options['acting_settings']['escott']['operateid'] : '1Auth';
			$options['acting_settings']['escott']['howtopay'] = ( isset($options['acting_settings']['escott']['howtopay']) ) ? $options['acting_settings']['escott']['howtopay'] : '1';
			$options['acting_settings']['escott']['conv_activate'] = ( isset($options['acting_settings']['escott']['conv_activate']) ) ? $options['acting_settings']['escott']['conv_activate'] : 'off';
			$options['acting_settings']['escott']['conv_limit'] = ( !empty($options['acting_settings']['escott']['conv_limit']) ) ? $options['acting_settings']['escott']['conv_limit'] : '7';
			$options['acting_settings']['escott']['conv_fee_type'] = ( isset($options['acting_settings']['escott']['conv_fee_type']) ) ? $options['acting_settings']['escott']['conv_fee_type'] : '';
			$options['acting_settings']['escott']['conv_fee'] = ( isset($options['acting_settings']['escott']['conv_fee']) ) ? $options['acting_settings']['escott']['conv_fee'] : '';
			$options['acting_settings']['escott']['conv_fee_limit_amount'] = ( isset($options['acting_settings']['escott']['conv_fee_limit_amount']) ) ? $options['acting_settings']['escott']['conv_fee_limit_amount'] : '';
			$options['acting_settings']['escott']['conv_fee_first_amount'] = ( isset($options['acting_settings']['escott']['conv_fee_first_amount']) ) ? $options['acting_settings']['escott']['conv_fee_first_amount'] : '';
			$options['acting_settings']['escott']['conv_fee_first_fee'] = ( isset($options['acting_settings']['escott']['conv_fee_first_fee']) ) ? $options['acting_settings']['escott']['conv_fee_first_fee'] : '';
			$options['acting_settings']['escott']['conv_fee_amounts'] = ( isset($options['acting_settings']['escott']['conv_fee_amounts']) ) ? $options['acting_settings']['escott']['conv_fee_amounts'] : array();
			$options['acting_settings']['escott']['conv_fee_fees'] = ( isset($options['acting_settings']['escott']['conv_fee_fees']) ) ? $options['acting_settings']['escott']['conv_fee_fees'] : array();
			$options['acting_settings']['escott']['conv_fee_end_fee'] = ( isset($options['acting_settings']['escott']['conv_fee_end_fee']) ) ? $options['acting_settings']['escott']['conv_fee_end_fee'] : '';
			$options['acting_settings']['escott']['activate'] = ( isset($options['acting_settings']['escott']['activate']) ) ? $options['acting_settings']['escott']['activate'] : 'off';
			update_option( 'usces', $options );
		}

		$available_settlement = get_option( 'usces_available_settlement' );
		if( !in_array( 'escott', $available_settlement ) ) {
			$available_settlement['escott'] = 'e-SCOTT Smart';
			update_option( 'usces_available_settlement', $available_settlement );
		}

		$noreceipt_status = get_option( 'usces_noreceipt_status' );
		if( !in_array( 'acting_escott_conv', $noreceipt_status ) ) {
			$noreceipt_status[] = 'acting_escott_conv';
			update_option( 'usces_noreceipt_status', $noreceipt_status );
		}

		$this->unavailable_method = get_option( 'usces_unavailable_settlement' );
	}

	/**********************************************
	* usces_filter_settle_info_field_value
	* 受注編集画面に表示する決済情報の値整形
	* @param  $value $key $acting
	* @return string $value
	***********************************************/
	public function settlement_info_field_value( $value, $key, $acting ) {

		if( 'escott_card' != $acting && 'escott_conv' != $acting ) {
			return $value;
		}

		switch( $key ) {
		case 'acting':
			switch( $value ) {
			case 'escott_card':
				$value = __('e-SCOTT - Credit card transaction','usces');
				break;
			case 'escott_conv':
				$value = __('e-SCOTT - Online storage agency','usces');
				break;
			}
			break;
		}

		$value = parent::settlement_info_field_value( $value, $key, $acting );

		return $value;
	}
}
