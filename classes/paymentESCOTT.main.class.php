<?php
/*----------------------------------------------------------------------------
e-SCOTT Smart Main Class
Version: 1.0.0
Author: Collne Inc.
------------------------------------------------------------------------------*/
class ESCOTT_MAIN
{
	protected $paymod_id;			// ex) 'escott'
	protected $acting_name;			// ex) 'e-SCOTT'
	protected $acting_formal_name;	// ex) 'e-SCOTT Smart ソニーペイメントサービス'

	protected $acting_card;			// ex) 'escott_card'
	protected $acting_conv;			// ex) 'escott_conv'
	protected $acting_atodene;		// ex) 'escott_atodene'

	protected $acting_flg_card;		// ex) 'acting_escott_card'
	protected $acting_flg_conv;		// ex) 'acting_escott_conv'
	protected $acting_flg_atodene;	// ex) 'acting_escott_atodene'

	protected $pay_method;			// ex) array( 'acting_escott_card', 'acting_escott_conv' )
	protected $unavailable_method;	// ex) array( 'acting_zeus_card', 'acting_zeus_conv' ) ※併用不可決済
	protected $merchantfree3;		// ex) 'wc1collne'
	protected $quick_key_pre;		// ex) 'escott'

	protected $error_mes;

	public function __construct( $mode ) {

		$this->paymod_id = $mode;

		if( $this->is_activate_conv() || $this->is_activate_atodene() ) {
			add_filter( 'usces_filter_noreceipt_status', array( $this, 'noreceipt_status' ) );
		}

		if( is_admin() ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'usces_action_admin_settlement_update', array( $this, 'settlement_update' ) );
			add_action( 'usces_action_settlement_tab_title', array( $this, 'settlement_tab_title' ) );
			add_action( 'usces_action_settlement_tab_body', array( $this, 'settlement_tab_body' ) );
			//add_filter( 'usces_filter_unavailable_payments', array( $this, 'unavailable_payments' ) );
		}

		if( $this->is_activate_card() || $this->is_activate_conv() ) {
			add_action( 'usces_after_cart_instant', array( $this, 'acting_transaction' ), 9 );
			add_filter( 'usces_filter_order_confirm_mail_payment', array( $this, 'order_confirm_mail_payment' ), 10, 5 );
			add_filter( 'usces_filter_is_complete_settlement', array( $this, 'is_complete_settlement' ), 10, 3 );
			add_action( 'usces_action_revival_order_data', array( $this, 'revival_orderdata' ), 10, 3 );

			if( is_admin() ) {
				add_filter( 'usces_filter_settle_info_field_keys', array( $this, 'settlement_info_field_keys' ) );
				add_filter( 'usces_filter_settle_info_field_value', array( $this, 'settlement_info_field_value' ), 10, 3 );
				add_action( 'usces_action_admin_member_info', array( $this, 'admin_member_info' ), 10, 3 );
				add_action( 'usces_action_post_update_memberdata', array( $this, 'admin_update_memberdata' ), 10, 2 );

			} else {
				add_filter( 'usces_filter_payment_detail', array( $this, 'payment_detail' ), 10, 2 );
				add_filter( 'usces_filter_payments_str', array( $this, 'payments_str' ), 10, 2 );
				add_filter( 'usces_filter_payments_arr', array( $this, 'payments_arr' ), 10, 2 );
				add_filter( 'usces_filter_confirm_inform', array( $this, 'confirm_inform' ), 10, 5 );
				add_action( 'usces_action_confirm_page_point_inform', array( $this, 'e_point_inform' ), 10, 5 );
				add_filter( 'usces_filter_confirm_point_inform', array( $this, 'point_inform' ), 10, 5 );
				if( defined('WCEX_COUPON') ) {
					add_filter( 'wccp_filter_coupon_inform', array( $this, 'point_inform' ), 10, 5 );
				}
				add_action( 'usces_action_acting_processing', array( $this, 'acting_processing' ), 10, 2 );
				add_filter( 'usces_filter_check_acting_return_results', array( $this, 'acting_return' ) );
				add_filter( 'usces_filter_check_acting_return_duplicate', array( $this, 'check_acting_return_duplicate' ), 10, 2 );
				add_action( 'usces_action_reg_orderdata', array( $this, 'register_orderdata' ) );
				add_action( 'usces_post_reg_orderdata', array( $this, 'post_register_orderdata'), 10, 2 );
				add_filter( 'usces_filter_get_error_settlement', array( $this, 'error_page_message' ) );
				add_filter( 'usces_filter_send_order_mail_payment', array( $this, 'order_mail_payment' ), 10, 6 );
			}
		}

		if( $this->is_validity_acting('card') ) {
			add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
			add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ), 9 );
			add_filter( 'usces_filter_delivery_check', array( $this, 'delivery_check' ), 15 );
			add_filter( 'usces_filter_delivery_secure_form', array( $this, 'delivery_secure_form' ), 10, 2 );
			add_filter( 'usces_filter_delivery_secure_form_loop', array( $this, 'delivery_secure_form_loop' ), 10, 2 );
			add_filter( 'usces_filter_delete_member_check', array( $this, 'delete_member_check' ), 10, 2 );
			add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'usces_filter_uscesL10n', array( $this, 'set_uscesL10n' ) );
			add_action( 'usces_front_ajax', array( $this, 'front_ajax' ) );
		}

		if( $this->is_validity_acting('conv') || $this->is_validity_acting('atodene') ) {
			add_filter( 'usces_filter_cod_label', array( $this, 'set_fee_label' ) );
			add_filter( 'usces_filter_member_history_cod_label', array( $this, 'set_member_history_fee_label' ), 10, 2 );
			if( is_admin() ) {
			} else {
				add_filter( 'usces_fiter_the_payment_method', array( $this, 'payment_method' ) );
				add_filter( 'usces_filter_set_cart_fees_cod', array( $this, 'add_fee' ), 10, 7 );
				add_filter( 'usces_filter_delivery_check', array( $this, 'check_fee_limit' ) );
				add_filter( 'usces_filter_point_check_last', array( $this, 'check_fee_limit' ) );
			}
		}
	}

	/**********************************************
	* Initialize
	***********************************************/
	public function initialize_data() {
		$this->unavailable_method = get_option( 'usces_unavailable_settlement' );
	}

	/**********************************************
	* 決済有効判定
	* 引数が指定されたとき、支払方法で使用している場合に「有効」とする
	* @param  ($type)
	* @return boolean
	***********************************************/
	public function is_validity_acting( $type = '' ) {

		$acting_opts = $this->get_acting_settings();
		if( empty($acting_opts) ) {
			return false;
		}

		$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
		$method = false;

		switch( $type ) {
		case 'card':
			foreach( $payment_method as $payment ) {
				if( $this->acting_flg_card == $payment['settlement'] && 'activate' == $payment['use'] ) {
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
				if( $this->acting_flg_conv == $payment['settlement'] && 'activate' == $payment['use'] ) {
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

		case 'atodene':
			foreach( $payment_method as $payment ) {
				if( $this->acting_flg_atodene == $payment['settlement'] && 'activate' == $payment['use'] ) {
					$method = true;
					break;
				}
			}
			if( $method && $this->is_activate_atodene() ) {
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

	/**********************************************
	* カード決済有効判定
	* @param  -
	* @return boolean $res
	***********************************************/
	public function is_activate_card() {

		$acting_opts = $this->get_acting_settings();
		if( ( isset($acting_opts['activate']) && 'on' == $acting_opts['activate'] ) && 
			( isset($acting_opts['card_activate']) && ( 'on' == $acting_opts['card_activate'] || 'link' == $acting_opts['card_activate'] || 'token' == $acting_opts['card_activate'] ) ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**********************************************
	* オンライン収納代行有効判定
	* @param  -
	* @return boolean $res
	***********************************************/
	public function is_activate_conv() {

		$acting_opts = $this->get_acting_settings();
		if( ( isset($acting_opts['activate']) && 'on' == $acting_opts['activate'] ) && 
			( isset($acting_opts['conv_activate']) && 'on' == $acting_opts['conv_activate'] ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**********************************************
	* 後払い決済有効判定
	* @param  -
	* @return boolean $res
	***********************************************/
	public function is_activate_atodene() {

		$acting_opts = $this->get_acting_settings();
		if( ( isset($acting_opts['activate']) && 'on' == $acting_opts['activate'] ) && 
			( isset($acting_opts['atodene_activate']) && 'on' == $acting_opts['atodene_activate'] ) ) {
			$res = true;
		} else {
			$res = false;
		}
		return $res;
	}

	/**********************************************
	* usces_filter_noreceipt_status
	* @param  $noreceipt_status
	* @return array $noreceipt_status
	***********************************************/
	public function noreceipt_status( $noreceipt_status ) {

		if( !in_array( 'acting_escott_conv', $noreceipt_status ) ) {
			$noreceipt_status[] = 'acting_escott_conv';
		}
		return $noreceipt_status;
	}

	/**********************************************
	* admin_print_footer_scripts
	* JavaScript
	* @param  -
	* @return -
	* @echo   js
	***********************************************/
	public function admin_scripts() {

		$admin_page = ( isset($_GET['page']) ) ? $_GET['page'] : '';
		switch( $admin_page ):
		case 'usces_settlement':
			$settlement_selected = get_option( 'usces_settlement_selected' );
			if( in_array( $this->paymod_id, (array)$settlement_selected ) ):
				$acting_opts = $this->get_acting_settings();
?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	var card_activate = "<?php echo $acting_opts['card_activate']; ?>";
	var conv_activate = "<?php echo $acting_opts['conv_activate']; ?>";

	if( "on" == card_activate || "token" == card_activate ) {
		$(".card_escott").css("display", "");
		$(".card_token_code_escott").css("display", "");
		$(".card_howtopay_escott").css("display", "");
		var quickpay_escott = "<?php echo $acting_opts['quickpay']; ?>";
		if( "on" == quickpay_escott ) {
			$(".card_chooseable_quickpay_escott").css("display", "");
		} else {
			$(".card_chooseable_quickpay_escott").css("display", "none");
		}
	} else if( "link" == card_activate ) {
		$(".card_escott").css("display", "");
		$(".card_token_code_escott").css("display", "none");
		$(".card_howtopay_escott").css("display", "none");
		var quickpay_escott = "<?php echo $acting_opts['quickpay']; ?>";
		if( "on" == quickpay_escott ) {
			$(".card_chooseable_quickpay_escott").css("display", "");
		} else {
			$(".card_chooseable_quickpay_escott").css("display", "none");
		}
	} else {
		$(".card_escott").css("display", "none");
		$(".card_token_code_escott").css("display", "none");
		$(".card_howtopay_escott").css("display", "none");
		$(".card_chooseable_quickpay_escott").css("display", "none");
	}

	if( "on" == conv_activate ) {
		$(".conv_escott").css("display", "");
	} else {
		$(".conv_escott").css("display", "none");
	}

	$(document).on( "change", ".card_activate_escott", function() {
		if( "on" == $( this ).val() || "token" == $( this ).val() ) {
			$(".card_escott").css("display", "");
			$(".card_token_code_escott").css("display", "");
			$(".card_howtopay_escott").css("display", "");
		} else if( "link" == $( this ).val() ) {
			$(".card_escott").css("display", "");
			$(".card_token_code_escott").css("display", "none");
			$(".card_howtopay_escott").css("display", "none");
		} else {
			$(".card_escott").css("display", "none");
			$(".card_token_code_escott").css("display", "none");
			$(".card_howtopay_escott").css("display", "none");
			$(".card_chooseable_quickpay_escott").css("display", "none");
		}
	});

	$(document).on( "change", ".quickpay_escott", function() {
		if( "on" == $( this ).val() ) {
			$(".card_chooseable_quickpay_escott").css("display", "");
		} else {
			$(".card_chooseable_quickpay_escott").css("display", "none");
		}
	});

	$(document).on( "change", ".conv_activate_escott", function() {
		if( "on" == $( this ).val() ) {
			$(".conv_escott").css("display", "");
		} else {
			$(".conv_escott").css("display", "none");
		}
	});

	adminSettlementEScott = {
		openFee : function( mode ) {
			$("#fee_change_field").html("");
			$("#fee_fix").val( $("#"+mode+"_fee").val() );
			$("#fee_limit_amount_fix").val( $("#"+mode+"_fee_limit_amount_fix").val() );
			$("#fee_first_amount").val( $("#"+mode+"_fee_first_amount").val() );
			$("#fee_first_fee").val( $("#"+mode+"_fee_first_fee").val() );
			$("#fee_limit_amount_change").val( $("#"+mode+"_fee_limit_amount_change").val() );
			var fee_amounts = new Array();
			var fee_fees = new Array();
			if( 0 < $("#"+mode+"_fee_amounts").val().length ) {
				fee_amounts = $("#"+mode+"_fee_amounts").val().split("|");
			}
			if( 0 < $("#"+mode+"_fee_fees").val().length ) {
				fee_fees = $("#"+mode+"_fee_fees").val().split("|");
			}
			if( 0 < fee_amounts.length ) {
				var amount = parseInt($("#fee_first_amount").val()) + 1;
				for( var i = 0; i < fee_amounts.length; i++ ) {
					html = '<tr id="row_'+i+'"><td class="cod_f"><span id="amount_'+i+'">'+amount+'</span></td><td class="cod_m"><?php _e(' - ','usces'); ?></td><td class="cod_e"><input name="fee_amounts['+i+']" type="text" class="short_str num" value="'+fee_amounts[i]+'" /></td><td class="cod_cod"><input name="fee_fees['+i+']" type="text" class="short_str num" value="'+fee_fees[i]+'" /></td></tr>';
					$("#fee_change_field").append(html);
					amount = parseInt(fee_amounts[i]) + 1;
				}
				$("#end_amount").html( amount );
			} else {
				$("#end_amount").html( parseInt($("#"+mode+"_fee_first_amount").val()) + 1 );
			}
			$("#fee_end_fee").val( $("#"+mode+"_fee_end_fee").val() );

			var fee_type = $("#"+mode+"_fee_type").val();
			if( "change" == fee_type ) {
				$("#fee_type_change").prop("checked", true);
				$("#escott_fee_fix_table").css("display","none");
				$("#escott_fee_change_table").css("display","");
			} else {
				$("#fee_type_fix").prop("checked", true);
				$("#escott_fee_fix_table").css("display","");
				$("#escott_fee_change_table").css("display","none");
			}
		},

		updateFee : function( mode ) {
			var fee_type = $("input[name='fee_type']:checked").val();
			$("#"+mode+"_fee_type").val( fee_type );
			$("#"+mode+"_fee").val( $("#fee_fix").val() );
			$("#"+mode+"_fee_limit_amount_"+fee_type).val( $("#fee_limit_amount_"+fee_type).val() );
			$("#"+mode+"_fee_first_amount").val( $("#fee_first_amount").val() );
			$("#"+mode+"_fee_first_fee").val( $("#fee_first_fee").val() );
			var fee_amounts = "";
			var fee_fees = "";
			var sp = "";
			var fee_amounts_length = $("input[name^='fee_amounts']").length;
			for( var i = 0; i < fee_amounts_length; i++ ) {
				fee_amounts += sp + $("input[name='fee_amounts\["+i+"\]']").val();
				fee_fees += sp + $("input[name='fee_fees\["+i+"\]']").val();
				sp = "|";
			}
			$("#"+mode+"_fee_amounts").val( fee_amounts );
			$("#"+mode+"_fee_fees").val( fee_fees );
			$("#"+mode+"_fee_end_fee").val( $("#fee_end_fee").val() );
		},

		setFeeType : function( mode, closed ) {
			var fee_type = $("input[name='fee_type']:checked").val();
			if( "change" == fee_type ) {
				$("#"+mode+"_fee_type_field").html("<?php _e('Variable','usces'); ?>");
				if( !closed ) {
					$("#escott_fee_fix_table").css("display","none");
					$("#escott_fee_change_table").css("display","");
				}
			} else if( "fix" == fee_type ) {
				$("#"+mode+"_fee_type_field").html("<?php _e('Fixation','usces'); ?>");
				if( !closed ) {
					$("#escott_fee_fix_table").css("display","");
					$("#escott_fee_change_table").css("display","none");
				}
			}
		}
	};

	$("#escott_fee_dialog").dialog({
		autoOpen: false,
		height: 500,
		width: 450,
		modal: true,
		open: function() {
			adminSettlementEScott.openFee( $("#escott_fee_mode").val() );
		},
		buttons: {
			"<?php _e('Settings'); ?>": function() {
				adminSettlementEScott.updateFee( $("#escott_fee_mode").val() );
			},
			"<?php _e('Close'); ?>": function() {
				$(this).dialog('close');
			}
		},
		close: function() {
			adminSettlementEScott.setFeeType( $("#escott_fee_mode").val(), true );
		}
	});

	$(document).on("click", "#conv_fee_setting", function() {
		$("#escott_fee_mode").val( "conv" );
		$("#escott_fee_dialog").dialog( "option", "title", "<?php _e('Online storage agency settlement fee setting','usces'); ?>" );
		$("#escott_fee_dialog").dialog( "open" );
	});

	$(document).on("click", ".fee_type", function() {
		if( "change" == $(this).val() ) {
			$("#escott_fee_fix_table").css("display","none");
			$("#escott_fee_change_table").css("display","");
		} else {
			$("#escott_fee_fix_table").css("display","");
			$("#escott_fee_change_table").css("display","none");
		}
	});

	$(document).on("change", "input[name='fee_first_amount']", function() {
		var rows = $("input[name^='fee_amounts']");
		var first_amount = $("input[name='fee_first_amount']");
		if( 0 == rows.length && $(first_amount).val() != '' ) {
			$("#end_amount").html( parseInt($(first_amount).val()) + 1 );
		} else if( 0 < rows.length && $(first_amount).val() != '' ) {
			$('#amount_0').html( parseInt($(first_amount).val()) + 1 );
		}
	});

	$(document).on("change", "#fee_limit_amount_change", function() {
		if( "change" == $("input[name='fee_type']:checked").val() ) {
			var amount = parseInt($("#end_amount").html());
			var limit = parseInt($("#fee_limit_amount_change").val());
			if( amount >= limit ) {
				alert("<?php _e('A value of the amount of upper limit is dirty.', 'usces'); ?>"+amount+' : '+limit );
			}
		}
	});

	$(document).on("change", "input[name^='fee_amounts']", function() {
		var rows = $("input[name^='fee_amounts']");
		var cnt = $(rows).length;
		var end_amount = $("#end_amount");
		var id = $(rows).index(this);
		if( id >= cnt - 1 ) {
			$(end_amount).html( parseInt($(rows).eq(id).val()) + 1 );
		} else if( id < cnt - 1 ) {
			$("#amount_"+(id+1)).html( parseInt($(rows).eq(id).val()) + 1 );
		}
	});

	$(document).on("click", "#fee_add_row", function() {
		var rows = $("input[name^='fee_amounts']");
		$(rows).unbind("change");
		var first_amount = $("input[name='fee_first_amount']");
		var first_fee = $("input[name='fee_first_fee']");
		var end_amount = $("#end_amount");
		var enf_fee = $("input[name='fee_end_fee']");
		if( 0 == rows.length ) {
			amount = ( $(first_amount).val() == '' ) ? '' : parseInt( $(first_amount).val() ) + 1;
		} else if( 0 < rows.length ) {
			amount = ( $(rows).eq(rows.length - 1).val() == '' ) ? '' : parseInt( $(rows).eq(rows.length-1).val() ) + 1;
		}
		html = '<tr id="row_'+rows.length+'"><td class="cod_f"><span id="amount_'+rows.length+'">'+amount+'</span></td><td class="cod_m"><?php _e(' - ','usces'); ?></td><td class="cod_e"><input name="fee_amounts['+rows.length+']" type="text" class="short_str num" /></td><td class="cod_cod"><input name="fee_fees['+rows.length+']" type="text" class="short_str num" /></td></tr>';
		$("#fee_change_field").append(html);
		rows = $("input[name^='fee_amounts']");
		$(rows).bind("change", function() {
			var cnt = $(rows).length - 1;
			var id = $(rows).index(this);
			if( id >= cnt ) {
				$(end_amount).html( parseInt($(rows).eq(id).val()) + 1 );
			} else if( id < cnt ) {
				$("#amount_"+(id+1)).html( parseInt($(rows).eq(id).val()) + 1 );
			}
		});
	});

	$(document).on("click", "#fee_del_row", function() {
		var rows = $("input[name^='fee_amounts']");
		var first_amount = $("input[name='fee_first_amount']");
		var end_amount = $("#end_amount");
		var del_id = rows.length - 1;
		if( 0 < rows.length ) {
			$("#row_"+del_id).remove();
		}
		rows = $("input[name^='fee_amounts']");
		if( 0 == rows.length && $(first_amount).val() != "" ) {
			$(end_amount).html( parseInt($(first_amount).val()) + 1 );
		} else if( 0 < rows.length && $(rows).eq(rows.length-1).val() != "" ) {
			$(end_amount).html( parseInt($(rows).eq(rows.length-1).val()) + 1 );
		}
	});

	adminSettlementEScott.setFeeType( "conv", false );
});
</script>
<?php
			endif;
			break;
		endswitch;
	}

	/**********************************************
	* usces_action_admin_settlement_update
	* 決済オプション登録・更新
	* @param  -
	* @return -
	***********************************************/
	public function settlement_update() {
		global $usces;

		if( $this->paymod_id != $_POST['acting'] ) {
			return;
		}

		$this->error_mes = '';
		$options = get_option( 'usces' );
		$payment_method = usces_get_system_option( 'usces_payment_method', 'settlement' );

		unset( $options['acting_settings'][$this->paymod_id] );
		$options['acting_settings'][$this->paymod_id]['merchant_id'] = ( isset($_POST['merchant_id']) ) ? $_POST['merchant_id'] : '';
		$options['acting_settings'][$this->paymod_id]['merchant_pass'] = ( isset($_POST['merchant_pass']) ) ? $_POST['merchant_pass'] : '';
		$options['acting_settings'][$this->paymod_id]['tenant_id'] = ( isset($_POST['tenant_id']) ) ? $_POST['tenant_id'] : '';
		$options['acting_settings'][$this->paymod_id]['ope'] = ( isset($_POST['ope']) ) ? $_POST['ope'] : '';
		$options['acting_settings'][$this->paymod_id]['card_activate'] = ( isset($_POST['card_activate']) ) ? $_POST['card_activate'] : '';
		$options['acting_settings'][$this->paymod_id]['seccd'] = ( isset($_POST['seccd']) ) ? $_POST['seccd'] : 'on';
		$options['acting_settings'][$this->paymod_id]['token_code'] = ( isset($_POST['token_code']) ) ? $_POST['token_code'] : '';
		$options['acting_settings'][$this->paymod_id]['quickpay'] = ( isset($_POST['quickpay']) ) ? $_POST['quickpay'] : '';
		$options['acting_settings'][$this->paymod_id]['chooseable_quickpay'] = ( isset($_POST['chooseable_quickpay']) ) ? $_POST['chooseable_quickpay'] : 'on';
		$options['acting_settings'][$this->paymod_id]['operateid'] = ( isset($_POST['operateid']) ) ? $_POST['operateid'] : '';
		$options['acting_settings'][$this->paymod_id]['howtopay'] = ( isset($_POST['howtopay']) ) ? $_POST['howtopay'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_activate'] = ( isset($_POST['conv_activate']) ) ? $_POST['conv_activate'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_limit'] = ( !empty($_POST['conv_limit']) ) ? $_POST['conv_limit'] : '7';
		$options['acting_settings'][$this->paymod_id]['conv_fee_type'] = ( isset($_POST['conv_fee_type']) ) ? $_POST['conv_fee_type'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_fee'] = ( isset($_POST['conv_fee']) ) ? $_POST['conv_fee'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_fee_limit_amount'] = ( isset($_POST['conv_fee_limit_amount_'.$options['acting_settings'][$this->paymod_id]['conv_fee_type']]) ) ? $_POST['conv_fee_limit_amount_'.$options['acting_settings'][$this->paymod_id]['conv_fee_type']] : '';
		$options['acting_settings'][$this->paymod_id]['conv_fee_first_amount'] = ( isset($_POST['conv_fee_first_amount']) ) ? $_POST['conv_fee_first_amount'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_fee_first_fee'] = ( isset($_POST['conv_fee_first_fee']) ) ? $_POST['conv_fee_first_fee'] : '';
		$options['acting_settings'][$this->paymod_id]['conv_fee_amounts'] = ( isset($_POST['conv_fee_amounts']) ) ? explode( '|', $_POST['conv_fee_amounts'] ) : array();
		$options['acting_settings'][$this->paymod_id]['conv_fee_fees'] = ( isset($_POST['conv_fee_fees']) ) ? explode( '|', $_POST['conv_fee_fees'] ) : array();
		$options['acting_settings'][$this->paymod_id]['conv_fee_end_fee'] = ( isset($_POST['conv_fee_end_fee']) ) ? $_POST['conv_fee_end_fee'] : '';

		if( 'on' == $options['acting_settings'][$this->paymod_id]['card_activate'] || 'on' == $options['acting_settings'][$this->paymod_id]['conv_activate'] ) {
			$unavailable_activate = false;
			foreach( $payment_method as $key => $payment ) {
				foreach( (array)$this->unavailable_method as $unavailable ) {
					if( $unavailable == $key && 'activate' == $payment['use'] ) {
						$unavailable_activate = true;
						break;
					}
				}
			}
			if( $unavailable_activate ) {
				$this->error_mes .= __('* Settlement that can not be used together is activated.','usces').'<br />';
			} else {
				if( WCUtils::is_blank($_POST['merchant_id']) ) {
					$this->error_mes .= __('* Please enter the Merchant ID.','usces').'<br />';
				}
				if( WCUtils::is_blank($_POST['merchant_pass']) ) {
					$this->error_mes .= __('* Please enter the Merchant Password.','usces').'<br />';
				}
				if( WCUtils::is_blank($_POST['tenant_id']) ) {
					$this->error_mes .= __('* Please enter the Tenant ID.','usces').'<br />';
				}
				if( WCUtils::is_blank($_POST['ope']) ) {
					$this->error_mes .= __('* Please select the operating environment.','usces').'<br />';
				}
				if( WCUtils::is_blank( $_POST['operateid'] ) ) {
					$this->error_mes .= __( '* Please select the processing classification.', 'usces' ).'<br />';
				}
				if( 'on' == $options['acting_settings'][$this->paymod_id]['card_activate'] ) {
					if( WCUtils::is_blank($_POST['token_code']) ) {
						$this->error_mes .= __('* Please enter the Token auth code.','usces').'<br />';
					}
				}
			}
		}

		if( WCUtils::is_blank($this->error_mes) ) {
			$usces->action_status = 'success';
			$usces->action_message = __('options are updated','usces');
			$options['acting_settings'][$this->paymod_id]['activate'] = 'on';
			if( 'public' == $options['acting_settings'][$this->paymod_id]['ope'] ) {
				$options['acting_settings'][$this->paymod_id]['send_url'] = 'https://www.e-scott.jp/online/aut/OAUT002.do';
				$options['acting_settings'][$this->paymod_id]['send_url_member'] = 'https://www.e-scott.jp/online/crp/OCRP005.do';
				$options['acting_settings'][$this->paymod_id]['send_url_conv'] = 'https://www.e-scott.jp/online/cnv/OCNV005.do';
				$options['acting_settings'][$this->paymod_id]['redirect_url_conv'] = 'https://link.kessai.info/JLP/JLPcon';
				$options['acting_settings'][$this->paymod_id]['api_token'] = 'https://www.e-scott.jp/euser/stn/CdGetJavaScript.do';
				$options['acting_settings'][$this->paymod_id]['send_url_token'] = 'https://www.e-scott.jp/online/atn/OATN005.do';
			} else {
				$options['acting_settings'][$this->paymod_id]['send_url'] = 'https://www.test.e-scott.jp/online/aut/OAUT002.do';
				$options['acting_settings'][$this->paymod_id]['send_url_member'] = 'https://www.test.e-scott.jp/online/crp/OCRP005.do';
				$options['acting_settings'][$this->paymod_id]['send_url_conv'] = 'https://www.test.e-scott.jp/online/cnv/OCNV005.do';
				$options['acting_settings'][$this->paymod_id]['redirect_url_conv'] = 'https://link.kessai.info/JLPCT/JLPcon';
				$options['acting_settings'][$this->paymod_id]['api_token'] = 'https://www.test.e-scott.jp/euser/stn/CdGetJavaScript.do';
				$options['acting_settings'][$this->paymod_id]['send_url_token'] = 'https://www.test.e-scott.jp/online/atn/OATN005.do';
				$options['acting_settings'][$this->paymod_id]['tenant_id'] = '0001';
			}
			if( 'on' == $options['acting_settings'][$this->paymod_id]['card_activate'] ) {
				if( !empty($options['acting_settings'][$this->paymod_id]['token_code']) ) {
					$options['acting_settings'][$this->paymod_id]['card_activate'] = 'token';
				}
			}
			if( 'on' == $options['acting_settings'][$this->paymod_id]['card_activate'] || 'link' == $options['acting_settings'][$this->paymod_id]['card_activate'] || 'token' == $options['acting_settings'][$this->paymod_id]['card_activate'] ) {
				$usces->payment_structure[$this->acting_flg_card] = __('Credit card transaction (e-SCOTT)','usces');
			} else {
				unset($usces->payment_structure[$this->acting_flg_card]);
			}
			if( 'on' == $options['acting_settings'][$this->paymod_id]['conv_activate'] ) {
				$usces->payment_structure[$this->acting_flg_conv] = __('Online storage agency (e-SCOTT)','usces');
			} else {
				unset($usces->payment_structure[$this->acting_flg_conv]);
			}
		} else {
			$usces->action_status = 'error';
			$usces->action_message = __('Data have deficiency.','usces');
			$options['acting_settings'][$this->paymod_id]['activate'] = 'off';
			unset( $usces->payment_structure[$this->acting_flg_card] );
			unset( $usces->payment_structure[$this->acting_flg_conv] );
		}
		ksort( $usces->payment_structure );
		update_option( 'usces', $options );
		update_option( 'usces_payment_structure', $usces->payment_structure );
	}

	/**********************************************
	* usces_action_settlement_tab_title
	* クレジット決済設定画面タブ
	* @param  -
	* @return -
	* @echo   html
	***********************************************/
	public function settlement_tab_title() {

		$settlement_selected = get_option( 'usces_settlement_selected' );
		if( in_array( $this->paymod_id, (array)$settlement_selected ) ) {
			echo '<li><a href="#uscestabs_'.$this->paymod_id.'">'.__($this->acting_name,'usces').'</a></li>';
		}
	}

	/**********************************************
	* usces_action_settlement_tab_body
	* クレジット決済設定画面フォーム
	* @param  -
	* @return -
	* @echo   html
	***********************************************/
	public function settlement_tab_body() {

		$acting_opts = $this->get_acting_settings();
		$settlement_selected = get_option( 'usces_settlement_selected' );
		if( in_array( $this->paymod_id, (array)$settlement_selected ) ):
?>
	<div id="uscestabs_escott">
	<div class="settlement_service"><span class="service_title"><?php _e($this->acting_formal_name,'usces'); ?></span></div>

	<?php if( isset($_POST['acting']) && $this->paymod_id == $_POST['acting'] ): ?>
		<?php if( '' != $this->error_mes ): ?>
		<div class="error_message"><?php echo $this->error_mes; ?></div>
		<?php elseif( isset($acting_opts['activate']) && 'on' == $acting_opts['activate'] ): ?>
		<div class="message"><?php _e('Test thoroughly before use.','usces'); ?></div>
		<?php endif; ?>
	<?php endif; ?>
	<form action="" method="post" name="escott_form" id="escott_form">
		<table class="settle_table">
			<tr>
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_merchant_id_escott');"><?php _e('Merchant ID','usces');//マーチャントID ?></a></th>
				<td colspan="4"><input name="merchant_id" type="text" id="merchant_id_escott" value="<?php echo $acting_opts['merchant_id']; ?>" size="20" /></td>
				<td><div id="ex_merchant_id_escott" class="explanation"><?php _e('Merchant ID (single-byte numbers only) issued from e-SCOTT.','usces'); ?></div></td>
			</tr>
			<tr>
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_merchant_pass_escott');"><?php _e('Merchant Password','usces');//マーチャントパスワード ?></a></th>
				<td colspan="4"><input name="merchant_pass" type="text" id="merchant_pass_escott" value="<?php echo $acting_opts['merchant_pass']; ?>" size="20" /></td>
				<td><div id="ex_merchant_pass_escott" class="explanation"><?php _e('Merchant Password (single-byte alphanumeric characters only) issued from e-SCOTT.','usces'); ?></div></td>
			</tr>
			<tr>
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_tenant_id_escott');"><?php _e('Tenant ID','usces');//店舗コード ?></a></th>
				<td colspan="4"><input name="tenant_id" type="text" id="tenant_id_escott" value="<?php echo $acting_opts['tenant_id']; ?>" size="20" /></td>
				<td><div id="ex_tenant_id_escott" class="explanation"><?php _e('Tenant ID issued from e-SCOTT.<br />If you have only one shop to contract, enter 0001.','usces'); ?></div></td>
			</tr>
			<tr>
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_ope_escott');"><?php _e('Operation Environment','usces');//動作環境 ?></a></th>
				<td><input name="ope" type="radio" id="ope_escott_1" value="test"<?php if( $acting_opts['ope'] == 'test' ) echo ' checked="checked"'; ?> /></td><td><label for="ope_escott_1"><?php _e('Testing environment','usces'); ?></label></td>
				<td><input name="ope" type="radio" id="ope_escott_2" value="public"<?php if( $acting_opts['ope'] == 'public' ) echo ' checked="checked"'; ?> /></td><td><label for="ope_escott_2"><?php _e('Production environment','usces'); ?></label></td>
				<td><div id="ex_ope_escott" class="explanation"><?php _e('Switch the operating environment.','usces'); ?></div></td>
			</tr>
		</table>
		<table class="settle_table">
			<tr>
				<th><?php _e('Credit card settlement','usces');//クレジットカード決済 ?></th>
				<td><input name="card_activate" type="radio" class="card_activate_escott" id="card_activate_escott_1" value="on"<?php if( $acting_opts['card_activate'] == 'on' || $acting_opts['card_activate'] == 'token' ) echo ' checked="checked"'; ?> /></td><td><label for="card_activate_escott_1"><?php _e('Use with non-passage type','usces'); ?></label></td>
				<td><input name="card_activate" type="radio" class="card_activate_escott" id="card_activate_escott_0" value="off"<?php if( $acting_opts['card_activate'] == 'off' ) echo ' checked="checked"'; ?> /></td><td><label for="card_activate_escott_0"><?php _e('Do not Use','usces'); ?></label></td>
				<td></td><td></td><td></td>
			</tr>
			<tr class="card_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_seccd_escott');"><?php _e('Security code <br /> (authentication assist)','usces');//セキュリティコード ?></a></th>
				<td><input name="seccd" type="radio" id="seccd_escott_1" value="on"<?php if( $acting_opts['seccd'] == 'on' ) echo ' checked="checked"'; ?> /></td><td><label for="seccd_escott_1"><?php _e('Use','usces'); ?></label></td>
				<td><input name="seccd" type="radio" id="seccd_escott_0" value="off"<?php if( $acting_opts['seccd'] == 'off' ) echo ' checked="checked"'; ?> /></td><td><label for="seccd_escott_0"><?php _e('Do not Use','usces'); ?></label></td>
				<td></td><td></td>
				<td><div id="ex_seccd_escott" class="explanation"><?php _e("Use 'Security code' of authentication assist matching. If you decide not to use, please also set 'Do not verify matching' on the e-SCOTT management screen.",'usces'); ?></div></td>
			</tr>
			<tr class="card_token_code_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_token_code_escott');"><?php _e('Token auth code','usces');//トークン決済認証コード ?></a></th>
				<td colspan="6"><input name="token_code" type="text" id="token_code_escott" value="<?php echo $acting_opts['token_code']; ?>" size="36" maxlength="32" /></td>
				<td><div id="ex_token_code_escott" class="explanation"><?php _e("Token auth code (single-byte alphanumeric characters only) issued from e-SCOTT.",'usces'); ?></div></td>
			</tr>
			<tr class="card_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_quickpay_escott');"><?php _e('Quick payment','usces');//クイック決済 ?></a></th>
				<td><input name="quickpay" type="radio" class="quickpay_escott" id="quickpay_escott_1" value="on"<?php if( $acting_opts['quickpay'] == 'on' ) echo ' checked="checked"'; ?> /></td><td><label for="quickpay_escott_1"><?php _e('Use','usces'); ?></label></td>
				<td><input name="quickpay" type="radio" class="quickpay_escott" id="quickpay_escott_0" value="off"<?php if( $acting_opts['quickpay'] == 'off' ) echo ' checked="checked"'; ?> /></td><td><label for="quickpay_escott_0"><?php _e('Do not Use','usces'); ?></label></td>
				<td></td><td></td>
				<td colspan="2"><div id="ex_quickpay_escott" class="explanation"><?php _e("Members can pay with saved card. Card number will be registered in e-SCOTT Smart system.",'usces'); ?></div></td>
			</tr>
			<tr class="card_chooseable_quickpay_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_chooseable_quickpay_escott');"><?php _e('Register credit card','usces');//クレジットカードの登録 ?></a></th>
				<td><input name="chooseable_quickpay" type="radio" class="chooseable_quickpay_escott" id="chooseable_quickpay_escott_1" value="on"<?php if( $acting_opts['chooseable_quickpay'] == 'on' ) echo ' checked="checked"'; ?> /></td><td><label for="chooseable_quickpay_escott_1"><?php _e('Member chooses','usces'); ?></label></td>
				<td><input name="chooseable_quickpay" type="radio" class="chooseable_quickpay_escott" id="chooseable_quickpay_escott_0" value="off"<?php if( $acting_opts['chooseable_quickpay'] == 'off' ) echo ' checked="checked"'; ?> /></td><td><label for="chooseable_quickpay_escott_0"><?php _e('Always register','usces'); ?></label></td>
				<td></td><td></td>
				<td colspan="2"><div id="ex_chooseable_quickpay_escott" class="explanation"><?php _e("In case of 'Always register', 'Register and purchase credit card' will not be displayed when purchasing with credit card.",'usces'); ?></div></td>
			</tr>
			<tr class="card_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_operateid_escott');"><?php _e('Processing classification','usces');//処理区分 ?></a></th>
				<td><input name="operateid" type="radio" id="operateid_escott_1" value="1Auth"<?php if( $acting_opts['operateid'] == '1Auth' ) echo ' checked="checked"'; ?> /></td><td><label for="operateid_escott_1"><?php _e('Credit','usces');//与信 ?></label></td>
				<td><input name="operateid" type="radio" id="operateid_escott_2" value="1Gathering"<?php if( $acting_opts['operateid'] == '1Gathering' ) echo ' checked="checked"'; ?> /></td><td><label for="operateid_escott_2"><?php _e('Credit sales','usces');//与信売上計上 ?></label></td>
				<td></td><td></td>
				<td colspan="2"><div id="ex_operateid_escott" class="explanation"><?php _e("In case of 'Credit' setting, it need to change to 'Sales recorded' manually in later. In case of 'Credit sales recorded' setting, sales will be recorded at the time of purchase.",'usces'); ?></div></td>
			</tr>
			<tr class="card_howtopay_escott">
				<th><?php _e('Number of payments','usces');//支払い回数 ?></th>
				<td><input name="howtopay" type="radio" id="howtopay_escott_1" value="1"<?php if( $acting_opts['howtopay'] == '1' ) echo ' checked="checked"'; ?> /></td><td><label for="howtopay_escott_1"><?php _e('Lump-sum payment only','usces');//一括払いのみ ?></label></td>
				<td><input name="howtopay" type="radio" id="howtopay_escott_2" value="2"<?php if( $acting_opts['howtopay'] == '2' ) echo ' checked="checked"'; ?> /></td><td><label for="howtopay_escott_2"><?php _e('Activate installment payment','usces');//分割払いを有効にする ?></label></td>
				<td><input name="howtopay" type="radio" id="howtopay_escott_3" value="3"<?php if( $acting_opts['howtopay'] == '3' ) echo ' checked="checked"'; ?> /></td><td><label for="howtopay_escott_3"><?php _e('Activate installment payments and bonus payments','usces');//分割払いとボーナス払いを有効にする ?></label></td>
				<td></td>
			</tr>
		</table>
		<table class="settle_table">
			<tr>
				<th><?php _e('Online storage agency','usces');//オンライン収納代行 ?></th>
				<td><input name="conv_activate" type="radio" class="conv_activate_escott" id="conv_activate_escott_1" value="on"<?php if( $acting_opts['conv_activate'] == 'on' ) echo ' checked="checked"'; ?> /></td><td><label for="conv_activate_escott_1"><?php _e('Use','usces'); ?></label></td>
				<td><input name="conv_activate" type="radio" class="conv_activate_escott" id="conv_activate_escott_0" value="off"<?php if( $acting_opts['conv_activate'] == 'off' ) echo ' checked="checked"'; ?> /></td><td><label for="conv_activate_escott_0"><?php _e('Do not Use','usces'); ?></label></td>
				<td></td>
			</tr>
			<tr class="conv_escott">
				<th><?php _e('Payment due days','usces');//支払期限日数 ?></th>
				<td colspan="4"><input name="conv_limit" type="text" id="conv_limit" value="<?php echo $acting_opts['conv_limit']; ?>" size="5" /><?php _e('days','usces'); ?></td>
				<td></td>
			</tr>
			<tr class="conv_escott">
				<th><a style="cursor:pointer;" onclick="toggleVisibility('ex_conv_fee_escott');"><?php _e('Fee','usces');//手数料 ?></a></th>
				<td colspan="2" id="conv_fee_type_field"><?php echo $this->get_fee_name( $acting_opts['conv_fee_type'] ); ?></td><td colspan="2"><input type="button" class="button" value="<?php _e('Detailed setting','usces'); ?>" id="conv_fee_setting" /></td>
				<td><div id="ex_conv_fee_escott" class="explanation"><?php _e('Set the online storage agency commission and settlement upper limit. Leave it blank if you do not need it.','usces'); ?></div></td>
			</tr>
		</table>
		<input type="hidden" name="acting" value="escott" />
		<input type="hidden" name="conv_fee_type" id="conv_fee_type" value="<?php echo $acting_opts['conv_fee_type']; ?>" />
		<input type="hidden" name="conv_fee" id="conv_fee" value="<?php echo $acting_opts['conv_fee']; ?>" />
		<input type="hidden" name="conv_fee_limit_amount_fix" id="conv_fee_limit_amount_fix" value="<?php echo $acting_opts['conv_fee_limit_amount']; ?>" />
		<input type="hidden" name="conv_fee_first_amount" id="conv_fee_first_amount" value="<?php echo $acting_opts['conv_fee_first_amount']; ?>" />
		<input type="hidden" name="conv_fee_first_fee" id="conv_fee_first_fee" value="<?php echo $acting_opts['conv_fee_first_fee']; ?>" />
		<input type="hidden" name="conv_fee_limit_amount_change" id="conv_fee_limit_amount_change" value="<?php echo $acting_opts['conv_fee_limit_amount']; ?>" />
		<input type="hidden" name="conv_fee_amounts" id="conv_fee_amounts" value="<?php echo implode('|', $acting_opts['conv_fee_amounts']); ?>" />
		<input type="hidden" name="conv_fee_fees" id="conv_fee_fees" value="<?php echo implode('|', $acting_opts['conv_fee_fees']); ?>" />
		<input type="hidden" name="conv_fee_end_fee" id="conv_fee_end_fee" value="<?php echo $acting_opts['conv_fee_end_fee']; ?>" />
		<input name="usces_option_update" type="submit" class="button button-primary" value="<?php _e('Update e-SCOTT settings','usces'); ?>" />
		<?php wp_nonce_field( 'admin_settlement', 'wc_nonce' ); ?>
	</form>
	<div class="settle_exp">
		<p><strong><?php _e($this->acting_formal_name,'usces'); ?></strong></p>
		<a href="http://www.sonypaymentservices.jp/intro/" target="_blank"><?php _e('Details of e-SCOTT Smart is here >>','usces'); ?></a>
		<p>&nbsp;</p>
		<p><?php echo __("This settlement is an 'Embedded type' settlement system.",'usces'); ?><br />
			<?php echo __("'Embedded type' is a settlement system that completes with shop site only, without transitioning to the page of the settlement company.",'usces'); ?><br />
			<?php echo __("Stylish with unified design is possible. However, because we will handle the card number, dedicated SSL is required.",'usces'); ?><br />
			<?php echo __("When there is a setting of 'Token auth code', it becomes settlement of the 'Token settlement type'.",'usces'); ?></p>
		<p><?php echo __("In both types, the entered card number will be sent to the e-SCOTT Smart system, so it will not be saved in Welcart.",'usces'); ?></p>
		<p><?php echo __("In addition, in the production environment, it is SSL communication with only an authorized SSL certificate, so it is necessary to be careful.",'usces'); ?></p>
		<p><?php echo __("The Welcart member account used in the test environment may not be available in the production environment.",'usces'); ?><br />
			<?php echo __("Please make another member registration in the test environment and production environment, or delete the member used in the test environment once and register again in the production environment.",'usces'); ?></p>
	</div>
	</div><!--uscestabs_escott-->

	<div id="escott_fee_dialog" class="cod_dialog">
		<fieldset>
		<table id="escott_fee_type_table" class="cod_type_table">
			<tr>
				<th><?php _e('Type of the fee','usces'); ?></th>
				<td class="radio"><input name="fee_type" type="radio" id="fee_type_fix" class="fee_type" value="fix" /></td><td><label for="fee_type_fix"><?php _e('Fixation','usces'); ?></label></td>
				<td class="radio"><input name="fee_type" type="radio" id="fee_type_change" class="fee_type" value="change" /></td><td><label for="fee_type_change"><?php _e('Variable','usces'); ?></label></td>
			</tr>
		</table>
		<table id="escott_fee_fix_table" class="cod_fix_table">
			<tr>
				<th><?php _e('Fee','usces'); ?></th>
				<td><input name="fee" type="text" id="fee_fix" class="short_str num" /><?php usces_crcode(); ?></td>
			</tr>
			<tr>
				<th><?php _e('Upper limit','usces'); ?></th>
				<td><input name="fee_limit_amount_fix" type="text" id="fee_limit_amount_fix" class="short_str num" /><?php usces_crcode(); ?></td>
			</tr>
		</table>
		<div id="escott_fee_change_table" class="cod_change_table">
		<input type="button" class="button" id="fee_add_row" value="<?php _e('Add row','usces'); ?>" />
		<input type="button" class="button" id="fee_del_row" value="<?php _e('Delete row','usces'); ?>" />
		<table>
			<thead>
				<tr>
					<th colspan="3"><?php _e('A purchase amount','usces'); ?>(<?php usces_crcode(); ?>)</th>
					<th><?php _e('Fee','usces'); ?>(<?php usces_crcode(); ?>)</th>
				</tr>
				<tr>
					<td class="cod_f">0</td>
					<td class="cod_m"><?php _e(' - ','usces'); ?></td>
					<td class="cod_e"><input name="fee_first_amount" id="fee_first_amount" type="text" class="short_str num" /></td>
					<td class="cod_cod"><input name="fee_first_fee" id="fee_first_fee" type="text" class="short_str num" /></td>
				</tr>
			</thead>
			<tbody id="fee_change_field"></tbody>
			<tfoot>
				<tr>
					<td class="cod_f"><span id="end_amount"></span></td>
					<td class="cod_m"><?php _e(' - ','usces'); ?></td>
					<td class="cod_e"><input name="fee_limit_amount_change" type="text" id="fee_limit_amount_change" class="short_str num" /></td>
					<td class="cod_cod"><input name="fee_end_fee" type="text" id="fee_end_fee" class="short_str num" /></td>
				</tr>
			</tfoot>
		</table>
		</div>
		</fieldset>
		<input type="hidden" id="escott_fee_mode">
	</div><!--escott_fee_dialog-->
<?php
		endif;
	}

	/**********************************************
	* usces_filter_unavailable_payments
	* 併用不可決済モジュール
	* @param  -
	* @return -
	***********************************************/
	public function unavailable_payments() {
		return $this->unavailable_method;
	}

	/**********************************************
	* usces_after_cart_instant
	* 入金通知処理
	* @param  -
	* @return -
	***********************************************/
	public function acting_transaction() {
		global $wpdb, $usces;

		if( isset($_REQUEST['MerchantFree1']) && isset($_REQUEST['MerchantId']) && isset($_REQUEST['TransactionId']) && isset($_REQUEST['RecvNum']) && isset($_REQUEST['NyukinDate']) && 
			( isset($_REQUEST['MerchantFree2']) && $this->acting_flg_conv == $_REQUEST['MerchantFree2'] ) ) {
			$acting_opts = $this->get_acting_settings();
			if( $acting_opts['merchant_id'] == $_REQUEST['MerchantId'] ) {
				$response_data = $_REQUEST;

				$order_meta_table_name = $wpdb->prefix.'usces_order_meta';
				$query = $wpdb->prepare( "SELECT order_id FROM $order_meta_table_name WHERE meta_key = %s", $response_data['MerchantFree1'] );
				$order_id = $wpdb->get_var($query);
				if( !empty($order_id) ) {

					//オーダーステータス変更
					usces_change_order_receipt( $order_id, 'receipted' );
					//ポイント付与
					usces_action_acting_getpoint( $order_id );

					$response_data['OperateId'] = 'receipted';
					$order_meta = usces_unserialize( $usces->get_order_meta_value( $response_data['MerchantFree2'], $order_id ) );
					$meta_value = array_merge( $order_meta, $response_data );
					$usces->set_order_meta_value( $response_data['MerchantFree2'], usces_serialize($meta_value), $order_id );
					usces_log('['.$this->acting_name.'] conv receipted : '.print_r($response_data, true), 'acting_transaction.log');
				} else {
					usces_log('['.$this->acting_name.'] conv receipted order_id error : '.print_r($response_data, true), 'acting_transaction.log');
				}
			}
			header("HTTP/1.0 200 OK");
			die();
		}
	}

	/**********************************************
	* usces_filter_order_confirm_mail_payment
	* 管理画面送信メール
	* @param  $msg_payment $order_id $payment $cart $data
	* @return string $msg_payment
	***********************************************/
	public function order_confirm_mail_payment( $msg_payment, $order_id, $payment, $cart, $data ) {
		global $usces;

		if( $this->acting_flg_card == $payment['settlement'] ) {
			$acting_opts = $this->get_acting_settings();
			if( 1 === (int)$acting_opts['howtopay'] ) {

			} else {
				$acting_data = usces_unserialize( $usces->get_order_meta_value( $this->acting_flg_card, $order_id ) );
				if( isset($acting_data['PayType']) ) {
					$msg_payment = __('** Payment method **','usces')."\r\n";
					$msg_payment .= usces_mail_line( 1, $data['order_email'] );//********************
					$msg_payment .= $payment['name'];
					switch( $acting_data['PayType'] ) {
					case '01':
						$msg_payment .= ' ('.__('One time payment','usces').')';
						break;
					case '02':
					case '03':
					case '05':
					case '06':
					case '10':
					case '12':
					case '15':
					case '18':
					case '20':
					case '24':
						$times = (int)$acting_data['PayType'];
						$msg_payment .= ' ('.$times.__('-time payment','usces').')';
						break;
					case '80':
						$msg_payment .= ' ('.__('Bonus lump-sum payment','usces').')';
						break;
					case '88':
						$msg_payment .= ' ('.__('Libor Funding pay','usces').')';
						break;
					}
					$msg_payment .= "\r\n\r\n";
				}
			}

		} elseif( $this->acting_flg_conv == $payment['settlement'] && ('orderConfirmMail' == $_POST['mode'] || 'changeConfirmMail' == $_POST['mode']) ) {
			$acting_opts = $this->get_acting_settings();
			$url = $usces->get_order_meta_value( $this->paymod_id.'_conv_url', $order_id );
			$msg_payment .= sprintf( __("Payment expiration date is %s days.",'usces'), $acting_opts['conv_limit'] )."\r\n";
			$msg_payment .= __("If payment has not yet been completed, please payment procedure from the following URL.",'usces')."\r\n\r\n";
			$msg_payment .= __("[Payment URL]",'usces')."\r\n";
			$msg_payment .= $url."\r\n";
		}
		return $msg_payment;
	}

	/**********************************************
	* usces_filter_is_complete_settlement
	* ポイント即時付与
	* @param  $complete $payment_name $status
	* @return boolean $complete
	***********************************************/
	public function is_complete_settlement( $complete, $payment_name, $status ) {

		$payment = usces_get_payments_by_name( $payment_name );
		if( $this->acting_flg_card == $payment['settlement'] ) {
			$complete = true;
		}
		return $complete;
	}

	/**********************************************
	* usces_action_revival_order_data
	* 受注データ復旧処理
	* @param  $order_id $log_key $acting_flg
	* @return -
	***********************************************/
	public function revival_orderdata( $order_id, $log_key, $acting_flg ) {
		global $usces;

		if( !in_array( $acting_flg, $this->pay_method ) ) {
			return;
		}

		$usces->set_order_meta_value( 'trans_id', $log_key, $order_id );
		$usces->set_order_meta_value( 'wc_trans_id', $log_key, $order_id );

		$order_data = $usces->get_order_data( $order_id, 'direct' );
		$order_meta = array();
		$order_meta['acting'] = substr($acting_flg,7);
		$order_meta['MerchantFree1'] = $log_key;
		$total_full_price = $order_data['order_item_total_price'] - $order_data['order_usedpoint'] + $order_data['order_discount'] + $order_data['order_shipping_charge'] + $order_data['order_cod_fee'] + $order_data['order_tax'];
		if( $total_full_price < 0 ) $total_full_price = 0;
		$order_meta['Amount'] = $total_full_price;
		if( $this->acting_flg_conv == $acting_flg ) {
			$acting_opts = $this->get_acting_settings();
			$paylimit = date_i18n( 'Ymd', strtotime($order_data['order_date'])+(86400*$acting_opts['conv_limit']) ).'2359';
			$order_meta['PayLimit'] = $paylimit;
		}
		$usces->set_order_meta_value( $acting_flg, usces_serialize($order_meta), $order_id );

		if( $this->acting_flg_conv == $acting_flg ) {
			$usces->set_order_meta_value( $log_key, $acting_flg, $order_id );
		}
	}

	/**********************************************
	* usces_filter_settle_info_field_keys
	* 受注編集画面に表示する決済情報のキー
	* @param  $keys
	* @return array $keys
	***********************************************/
	public function settlement_info_field_keys( $keys ) {

		$field_keys = array_merge( $keys, array( 'MerchantFree1', 'ResponseCd', 'PayType', 'KessaiNumber', 'NyukinDate', 'CvsCd', 'PayLimit' ) );
		return $field_keys;
	}

	/**********************************************
	* usces_filter_settle_info_field_value
	* 受注編集画面に表示する決済情報の値整形
	* @param  $value $key $acting
	* @return string $value
	***********************************************/
	public function settlement_info_field_value( $value, $key, $acting ) {

		if( $this->acting_card != $acting && $this->acting_conv != $acting ) {
			return $value;
		}

		switch( $key ) {
		case 'CvsCd':
			$value = $this->get_cvs_name($value);
			break;

		case 'PayType':
			switch( $value ) {
			case '01':
				$value = __('One time payment','usces');
				break;
			case '02':
			case '03':
			case '05':
			case '06':
			case '10':
			case '12':
			case '15':
			case '18':
			case '20':
			case '24':
				$times = (int)$value;
				$value = $times.__('-time payment','usces');
				break;
			case '80':
				$value = __('Bonus lump-sum payment','usces');
				break;
			case '88':
				$value = __('Libor Funding pay','usces');
				break;
			}
		}
		return $value;
	}

	/**********************************************
	* usces_action_admin_member_info
	* 
	* @param  $data $member_metas $usces_member_history
	* @return -
	* @echo   html
	***********************************************/
	public function admin_member_info( $data, $member_metas, $usces_member_history ) {

		if( 0 < count($member_metas) ):
			//e-SCOTT 会員照会
			$response_member = $this->escott_member_reference( $data['ID'] );
			if( 'OK' == $response_member['ResponseCd'] ):
				$cardlast4 = substr($response_member['CardNo'], -4);
				$expyy = substr(date_i18n('Y', current_time('timestamp')), 0, 2).substr($response_member['CardExp'], 0, 2);
				$expmm = substr($response_member['CardExp'], 2, 2);
?>
		<tr>
			<td class="label"><?php _e('Lower 4 digits','usces'); ?></td>
			<td><div class="rod_left shortm"><?php echo $cardlast4; ?></div></td>
		</tr>
		<tr>
			<td class="label"><?php _e('Expiration date','usces'); ?></td>
			<td><div class="rod_left shortm"><?php echo $expyy.'/'.$expmm; ?></div></td>
		</tr>
		<tr>
			<td class="label"><?php _e('Quick payment','usces'); ?></td>
			<td><div class="rod_left shortm"><?php _e('Registered','usces'); ?></div></td>
		</tr>
<?php			if( !usces_have_member_continue_order( $data['ID'] ) && !usces_have_member_regular_order( $data['ID'] ) ): ?>
		<tr>
			<td class="label"><input type="checkbox" name="escott_quickpay" id="escott-quickpay-release" value="release"></td>
			<td><label for="escott-quickpay-release"><?php _e('Release quick payment','usces'); ?></label></td>
		</tr>
<?php			endif;
			endif;
		endif;
	}

	/**********************************************
	* usces_action_post_update_memberdata
	* 管理画面会員情報更新
	* @param  $member_id $res
	* @return -
	***********************************************/
	public function admin_update_memberdata( $member_id, $res ) {

		if( !$this->is_activate_card() || false === $res ) {
			return;
		}

		if( isset($_POST['escott_quickpay']) && $_POST['escott_quickpay'] == 'release' ) {
			$this->escott_member_delete( $member_id );
		}
	}

	/**********************************************
	* usces_filter_payment_detail
	* 支払方法説明
	* @param  $str $usces_entries
	* @return string $str
	***********************************************/
	public function payment_detail( $str, $usces_entries ) {
		global $usces;

		$payment = $usces->getPayments( $usces_entries['order']['payment_name'] );
		if( $this->acting_flg_card == $payment['settlement'] ) {
			$acting_opts = $this->get_acting_settings();
			if( 1 === (int)$acting_opts['howtopay'] ) {

			} else {
				$paytype = ( isset($usces_entries['order']['paytype']) ) ? esc_html($usces_entries['order']['paytype']) : '';
				if( 'token' == $acting_opts['card_activate'] && empty($paytype) ) {
					$paytype = ( isset($_POST['paytype']) ) ? $_POST['paytype'] : '';
				}
				switch( $paytype ) {
				case '01':
					$str = ' ('.__('One time payment','usces').')';
					break;
				case '02':
				case '03':
				case '05':
				case '06':
				case '10':
				case '12':
				case '15':
				case '18':
				case '20':
				case '24':
					$times = (int)$paytype;
					$str = ' ('.$times.__('-time payment','usces').')';
					break;
				case '80':
					$str = ' ('.__('Bonus lump-sum payment','usces').')';
					break;
				case '88':
					$str = ' ('.__('Libor Funding pay','usces').')';
					break;
				}
			}
		}
		return $str;
	}

	/**********************************************
	* usces_filter_payments_str
	* 支払方法 JavaScript 用決済名追加
	* @param  $payments_str $payment
	* @return string $payments_str
	***********************************************/
	public function payments_str( $payments_str, $payment ) {

		if( $this->acting_flg_card == $payment['settlement'] ) {
			if( $this->is_activate_card() ) {
				$payments_str .= "'".$payment['name']."': '".$this->paymod_id."', ";
			}
		}
		return $payments_str;
	}

	/**********************************************
	* usces_filter_payments_arr
	* 支払方法 JavaScript 用決済追加
	* @param  $payments_arr $payment
	* @return array $payments_arr
	***********************************************/
	public function payments_arr( $payments_arr, $payment ) {

		if( $this->acting_flg_card == $payment['settlement'] ) {
			if( $this->is_activate_card() ) {
				$payments_arr[] = $this->paymod_id;
			}
		}
		return $payments_arr;
	}

	/**********************************************
	* usces_filter_confirm_inform
	* 内容確認ページ Purchase Button
	* @param  $html $payments $acting_flg $rand $purchase_disabled
	* @return string $html
	***********************************************/
	public function confirm_inform( $html, $payments, $acting_flg, $rand, $purchase_disabled ) {
		global $usces;

		if( !in_array( $acting_flg, $this->pay_method ) ) {
			return $html;
		}

		$usces_entries = $usces->cart->get_entry();
		if( !$usces_entries['order']['total_full_price'] ) {
			return $html;
		}

		if( $this->acting_flg_card == $acting_flg ) {
			$acting_opts = $this->get_acting_settings();
			if( 'on' == $acting_opts['card_activate'] ) {
				$cardlast4 = ( isset($_POST['cardlast4']) ) ? $_POST['cardlast4'] : '';
				$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
				$html = '<form id="purchase_form" action="'.USCES_CART_URL.'" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
					<input type="hidden" name="cardno" value="'.trim($_POST['cardno']).'">
					<input type="hidden" name="cardlast4" value="'.trim($cardlast4).'">';
				if( 'on' == $acting_opts['seccd'] ) {
					$seccd = ( isset($_POST['seccd']) ) ? $_POST['seccd'] : '';
					$html .= '
					<input type="hidden" name="seccd" value="'.trim($seccd).'">';
				}
				$html .= '
					<input type="hidden" name="expyy" value="'.trim($_POST['expyy']).'">
					<input type="hidden" name="expmm" value="'.trim($_POST['expmm']).'">
					<input type="hidden" name="paytype" value="'.$usces_entries['order']['paytype'].'">
					<input type="hidden" name="rand" value="'.$rand.'">
					<input type="hidden" name="quick_member" value="'.$quick_member.'">
					<div class="send">
						<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="'.__('Back','usces').'"'.apply_filters( 'usces_filter_confirm_prebutton', NULL ).' />
						<input name="purchase" type="submit" id="purchase_button" class="checkout_button" value="'.__('Checkout','usces').'"'.apply_filters( 'usces_filter_confirm_nextbutton', NULL ).$purchase_disabled.' />
					</div>
					<input type="hidden" name="_nonce" value="'.wp_create_nonce($acting_flg).'">';
				if( isset($_POST['card_change']) && '1' == $_POST['card_change'] ) {
					$html .= '
					<input type="hidden" name="card_change" value="1">';
				}

			} elseif( 'link' == $acting_opts['card_activate'] ) {
				$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
				$html = '<form id="purchase_form" action="'.USCES_CART_URL.'" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
					<input type="hidden" name="rand" value="'.$rand.'">
					<input type="hidden" name="quick_member" value="'.$quick_member.'">
					<div class="send">
						<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="'.__('Back','usces').'"'.apply_filters( 'usces_filter_confirm_prebutton', NULL ).' />
						<input name="purchase" type="submit" id="purchase_button" class="checkout_button" value="'.__('Checkout','usces').'"'.apply_filters( 'usces_filter_confirm_nextbutton', NULL ).$purchase_disabled.' />
					</div>
					<input type="hidden" name="_nonce" value="'.wp_create_nonce($acting_flg).'">';

			} elseif( 'token' == $acting_opts['card_activate'] ) {
				$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
				$html = '<form id="purchase_form" action="'.USCES_CART_URL.'" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
					<input type="hidden" name="token" value="'.trim($_POST['token']).'">
					<input type="hidden" name="paytype" value="'.trim($_POST['paytype']).'">
					<input type="hidden" name="rand" value="'.$rand.'">
					<input type="hidden" name="quick_member" value="'.$quick_member.'">
					<div class="send">
						<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="'.__('Back','usces').'"'.apply_filters( 'usces_filter_confirm_prebutton', NULL ).' />
						<input name="purchase" type="submit" id="purchase_button" class="checkout_button" value="'.__('Checkout','usces').'"'.apply_filters( 'usces_filter_confirm_nextbutton', NULL ).$purchase_disabled.' />
					</div>
					<input type="hidden" name="_nonce" value="'.wp_create_nonce($acting_flg).'">';
				if( isset($_POST['card_change']) && '1' == $_POST['card_change'] ) {
					$html .= '
					<input type="hidden" name="card_change" value="1">';
				}

			}

		} elseif( $this->acting_flg_conv == $acting_flg ) {
			$html = '<form id="purchase_form" action="'.USCES_CART_URL.'" method="post" onKeyDown="if(event.keyCode == 13){return false;}">
				<input type="hidden" name="rand" value="'.$rand.'">
				<div class="send">
					<input name="backDelivery" type="submit" id="back_button" class="back_to_delivery_button" value="'.__('Back','usces').'"'.apply_filters( 'usces_filter_confirm_prebutton', NULL ).' />
					<input name="purchase" type="submit" id="purchase_button" class="checkout_button" value="'.__('Checkout','usces').'"'.apply_filters( 'usces_filter_confirm_nextbutton', NULL ).$purchase_disabled.' />
				</div>
				<input type="hidden" name="_nonce" value="'.wp_create_nonce($acting_flg).'">';
		}
		return $html;
	}

	/**********************************************
	* usces_action_confirm_page_point_inform
	* 内容確認ページ Point form
	* @param  -
	* @return -
	* @echo point_inform()
	***********************************************/
	public function e_point_inform() {

		$html = $this->point_inform( '' );
		echo $html;
	}

	/**********************************************
	* usces_filter_confirm_point_inform
	* 内容確認ページ Point form
	* @param  $html
	* @return string $html
	***********************************************/
	public function point_inform( $html ) {
		global $usces;

		$acting_opts = $this->get_acting_settings();
		$usces_entries = $usces->cart->get_entry();
		$payment = usces_get_payments_by_name( $usces_entries['order']['payment_name'] );
		$acting_flg = $payment['settlement'];
		if( $this->acting_flg_card != $acting_flg ) {
			return $html;
		}

		if( 'on' == $acting_opts['card_activate'] ) {
			$cardlast4 = ( isset($_POST['cardlast4']) ) ? $_POST['cardlast4'] : '';
			$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
			$html .= '
			<input type="hidden" name="cardno" value="'.$_POST['cardno'].'">
			<input type="hidden" name="cardlast4" value="'.$cardlast4.'">';
			if( 'on' == $acting_opts['seccd'] ) {
				$seccd = ( isset($_POST['seccd']) ) ? $_POST['seccd'] : '';
				$html .= '
				<input type="hidden" name="seccd" value="'.$seccd.'">';
			}
			$html .= '
			<input type="hidden" name="expyy" value="'.$_POST['expyy'].'">
			<input type="hidden" name="expmm" value="'.$_POST['expmm'].'">
			<input type="hidden" name="offer[paytype]" value="'.$usces_entries['order']['paytype'].'">
			<input type="hidden" name="quick_member" value="'.$quick_member.'">';

		} elseif( 'token' == $acting_opts['card_activate'] ) {
			$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
			$html .= '
			<input type="hidden" name="token" value="'.$_POST['token'].'">
			<input type="hidden" name="paytype" value="'.$_POST['paytype'].'">
			<input type="hidden" name="quick_member" value="'.$quick_member.'">';
		}
		return $html;
	}

	/**********************************************
	* usces_action_acting_processing
	* 決済処理
	* @param  $acting_flg $post_query
	* @return -
	***********************************************/
	public function acting_processing( $acting_flg, $post_query ) {
		global $usces;

		if( !in_array( $acting_flg, $this->pay_method ) ) {
			return;
		}

		$usces_entries = $usces->cart->get_entry();
		$cart = $usces->cart->get_cart();

		if( !$usces_entries || !$cart ) {
			wp_redirect(USCES_CART_URL);
		}

		if( !wp_verify_nonce( $_REQUEST['_nonce'], $acting_flg ) ) {
			wp_redirect(USCES_CART_URL);
		}

		$acting_opts = $this->get_acting_settings();
		parse_str( $post_query, $post_data );
		$TransactionDate = $this->get_transaction_date();
		$rand = $post_data['rand'];
		$member = $usces->get_member();

		if( $this->acting_flg_card == $acting_flg ) {
			if( 'on' == $acting_opts['card_activate'] || 'token' == $acting_opts['card_activate'] ) {

				//Duplication control
				$this->duplication_control( $acting_flg, $rand );

				if( isset($post_data['paytype']) && '01' != $post_data['paytype'] ) {
					$_SESSION['usces_entry']['order']['paytype'] = $post_data['paytype'];
				}
				usces_save_order_acting_data( $rand );

				$acting = $this->acting_card;
				$param_list = array();
				$params = array();

				//共通部
				$param_list['MerchantId'] = $acting_opts['merchant_id'];
				$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
				$param_list['TransactionDate'] = $TransactionDate;
				$param_list['MerchantFree1'] = $rand;
				$param_list['MerchantFree2'] = $acting_flg;
				$param_list['MerchantFree3'] = $this->merchantfree3;
				$param_list['TenantId'] = $acting_opts['tenant_id'];
				$param_list['Amount'] = $usces_entries['order']['total_full_price'];

				$token = ( isset($post_data['token']) ) ? trim($post_data['token']) : '';
				if( !empty($token) ) {
					//e-SCOTT トークンステータス参照
					$param_list['Token'] = $token;
					$param_list['OperateId'] = '1TokenSearch';
					$params['param_list'] = $param_list;
					$params['send_url'] = $acting_opts['send_url_token'];
					$response_token = $this->connection( $params );
					if( 'OK' != $response_token['ResponseCd'] || 'OK' != $response_token['TokenResponseCd'] ) {
						$tokenresponsecd = '';
						$responsecd = explode( '|', $response_token['ResponseCd'].'|'.$response_token['TokenResponseCd'] );
						foreach( (array)$responsecd as $cd ) {
							if( 'OK' != $cd ) {
								$response_token[$cd] = $this->response_message( $cd );
								$tokenresponsecd .= $cd.'|';
							}
						}
						$tokenresponsecd = rtrim($tokenresponsecd,'|');
						$response_data['MerchantFree2'] = $response_token['MerchantFree2'];
						$response_data['ResponseCd'] = $tokenresponsecd;
						$response_data['acting'] = $acting;
						$response_data['acting_return'] = 0;
						$response_data['result'] = 0;
						$logdata = array_merge( $param_list, $response_token );
						$log = array( 'acting'=>$acting.'(token_process)', 'key'=>$rand, 'result'=>$tokenresponsecd, 'data'=>$logdata );
						usces_save_order_acting_error( $log );
						wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
						exit();
					}
				}

				$quick_member = ( isset($post_data['quick_member']) ) ? $post_data['quick_member'] : '';
				if( !empty($member['ID']) && 'on' == $acting_opts['quickpay'] && 'add' == $quick_member ) {
					$response_member = $this->escott_member_process( $param_list );
					if( 'OK' == $response_member['ResponseCd'] ) {
						$param_list['KaiinId'] = $response_member['KaiinId'];
						$param_list['KaiinPass'] = $response_member['KaiinPass'];
					} else {
						$responsecd = explode( '|', $response_member['ResponseCd'] );
						foreach( (array)$responsecd as $cd ) {
							$response_member[$cd] = $this->response_message( $cd );
						}
						$response_data['MerchantFree2'] = $response_member['MerchantFree2'];
						$response_data['ResponseCd'] = $response_member['ResponseCd'];
						$response_data['acting'] = $acting;
						$response_data['acting_return'] = 0;
						$response_data['result'] = 0;
						$logdata = array_merge( $param_list, $response_member );
						$log = array( 'acting'=>$acting.'(member_process)', 'key'=>$rand, 'result'=>$response_member['ResponseCd'], 'data'=>$logdata );
						usces_save_order_acting_error( $log );
						wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
						exit();
					}
					if( true == $response_member['use_token'] ) {
						$param_list['Token'] = '';//トークンクリア
					}
					if( usces_have_continue_charge() ) {
						$chargingday = $usces->getItemChargingDay( $cart[0]['post_id'] );
						if( 99 == $chargingday ) {//受注日課金
							$OperateId = $acting_opts['operateid'];
						} else {
							$OperateId = '1Auth';
						}
						$param_list['PayType'] = '01';
					} else {
						$OperateId = $acting_opts['operateid'];
						$param_list['PayType'] = $post_data['paytype'];
					}
				} else {
					$OperateId = $acting_opts['operateid'];
					$param_list['PayType'] = ( !empty($post_data['paytype']) ) ? $post_data['paytype'] : '01';
					if( empty($token) ) {
						$param_list['CardNo'] = trim($post_data['cardno']);
						$param_list['CardExp'] = substr($post_data['expyy'],2).$post_data['expmm'];
						if( 'on' == $acting_opts['seccd'] ) {
							$param_list['SecCd'] = trim($post_data['seccd']);
						}
					}
				}
				$param_list['OperateId'] = apply_filters( 'usces_filter_escott_operateid', $OperateId, $cart, $usces_entries['order']['total_full_price'] );
				$params['param_list'] = $param_list;
				$params['send_url'] = $acting_opts['send_url'];
				//e-SCOTT 決済
				$response_data = $this->connection( $params );
				$response_data['acting'] = $acting;
				$response_data['PayType'] = $param_list['PayType'];

				if( 'OK' == $response_data['ResponseCd'] ) {
					$res = $usces->order_processing( $response_data );
					if( 'ordercompletion' == $res ) {
						$response_data['acting_return'] = 1;
						$response_data['result'] = 1;
						$response_data['nonce'] = wp_create_nonce( $this->paymod_id.'_transaction' );
						wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
					} else {
						$response_data['acting_return'] = 0;
						$response_data['result'] = 0;
						$logdata = array_merge( $usces_entries['order'], $response_data );
						$log = array( 'acting'=>$acting, 'key'=>$rand, 'result'=>'ORDER DATA REGISTERED ERROR', 'data'=>$logdata );
						usces_save_order_acting_error( $log );
						wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
					}
				} else {
					$response_data['acting_return'] = 0;
					$response_data['result'] = 0;
					$responsecd = explode( '|', $response_data['ResponseCd'] );
					foreach( (array)$responsecd as $cd ) {
						$response_data[$cd] = $this->response_message( $cd );
					}
					$logdata = array_merge( $params, $response_data );
					$log = array( 'acting'=>$acting, 'key'=>$rand, 'result'=>$response_data['ResponseCd'], 'data'=>$logdata );
					usces_save_order_acting_error( $log );
					wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
				}
				exit();
			}

		} elseif( $this->acting_flg_conv == $acting_flg ) {

			//Duplication control
			$this->duplication_control( $acting_flg, $rand );

			usces_save_order_acting_data( $rand );

			$acting = $this->acting_conv;
			$param_list = array();
			$params = array();

			$item_name = mb_convert_kana($usces->getItemName($cart[0]['post_id']), 'ASK', 'UTF-8');
			if( 1 < count($cart) ) {
				if( 16 < mb_strlen($item_name.__(' etc.','usces'), 'UTF-8') ) {
					$item_name = mb_substr($item_name, 0, 12, 'UTF-8').__(' etc.','usces');
				}
			} else {
				if( 16 < mb_strlen($item_name, 'UTF-8') ) {
					$item_name = mb_substr($item_name, 0, 13, 'UTF-8').__('...','usces');
				}
			}
			$paylimit = date_i18n( 'Ymd', current_time('timestamp')+(86400*$acting_opts['conv_limit']) ).'2359';

			//共通部
			$param_list['MerchantId'] = $acting_opts['merchant_id'];
			$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
			$param_list['TransactionDate'] = $TransactionDate;
			$param_list['MerchantFree1'] = $rand;
			$param_list['MerchantFree2'] = $acting_flg;
			$param_list['MerchantFree3'] = $this->merchantfree3;
			$param_list['TenantId'] = $acting_opts['tenant_id'];
			$param_list['Amount'] = $usces_entries['order']['total_full_price'];
			$param_list['OperateId'] = '2Add';
			$param_list['PayLimit'] = $paylimit;
			$param_list['NameKanji'] = $usces_entries['customer']['name1'].$usces_entries['customer']['name2'];
			$param_list['NameKana'] = ( !empty($usces_entries['customer']['name3']) ) ? $usces_entries['customer']['name3'].$usces_entries['customer']['name4'] : $param_list['NameKanji'];
			$param_list['TelNo'] = $usces_entries['customer']['tel'];
			$param_list['ShouhinName'] = $item_name;
			$param_list['Comment'] = __('Thank you for using.','usces');
			$param_list['ReturnURL'] = home_url('/');
			$params['send_url'] = $acting_opts['send_url_conv'];
			$params['param_list'] = $param_list;
			//e-SCOTT オンライン収納代行データ登録
			$response_data = $this->connection( $params );
			$response_data['acting'] = $acting;
			$response_data['PayLimit'] = $paylimit;
			$response_data['Amount'] = $param_list['Amount'];

			if( 'OK' == $response_data['ResponseCd'] ) {
				$FreeArea = trim($response_data['FreeArea']);
				$url = add_query_arg( array( 'code'=>$FreeArea, 'rkbn'=>1 ), $acting_opts['redirect_url_conv'] );
				$res = $usces->order_processing( $response_data );
				if( 'ordercompletion' == $res ) {
					if( isset($response_data['MerchantFree1']) ) {
						usces_ordered_acting_data( $response_data['MerchantFree1'] );
					}
					$usces->cart->clear_cart();
					header( 'location:'.$url );
					exit();
				} else {
					$response_data['acting_return'] = 0;
					$response_data['result'] = 0;
					$logdata = array_merge( $usces_entries['order'], $response_data );
					$log = array( 'acting'=>$acting, 'key'=>$rand, 'result'=>'ORDER DATA REGISTERED ERROR', 'data'=>$logdata );
					usces_save_order_acting_error( $log );
					wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
				}
			} else {
				$response_data['acting_return'] = 0;
				$response_data['result'] = 0;
				$responsecd = explode( '|', $response_data['ResponseCd'] );
				foreach( (array)$responsecd as $cd ) {
					$response_data[$cd] = $this->response_message( $cd );
				}
				$logdata = array_merge( $params, $response_data );
				$log = array( 'acting'=>$acting, 'key'=>$rand, 'result'=>$response_data['ResponseCd'], 'data'=>$logdata );
				usces_save_order_acting_error( $log );
				wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
			}
			exit();
		}
	}

	/**********************************************
	* usces_filter_check_acting_return_results
	* 決済完了ページ制御
	* @param  $results
	* @return array $results
	***********************************************/
	public function acting_return( $results ) {

		if( !in_array( 'acting_'.$results['acting'], $this->pay_method ) ) {
			return $results;
		}

		if( isset($results['acting_return']) && $results['acting_return'] != 1 ) {
			return $results;
		}

		$results['reg_order'] = false;

		usces_log('['.$this->acting_name.'] results : '.print_r($results, true), 'acting_transaction.log');
		if( !isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], $this->paymod_id.'_transaction') ) {
			wp_redirect( home_url() );
			exit();
		}

		return $results;
	}

	/**********************************************
	* usces_filter_check_acting_return_duplicate
	* 重複オーダー禁止処理
	* @param  $trans_id $results
	* @return string RandId
	***********************************************/
	public function check_acting_return_duplicate( $trans_id, $results ) {
		global $usces;

		$entry = $usces->cart->get_entry();
		if( !$entry['order']['total_full_price'] ) {
			return 'not_credit';
		} elseif( isset($results['MerchantFree1']) && isset($results['acting']) && ( $this->acting_card == $results['acting'] || $this->acting_conv == $results['acting'] ) ) {
			return $results['MerchantFree1'];
		} else {
			return $trans_id;
		}
	}

	/**********************************************
	* usces_action_reg_orderdata
	* 受注データ登録
	* call from usces_reg_orderdata() and usces_new_orderdata().
	* @param  $args = array(
	*						'cart'=>$cart, 'entry'=>$entry, 'order_id'=>$order_id, 
	*						'member_id'=>$member['ID'], 'payments'=>$set, 'charging_type'=>$charging_type, 
	*						'results'=>$results
	*						);
	* @return -
	***********************************************/
	public function register_orderdata( $args ) {
		global $usces;
		extract($args);

		$acting_flg = $payments['settlement'];
		if( !in_array( $acting_flg, $this->pay_method ) ) {
			return;
		}

		if( !$entry['order']['total_full_price'] ) {
			return;
		}

		if( isset($results['MerchantFree1']) ) {
			$usces->set_order_meta_value( 'trans_id', $results['MerchantFree1'], $order_id );
			$usces->set_order_meta_value( 'wc_trans_id', $results['MerchantFree1'], $order_id );
			$usces->set_order_meta_value( $acting_flg, usces_serialize($results), $order_id );
		}

		if( $this->acting_flg_conv == $acting_flg ) {
			$usces->set_order_meta_value( $results['MerchantFree1'], $acting_flg, $order_id );
		}
	}

	/**********************************************
	* usces_post_reg_orderdata
	* 受注データ登録
	* call after usces_reg_orderdata().
	* @param  $order_id $results
	* @return -
	***********************************************/
	public function post_register_orderdata( $order_id, $results ) {
		global $usces;

		if( isset($results['acting']) && $this->acting_conv == $results['acting'] ) {
			$acting_opts = $this->get_acting_settings();
			$FreeArea = trim($results['FreeArea']);
			$url = add_query_arg( array( 'code'=>$FreeArea, 'rkbn'=>2 ), $acting_opts['redirect_url_conv'] );
			$usces->set_order_meta_value( $this->paymod_id.'_conv_url', $url, $order_id );
		}
	}

	/**********************************************
	* usces_filter_get_error_settlement
	* 決済エラーメッセージ
	* @param  $html
	* @return string $html
	***********************************************/
	public function error_page_message( $html ) {

		$acting_flg = ( isset($_REQUEST['MerchantFree2']) ) ? $_REQUEST['MerchantFree2'] : '';
		if( $this->acting_flg_card == $acting_flg ) {
			if( isset($_REQUEST['MerchantFree1']) && usces_get_order_id_by_trans_id( (int)$_REQUEST['MerchantFree1'] ) ) {
				$html .= '<div class="error_page_mesage">
				<p>'.__('Your order has already we complete.','usces').'</p>
				<p>'.__('Please do not re-display this page.','usces').'</p>
				</div>';
			} else {
				$error_message = array();
				$responsecd = explode( '|', $_REQUEST['ResponseCd'] );
				foreach( (array)$responsecd as $cd ) {
					$error_message[] = $this->error_message( $cd );
				}
				$error_message = array_unique( $error_message );
				if( 0 < count($error_message) ) {
					$html .= '<div class="error_page_mesage">
					<p>'.__('Error code','usces').'：'.$_REQUEST['ResponseCd'].'</p>';
					foreach( $error_message as $message ) {
						$html .= '<p>'.$message.'</p>';
					}
					$html .= '
					<p class="return_settlement"><a href="'.add_query_arg( array( 'backDelivery'=>$this->acting_card, 're-enter'=>1 ), USCES_CART_URL ).'">'.__('Card number re-enter','usces').'</a></p>
					</div>';
				}
			}

		} elseif( $this->acting_flg_conv == $acting_flg ) {
			$error_message = array();
			$responsecd = explode( '|', $_REQUEST['ResponseCd'] );
			foreach( (array)$responsecd as $cd ) {
				$error_message[] = $this->error_message( $cd );
			}
			$error_message = array_unique( $error_message );
			if( 0 < count($error_message) ) {
				$html .= '<div class="error_page_mesage">
				<p>'.__('Error code','usces').'：'.$_REQUEST['ResponseCd'].'</p>';
				foreach( $error_message as $message ) {
					$html .= '<p>'.$message.'</p>';
				}
			}
			$html .= '</div>';
		}
		return $html;
	}

	/**********************************************
	* usces_filter_send_order_mail_payment
	* オンライン収納代行決済用サンキューメール
	* @param  $msg_payment $order_id $payment $cart $entry $data
	* @return string $msg_payment
	***********************************************/
	public function order_mail_payment( $msg_payment, $order_id, $payment, $cart, $entry, $data ) {
		global $usces;

		if( $this->acting_flg_conv != $payment['settlement'] ) {
			return $msg_payment;
		}

		$acting_opts = $this->get_acting_settings();
		$url = $usces->get_order_meta_value( $this->paymod_id.'_conv_url', $order_id );
		$msg_payment .= sprintf( __("Payment expiration date is %s days.",'usces'), $acting_opts['conv_limit'] )."\r\n";
		$msg_payment .= __("If payment has not yet been completed, please payment procedure from the following URL.",'usces')."\r\n\r\n";
		$msg_payment .= __("[Payment URL]",'usces')."\r\n";
		$msg_payment .= $url."\r\n";
		return $msg_payment;
	}

	/**********************************************
	* admin_notices
	* 管理画面メッセージ表示
	* @param  -
	* @return -
	***********************************************/
	public function display_admin_notices() {

		$acting_opts = $this->get_acting_settings();
		if( 'on' == $acting_opts['card_activate'] ) {
			if( empty($acting_opts['token_code']) ) {
				echo '<div class="update-nag">'.$this->acting_name.sprintf( __("Please enter the <a href=\"admin.php?page=usces_settlement#uscestabs_%s\">'Token auth code'</a>.",'usces'), $this->paymod_id ).'</div>';
			}
		}
	}

	/**********************************************
	* wp_print_footer_scripts
	* JavaScript
	* @param  -
	* @return -
	***********************************************/
	public function footer_scripts() {
		global $usces;

		if( !$this->is_validity_acting('card') ) {
			return;
		}

		//発送・支払方法ページ
		if( 'delivery' == $usces->page ):
			$acting_opts = $this->get_acting_settings();
			//埋込み型
			if( isset($acting_opts['card_activate']) && 'on' == $acting_opts['card_activate'] ):
?>
<script type="text/javascript">
(function($) {
	$("#cardno").change( function(e) {
		var first_c = $(this).val().substr( 0, 1 );
		var second_c = $(this).val().substr( 1, 1 );
		if( '4' == first_c || '5' == first_c || ( '3' == first_c && '5' == second_c ) ) {
			$("#paytype_default").attr("disabled", "disabled").css("display", "none");
			$("#paytype4535").removeAttr("disabled").css("display", "inline");
			$("#paytype37").attr("disabled", "disabled").css("display", "none");
			$("#paytype36").attr("disabled", "disabled").css("display", "none");
		} else if( '3' == first_c && '6' == second_c ) {
			$("#paytype_default").attr("disabled", "disabled").css("display", "none");
			$("#paytype4535").attr("disabled", "disabled").css("display", "none");
			$("#paytype37").attr("disabled", "disabled").css("display", "none");
			$("#paytype36").removeAttr("disabled").css("display", "inline");
		} else if( '3' == first_c && '7' == second_c ) {
			$("#paytype_default").attr("disabled", "disabled").css("display", "none");
			$("#paytype4535").attr("disabled", "disabled").css("display", "none");
			$("#paytype37").removeAttr("disabled").css("display", "inline");
			$("#paytype36").attr("disabled", "disabled").css("display", "none");
		} else {
			$("#paytype_default").removeAttr("disabled").css("display", "inline");
			$("#paytype4535").attr("disabled", "disabled").css("display", "none");
			$("#paytype37").attr("disabled", "disabled").css("display", "none");
			$("#paytype36").attr("disabled", "disabled").css("display", "none");
		}
	});
	$("#cardno").trigger( "change" );
		<?php if( isset($_REQUEST['backDelivery']) && $this->paymod_id.'_card' == substr($_REQUEST['backDelivery'], 0, 12) ):
			$payment_method = usces_get_system_option( 'usces_payment_method', 'settlement' );
			$id = $payment_method[$this->acting_flg_card]['sort']; ?>
	$("#payment_name_<?php echo $id; ?>").prop( "checked", true );
		<?php endif; ?>
})(jQuery);
</script>
<?php
			//トークン決済
			elseif( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ):
				wp_register_style( 'jquery-ui-style', USCES_FRONT_PLUGIN_URL.'/css/jquery/jquery-ui-1.10.3.custom.min.css' );
				wp_enqueue_style( 'jquery-ui-style' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'usces_cart_escott', USCES_FRONT_PLUGIN_URL.'/js/cart_escott.js', array('jquery'), USCES_VERSION, true );
			endif;

		//マイページ
		elseif( $usces->is_member_page($_SERVER['REQUEST_URI']) ):
			$member = $usces->get_member();
			$KaiinId = $this->get_quick_kaiin_id( $member['ID'] );
			if( !empty($KaiinId) ):
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$("input[name='deletemember']").css("display","none");
});
</script>
<?php
			endif;
		endif;
	}

	/**********************************************
	* usces_filter_delivery_check
	* カード情報入力チェック
	* @param  $mes
	* @return string $mes
	***********************************************/
	public function delivery_check( $mes ) {
		global $usces;

		if( !isset($_POST['offer']['payment_name']) ) {
			return $mes;
		}

		$payment = $usces->getPayments( $_POST['offer']['payment_name'] );
		if( $this->acting_flg_card == $payment['settlement'] ) {
			$acting_opts = $this->get_acting_settings();
			if( isset($acting_opts['card_activate']) && 'on' == $acting_opts['card_activate'] ) {
				if( 'on' == $acting_opts['seccd'] ) {
					if( ( isset($_POST['acting']) && $this->paymod_id == $_POST['acting'] ) && 
						( isset($_POST['cardno']) && empty($_POST['cardno']) ) || 
						( isset($_POST['seccd']) && empty($_POST['seccd']) ) || 
						( isset($_POST['expyy']) && empty($_POST['expyy']) ) || 
						( isset($_POST['expmm']) && empty($_POST['expmm']) ) ) {
						$mes .= __('Please enter the card information correctly.','usces').'<br />';
					}
				} else {
					if( ( isset($_POST['acting']) && $this->paymod_id == $_POST['acting'] ) && 
						( isset($_POST['cardno']) && empty($_POST['cardno']) ) || 
						( isset($_POST['expyy']) && empty($_POST['expyy']) ) || 
						( isset($_POST['expmm']) && empty($_POST['expmm']) ) ) {
						$mes .= __('Please enter the card information correctly.','usces').'<br />';
					}
				}
			} elseif( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ) {
				if( isset($_POST['token']) && empty($_POST['token']) ) {
					if( $usces->is_member_logged_in() && 'on' == $acting_opts['quickpay'] ) {
						$quick_member = ( isset($_POST['quick_member']) ) ? $_POST['quick_member'] : '';
						if( 'add' != $quick_member ) {
							$mes .= __('Please enter the card information correctly.','usces').'<br />';
						}
					} else {
						$mes .= __('Please enter the card information correctly.','usces').'<br />';
					}
				}
			}
		}
		return $mes;
	}

	/**********************************************
	* usces_filter_delivery_secure_form
	* 支払方法ページ用入力フォーム
	* @param  $html $payment
	* @return string $html
	***********************************************/
	public function delivery_secure_form( $html, $payment ) {
		global $usces;

		if( $usces->is_cart_page($_SERVER['REQUEST_URI']) && 'delivery' == $usces->page ) {
			$acting_opts = $this->get_acting_settings();
			if( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ) {
				$html .= '
					<input type="hidden" name="acting" value="'.$this->paymod_id.'" />
					<input type="hidden" name="confirm" value="confirm" />
					<input type="hidden" name="token" id="token" value="" />
					<input type="hidden" name="paytype" value="" />
					<input type="hidden" name="quick_member" value="" />
					<input type="hidden" name="card_change" value="" />';
			}
		}
		return $html;
	}

	/**********************************************
	* usces_filter_delivery_secure_form_loop
	* 支払方法ページ用入力フォーム
	* @param  $nouse $payment
	* @return string $html
	***********************************************/
	public function delivery_secure_form_loop( $nouse, $payment ) {
		global $usces;

		$html = '';
		if( $this->acting_flg_card == $payment['settlement'] ) {
			$acting_opts = $this->get_acting_settings();
			if( ( !isset($acting_opts['activate']) || 'on' != $acting_opts['activate'] ) || 
				( !isset($acting_opts['card_activate']) || 'on' != $acting_opts['card_activate'] ) ||
				'activate' != $payment['use'] ) {
				//continue;
				return $html;
			}

			$backDelivery = ( isset($_REQUEST['backDelivery']) && $this->paymod_id.'_card' == substr($_REQUEST['backDelivery'], 0, 12) ) ? true : false;
			$card_change = ( isset($_REQUEST['card_change']) ) ? true : false;
			if( $card_change ) {
				if( 'on' == $acting_opts['seccd'] ) {
					if( ( isset($_POST['acting']) && $this->paymod_id == $_POST['acting'] ) && 
						( isset($_POST['cardno']) && empty($_POST['cardno']) ) || 
						( isset($_POST['seccd']) && empty($_POST['seccd']) ) || 
						( isset($_POST['expyy']) && empty($_POST['expyy']) ) || 
						( isset($_POST['expmm']) && empty($_POST['expmm']) ) ) {
						$backDelivery = true;
					}
				} else {
					if( ( isset($_POST['acting']) && $this->paymod_id == $_POST['acting'] ) && 
						( isset($_POST['cardno']) && empty($_POST['cardno']) ) || 
						( isset($_POST['expyy']) && empty($_POST['expyy']) ) || 
						( isset($_POST['expmm']) && empty($_POST['expmm']) ) ) {
						$backDelivery = true;
					}
				}
			}

			$cardno = ( isset($_POST['cardno']) ) ? esc_html($_POST['cardno']) : '';
			$expyy = ( isset($_POST['expyy']) ) ? esc_html($_POST['expyy']) : '';
			$expmm = ( isset($_POST['expmm']) ) ? esc_html($_POST['expmm']) : '';
			$paytype = ( isset($usces_entries['order']['paytype']) ) ? esc_html($usces_entries['order']['paytype']) : '01';

			$html .= '<input type="hidden" name="acting" value="'.$this->paymod_id.'">';
			$html .= '
			<table class="customer_form" id="'.$this->paymod_id.'">';

			if( usces_is_login() ) {
				$member = $usces->get_member();
				$KaiinId = $this->get_quick_kaiin_id( $member['ID'] );
				$KaiinPass = $this->get_quick_pass( $member['ID'] );
			}

			$response_member = array( 'ResponseCd'=>'' );

			if( 'on' == $acting_opts['quickpay'] && !empty($KaiinId) && !empty($KaiinPass) && !$card_change ) {
				//e-SCOTT 会員照会
				$response_member = $this->escott_member_reference( $member['ID'], $KaiinId, $KaiinPass );
			}
			if( 'OK' == $response_member['ResponseCd'] && !$backDelivery ) {
				$cardlast4 = substr($response_member['CardNo'], -4);
				$expyy = substr(date_i18n('Y', current_time('timestamp')), 0, 2).substr($response_member['CardExp'], 0, 2);
				$expmm = substr($response_member['CardExp'], 2, 2);
				$html .= '
				<input name="cardno" type="hidden" value="8888888888888888" />
				<input name="cardlast4" type="hidden" value="'.$cardlast4.'" />
				<input name="expyy" type="hidden" value="'.$expyy.'" />
				<input name="expmm" type="hidden" value="'.$expmm.'" />
				<input name="quick_member" type="hidden" value="add">
				<tr>
					<th scope="row">'.__('The last four digits of your card number','usces').'</th>
					<td colspan="2"><p>'.$cardlast4.' (<a href="'.add_query_arg( array('backDelivery'=>$this->paymod_id.'_card','card_change'=>1), USCES_CART_URL ).'">'.__('Change of card information, click here','usces').'</a>)</p></td>
				</tr>';

			} else {
				$cardno_attention = apply_filters( 'usces_filter_cardno_attention', __('(Single-byte numbers only)','usces').'<div class="attention">'.__('* Please do not enter symbols or letters other than numbers such as space (blank), hyphen (-) between numbers.','usces').'</div>' );
				$change = ( $card_change ) ? '<input type="hidden" name="card_change" value="1">' : '';
				$quickpay = '';
				if( usces_is_login() && 'on' == $acting_opts['quickpay'] ) {
					if( usces_have_regular_order() || usces_have_continue_charge() ) {
						$quickpay = '<input type="hidden" name="quick_member" value="add">';
					} elseif( 'on' != $acting_opts['chooseable_quickpay'] ) {
						$quickpay = '<input type="hidden" name="quick_member" value="add">';
					} else {
						$quickpay = '<p class="escott_quick_member"><label type="add"><input type="checkbox" name="quick_member" value="add"><span>'.__('Register and purchase a credit card','usces').'</span></label></p>';
					}
				} else {
					$quickpay = '<input type="hidden" name="quick_member" value="no">';
				}
				$html .= '
				<tr>
					<th scope="row">'.__('card number','usces').'</th>
					<td colspan="2"><input name="cardno" id="cardno" type="tel" value="'.$cardno.'" />'.$cardno_attention.$change.$quickpay.'</td>
				</tr>';
				if( 'on' == $acting_opts['seccd'] ) {
					$seccd = ( isset($_POST['seccd']) ) ? esc_html($_POST['seccd']) : '';
					$seccd_attention = apply_filters( 'usces_filter_seccd_attention', __('(Single-byte numbers only)','usces') );
					$html .= '
				<tr>
					<th scope="row">'.__('security code','usces').'</th>
					<td colspan="2"><input name="seccd" type="tel" value="'.$seccd.'" />'.$seccd_attention.'</td>
				</tr>';
				}
				$html .= '
				<tr>
					<th scope="row">'.__('Card expiration','usces').'</th>
					<td colspan="2">
						<select name="expmm">
							<option value="">----</option>';
				for( $i = 1; $i <= 12; $i++ ) {
					$html .= '
							<option value="'.sprintf('%02d', $i).'"'.(( $i == (int)$expmm ) ? ' selected="selected"' : '').'>'.sprintf('%2d', $i).'</option>';
				}
				$html .= '
						</select>'.__('month','usces').'&nbsp;
						<select name="expyy">
							<option value="">----</option>';
				for( $i = 0; $i < 15; $i++ ) {
					$year = date_i18n('Y') - 1 + $i;
					$selected = ( $year == $expyy ) ? ' selected="selected"' : '';
					$html .= '
							<option value="'.$year.'"'.$selected.'>'.$year.'</option>';
				}
				$html .= '
						</select>'.__('year','usces').'
					</td>
				</tr>';
			}

			$html_paytype = '';
			if( ( usces_have_regular_order() || usces_have_continue_charge() ) && usces_is_login() ) {
				$html_paytype .= '<input type="hidden" name="offer[paytype]" value="01" />';

			} else {
				if( 1 === (int)$acting_opts['howtopay'] ) {
				//	$html_paytype .= '
				//<tr>
				//	<th scope="row">'.__('Number of payments','usces').'</th>
				//	<td colspan="2">'.__('Single payment only','usces').'
				//		<input type="hidden" name="offer[paytype]" value="01" />
				//	</td>
				//</tr>';
					$html_paytype .= '<input type="hidden" name="offer[paytype]" value="01" />';

				} elseif( 2 <= $acting_opts['howtopay'] ) {
					$cardfirst4 = ( 'OK' == $response_member['ResponseCd'] && !$backDelivery ) ? '<input type="hidden" id="cardno" value="'.substr($response_member['CardNo'], 0, 4).'" />' : '';//先頭4桁
					$html_paytype .= '
				<tr>
					<th scope="row">'.__('Number of payments','usces').'</th>
					<td colspan="2">'.$cardfirst4.'<div class="paytype">';

					$html_paytype .= '
						<select name="offer[paytype]" id="paytype_default" >
							<option value="01"'.(('01' == $paytype) ? ' selected="selected"' : '').'>'.__('One time payment','usces').'</option>
						</select>';

					$html_paytype .= '
						<select name="offer[paytype]" id="paytype4535" style="display:none;" disabled="disabled" >
							<option value="01"'.(('01' == $paytype) ? ' selected="selected"' : '').'>1'.__('-time payment','usces').'</option>
							<option value="02"'.(('02' == $paytype) ? ' selected="selected"' : '').'>2'.__('-time payment','usces').'</option>
							<option value="03"'.(('03' == $paytype) ? ' selected="selected"' : '').'>3'.__('-time payment','usces').'</option>
							<option value="05"'.(('05' == $paytype) ? ' selected="selected"' : '').'>5'.__('-time payment','usces').'</option>
							<option value="06"'.(('06' == $paytype) ? ' selected="selected"' : '').'>6'.__('-time payment','usces').'</option>
							<option value="10"'.(('10' == $paytype) ? ' selected="selected"' : '').'>10'.__('-time payment','usces').'</option>
							<option value="12"'.(('12' == $paytype) ? ' selected="selected"' : '').'>12'.__('-time payment','usces').'</option>
							<option value="15"'.(('15' == $paytype) ? ' selected="selected"' : '').'>15'.__('-time payment','usces').'</option>
							<option value="18"'.(('18' == $paytype) ? ' selected="selected"' : '').'>18'.__('-time payment','usces').'</option>
							<option value="20"'.(('20' == $paytype) ? ' selected="selected"' : '').'>20'.__('-time payment','usces').'</option>
							<option value="24"'.(('24' == $paytype) ? ' selected="selected"' : '').'>24'.__('-time payment','usces').'</option>
							<option value="88"'.(('88' == $paytype) ? ' selected="selected"' : '').'>'.__('Libor Funding pay','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80"'.(('80' == $paytype) ? ' selected="selected"' : '').'>'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '
						<select name="offer[paytype]" id="paytype37" style="display:none;" disabled="disabled" >
							<option value="01"'.(('01' == $paytype) ? ' selected="selected"' : '').'>1'.__('-time payment','usces').'</option>
							<option value="03"'.(('03' == $paytype) ? ' selected="selected"' : '').'>3'.__('-time payment','usces').'</option>
							<option value="05"'.(('05' == $paytype) ? ' selected="selected"' : '').'>5'.__('-time payment','usces').'</option>
							<option value="06"'.(('06' == $paytype) ? ' selected="selected"' : '').'>6'.__('-time payment','usces').'</option>
							<option value="10"'.(('10' == $paytype) ? ' selected="selected"' : '').'>10'.__('-time payment','usces').'</option>
							<option value="12"'.(('12' == $paytype) ? ' selected="selected"' : '').'>12'.__('-time payment','usces').'</option>
							<option value="15"'.(('15' == $paytype) ? ' selected="selected"' : '').'>15'.__('-time payment','usces').'</option>
							<option value="18"'.(('18' == $paytype) ? ' selected="selected"' : '').'>18'.__('-time payment','usces').'</option>
							<option value="20"'.(('20' == $paytype) ? ' selected="selected"' : '').'>20'.__('-time payment','usces').'</option>
							<option value="24"'.(('24' == $paytype) ? ' selected="selected"' : '').'>24'.__('-time payment','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80"'.(('80' == $paytype) ? ' selected="selected"' : '').'>'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '
						<select name="offer[paytype]" id="paytype36" style="display:none;" disabled="disabled" >
							<option value="01"'.(('01' == $paytype) ? ' selected="selected"' : '').'>'.__('One time payment','usces').'</option>
							<option value="88"'.(('88' == $paytype) ? ' selected="selected"' : '').'>'.__('Libor Funding pay','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80"'.(('80' == $paytype) ? ' selected="selected"' : '').'>'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '</div>
					</td>
				</tr>';
				}
			}
			$html .= apply_filters( 'usces_filter_escott_secure_form_paytype', $html_paytype );
			$html .= '
			</table><table>';
		}
		return $html;
	}

	/**********************************************
	* usces_filter_delete_member_check
	* 会員データ削除チェック
	* @param  $del $member_id
	* @return boolean $del
	***********************************************/
	public function delete_member_check( $del, $member_id ) {
		$KaiinId = $this->get_quick_kaiin_id( $member_id );
		if( !empty($KaiinId) ) {
			$del = false;
		}
		return $del;
	}

	/**********************************************
	* wp_print_styles
	* Style
	* @param  -
	* @return -
	***********************************************/
	public function print_styles() {
		global $usces;

		//発送・支払方法ページ
		if( !is_admin() && 'delivery' == $usces->page && $this->is_validity_acting('card') ):
			$acting_opts = $this->get_acting_settings();
			if( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ):
?>
<style type="text/css">
#escott-dialog {
	left: 50% !important;
	transform: translateY(-50%) translateX(-50%);
	-webkit- transform: translateY(-50%) translateX(-50%);
	width: 90% !important;
	max-width: 700px; 
}</style>
<?php
			endif;
		endif;
	}

	/**********************************************
	* wp_enqueue_scripts
	* JavaScript
	* @param  -
	* @return -
	***********************************************/
	public function enqueue_scripts() {
		global $usces;

		//発送・支払方法ページ
		if( !is_admin() && 'delivery' == $usces->page && $this->is_validity_acting('card') ):
			$acting_opts = $this->get_acting_settings();
			if( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ):
?>
<script type="text/javascript"
src="<?php esc_html_e( $acting_opts['api_token'] ); ?>?k_TokenNinsyoCode=<?php esc_html_e( $acting_opts['token_code'] ); ?>" callBackFunc="setToken" class="spsvToken"></script>
<?php
			endif;
		endif;
	}

	/**********************************************
	* usces_filter_uscesL10n
	* JavaScript
	* @param  -
	* @return -
	***********************************************/
	public function set_uscesL10n() {
		global $usces;

		if( $usces->is_cart_page($_SERVER['REQUEST_URI']) && 'delivery' == $usces->page ) {
			$acting_opts = $this->get_acting_settings();
			if( isset($acting_opts['card_activate']) && 'token' == $acting_opts['card_activate'] ) {
				echo "'front_ajaxurl': '".USCES_SSL_URL."',\n";
				$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
				$payment_method = apply_filters( 'usces_fiter_the_payment_method', $payment_method, '' );
				foreach( (array)$payment_method as $id => $payment ) {
					if( $payment['settlement'] == $this->acting_flg_card ) {
						echo "'escott_token_payment_id': '".$id."',\n";
						break;
					}
				}
				echo "'escott_token_dialog_title': '".__('Credit card information','usces')."',\n";
				echo "'escott_token_btn_next': '".__('Next')."',\n";
				echo "'escott_token_btn_cancel': '".__('Cancel')."',\n";
				echo "'escott_token_error_message': '".__('Credit card information is not appropriate.','usces')."',\n";
			}
		}
	}

	/**********************************************
	* usces_front_ajax
	* JavaScript
	* @param  -
	* @return -
	***********************************************/
	public function front_ajax() {
		global $usces;

		switch( $_POST['usces_ajax_action'] ) {
		case 'escott_token_dialog':
			if( !wp_verify_nonce( $_POST['wc_nonce'], 'wc_delivery_secure_nonce' ) ) {
				wp_redirect(USCES_CART_URL);
			}

			$acting_opts = $this->get_acting_settings();
			$card_change = ( isset($_POST['card_change']) ) ? true : false;

			$html = '';
			$html .= '
			<table class="customer_form settlement_form" id="'.$this->paymod_id.'">';

			if( usces_is_login() ) {
				$member = $usces->get_member();
				$KaiinId = $this->get_quick_kaiin_id( $member['ID'] );
				$KaiinPass = $this->get_quick_pass( $member['ID'] );
			}

			$response_member = array( 'ResponseCd'=>'' );

			if( 'on' == $acting_opts['quickpay'] && !empty($KaiinId) && !empty($KaiinPass) && !$card_change ) {
				//e-SCOTT 会員照会
				$response_member = $this->escott_member_reference( $member['ID'], $KaiinId, $KaiinPass );
			}

			if( 'OK' == $response_member['ResponseCd'] ) {
				$cardlast4 = substr($response_member['CardNo'], -4);
				$html .= '
				<tr>
					<th scope="row">'.__('The last four digits of your card number','usces').'</th>
					<td colspan="2"><p>'.$cardlast4.' (<a href="#" id="escott_card_change">'.__('Change of card information, click here','usces').'</a>)</p></td>
				</tr>';

			} else {
				$cardno_attention = apply_filters( 'usces_filter_cardno_attention', __('(Single-byte numbers only)','usces').'<div class="attention">'.__('* Please do not enter symbols or letters other than numbers such as space (blank), hyphen (-) between numbers.','usces').'</div>' );
				$change = ( $card_change ) ? '<input type="hidden" id="card_change" value="1">' : '';
				$quickpay = '';
				if( usces_is_login() && 'on' == $acting_opts['quickpay'] ) {
					if( usces_have_regular_order() || usces_have_continue_charge() ) {
						$quickpay = '<input type="hidden" id="quick_member" value="add">';
					} elseif( 'on' != $acting_opts['chooseable_quickpay'] ) {
						$quickpay = '<input type="hidden" id="quick_member" value="add">';
					} else {
						$quickpay = '<p class="escott_quick_member"><label type="add"><input type="checkbox" id="quick_member" value="add"><span>'.__('Register and purchase a credit card','usces').'</span></label></p>';
					}
				} else {
					$quickpay = '<input type="hidden" id="quick_member" value="no">';
				}
				$html .= '
				<tr>
					<th scope="row">'.__('card number','usces').'</th>
					<td colspan="2"><input id="cardno" type="tel" value="" />'.$cardno_attention.$change.$quickpay.'</td>
				</tr>';
				if( 'on' == $acting_opts['seccd'] ) {
					$seccd_attention = apply_filters( 'usces_filter_seccd_attention', __('(Single-byte numbers only)','usces') );
					$html .= '
				<tr>
					<th scope="row">'.__('security code','usces').'</th>
					<td colspan="2"><input id="seccd" type="tel" value="" />'.$seccd_attention.'</td>
				</tr>';
				}
				$html .= '
				<tr>
					<th scope="row">'.__('Card expiration','usces').'</th>
					<td colspan="2">
						<select id="expmm">
							<option value="">----</option>';
				for( $i = 1; $i <= 12; $i++ ) {
					$html .= '
							<option value="'.sprintf('%02d', $i).'">'.sprintf('%2d', $i).'</option>';
				}
				$html .= '
						</select>'.__('month','usces').'&nbsp;
						<select id="expyy">
							<option value="">------</option>';
				for( $i = 0; $i < 15; $i++ ) {
					$year = date_i18n('Y') - 1 + $i;
					$html .= '
							<option value="'.$year.'">'.$year.'</option>';
				}
				$html .= '
						</select>'.__('year','usces').'
					</td>
				</tr>';
			}

			$html_paytype = '';
			if( ( usces_have_regular_order() || usces_have_continue_charge() ) && usces_is_login() ) {
				$html_paytype .= '<input type="hidden" id="paytype" value="01" />';

			} else {
				if( 1 === (int)$acting_opts['howtopay'] ) {
				//	$html_paytype .= '
				//<tr>
				//	<th scope="row">'.__('Number of payments','usces').'</th>
				//	<td colspan="2">'.__('Single payment only','usces').'
				//		<input type="hidden" id="paytype" value="01" />
				//	</td>
				//</tr>';
					$html_paytype .= '<input type="hidden" id="paytype" value="01" />';

				} elseif( 2 <= $acting_opts['howtopay'] ) {
					$cardfirst4 = ( 'OK' == $response_member['ResponseCd'] ) ? '<input type="hidden" id="cardno" value="'.substr($response_member['CardNo'], 0, 4).'" />' : '';//先頭4桁
					$html_paytype .= '
				<tr>
					<th scope="row">'.__('Number of payments','usces').'</th>
					<td colspan="2">'.$cardfirst4.'<div class="paytype">';

					$html_paytype .= '
						<select id="paytype_default" >
							<option value="01">'.__('One time payment','usces').'</option>
						</select>';

					$html_paytype .= '
						<select id="paytype4535" style="display:none;" disabled="disabled" >
							<option value="01">1'.__('-time payment','usces').'</option>
							<option value="02">2'.__('-time payment','usces').'</option>
							<option value="03">3'.__('-time payment','usces').'</option>
							<option value="05">5'.__('-time payment','usces').'</option>
							<option value="06">6'.__('-time payment','usces').'</option>
							<option value="10">10'.__('-time payment','usces').'</option>
							<option value="12">12'.__('-time payment','usces').'</option>
							<option value="15">15'.__('-time payment','usces').'</option>
							<option value="18">18'.__('-time payment','usces').'</option>
							<option value="20">20'.__('-time payment','usces').'</option>
							<option value="24">24'.__('-time payment','usces').'</option>
							<option value="88">'.__('Libor Funding pay','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80">'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '
						<select id="paytype37" style="display:none;" disabled="disabled" >
							<option value="01">1'.__('-time payment','usces').'</option>
							<option value="03">3'.__('-time payment','usces').'</option>
							<option value="05">5'.__('-time payment','usces').'</option>
							<option value="06">6'.__('-time payment','usces').'</option>
							<option value="10">10'.__('-time payment','usces').'</option>
							<option value="12">12'.__('-time payment','usces').'</option>
							<option value="15">15'.__('-time payment','usces').'</option>
							<option value="18">18'.__('-time payment','usces').'</option>
							<option value="20">20'.__('-time payment','usces').'</option>
							<option value="24">24'.__('-time payment','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80">'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '
						<select id="paytype36" style="display:none;" disabled="disabled" >
							<option value="01">'.__('One time payment','usces').'</option>
							<option value="88">'.__('Libor Funding pay','usces').'</option>';
					if( 3 == $acting_opts['howtopay'] ) {
						$html_paytype .= '
							<option value="80">'.__('Bonus lump-sum payment','usces').'</option>';
					}
					$html_paytype .= '
						</select>';

					$html_paytype .= '</div>
					</td>
				</tr>';
				}
			}
			$html .= apply_filters( 'usces_filter_escott_secure_form_paytype_token', $html_paytype );
			$html .= '
			</table>';
			$quick = ( 'OK' == $response_member['ResponseCd'] ) ? 'quick' : '';
			die("OK#usces#".$html."#usces#".$quick);
			break;

		case 'escott_set_token':
			if( !wp_verify_nonce( $_POST['wc_nonce'], 'wc_delivery_secure_nonce' ) ) {
				wp_redirect(USCES_CART_URL);
			}

			$acting_opts = $this->get_acting_settings();
			$html = '';

			die("OK#usces#".$html);
			break;
		}
	}

	/**********************************************
	* usces_filter_cod_label
	* 手数料ラベル
	* @param  $label
	* @return string $label
	***********************************************/
	public function set_fee_label( $label ) {
		global $usces;

		if( is_admin() ) {
			$order_id = ( isset($_REQUEST['order_id']) ) ? $_REQUEST['order_id'] : '';
			if( !empty($order_id) ) {
				$order_data = $usces->get_order_data( $order_id, 'direct' );
				$payment = usces_get_payments_by_name( $order_data['order_payment_name'] );
				if( $this->acting_flg_conv == $payment['settlement'] || $this->acting_flg_atodene == $payment['settlement'] ) {
					$label = $payment['name'].__('Fee','usces');
				}
			}
		} else {
			$usces_entries = $usces->cart->get_entry();
			$payment = $usces->getPayments( $usces_entries['order']['payment_name'] );
			if( $this->acting_flg_conv == $payment['settlement'] || $this->acting_flg_atodene == $payment['settlement'] ) {
				$label = $payment['name'].__('Fee','usces');
			}
		}
		return $label;
	}

	/**********************************************
	* usces_filter_member_history_cod_label
	* 手数料ラベル
	* @param  $label $order_id
	* @return string $label
	***********************************************/
	public function set_member_history_fee_label( $label, $order_id ) {
		global $usces;

		$order_data = $usces->get_order_data( $order_id, 'direct' );
		$payment = usces_get_payments_by_name( $order_data['order_payment_name'] );
		if( $this->acting_flg_conv == $payment['settlement'] || $this->acting_flg_atodene == $payment['settlement'] ) {
			$label = $payment['name'].__('Fee','usces');
		}
		return $label;
	}

	/**********************************************
	* usces_fiter_the_payment_method
	* 支払方法
	* @param  $payments
	* @return array $payments
	***********************************************/
	public function payment_method( $payments ) {

		$conv_exclusion = false;

		if( usces_have_regular_order() ) {
			$conv_exclusion = true;

		} elseif( usces_have_continue_charge() ) {
			$conv_exclusion = true;
		}

		if( $conv_exclusion ) {
			foreach( $payments as $key => $payment ) {
				if( $this->acting_flg_conv == $payment['settlement'] ) {
					unset( $payments[$key] );
				}
			}
		}

		return $payments;
	}

	/**********************************************
	* usces_filter_set_cart_fees_cod
	* 決済手数料
	* @param  $cod_fee $usces_entries $total_items_price $use_point $discount $shipping_charge $amount_by_cod
	* @return float $cod_fee
	***********************************************/
	public function add_fee( $cod_fee, $usces_entries, $total_items_price, $use_point, $discount, $shipping_charge, $amount_by_cod ) {
		global $usces;

		$payment = usces_get_payments_by_name( $usces_entries['order']['payment_name'] );
		if( $this->acting_flg_conv != $payment['settlement'] && $this->acting_flg_atodene != $payment['settlement'] ) {
			return $cod_fee;
		}

		$acting_opts = $this->get_acting_settings();
		$acting = explode( '_', $payment['settlement'] );
		$fee = 0;
		if( 'fix' == $acting_opts[$acting[2].'_fee_type'] ) {
			$fee = (int)$acting_opts[$acting[2].'_fee'];
		} else {
			$materials = array(
				'total_items_price' => $usces_entries['order']['total_items_price'],
				'discount' => $usces_entries['order']['discount'],
				'shipping_charge' => $usces_entries['order']['shipping_charge'],
				'cod_fee' => $usces_entries['order']['cod_fee']
			);
			$items_price = $total_items_price - $discount;
			$price = $items_price + $usces->getTax( $items_price, $materials );
			if( $price <= (int)$acting_opts[$acting[2].'_fee_first_amount'] ) {
				$fee = $acting_opts[$acting[2].'_fee_first_fee'];
			} elseif( isset($acting_opts[$acting[2].'_fee_amounts']) && !empty($acting_opts[$acting[2].'_fee_amounts']) ) {
				$last = count( $acting_opts[$acting[2].'_fee_amounts'] ) - 1;
				if( $price > $acting_opts[$acting[2].'_fee_amounts'][$last] ) {
					$fee = $acting_opts[$acting[2].'_fee_end_fee'];
				} else {
					foreach( $acting_opts[$acting[2].'_fee_amounts'] as $key => $value ) {
						if( $price <= $value ) {
							$fee = $acting_opts[$acting[2].'_fee_fees'][$key];
							break;
						}
					}
				}
			} else {
				$fee = $acting_opts[$acting[2].'_fee_end_fee'];
			}
		}
		return $cod_fee + $fee;
	}

	/**********************************************
	* usces_filter_delivery_check usces_filter_point_check_last
	* 決済手数料チェック
	* @param  $mes
	* @return string $mes
	***********************************************/
	public function check_fee_limit( $mes ) {
		global $usces;

		$member = $usces->get_member();
		$usces->set_cart_fees( $member, array() );
		$usces_entries = $usces->cart->get_entry();
		$payment = usces_get_payments_by_name( $usces_entries['order']['payment_name'] );
		if( $this->acting_flg_conv != $payment['settlement'] && $this->acting_flg_atodene != $payment['settlement'] ) {
			return $mes;
		}

		if( 2 == $usces_entries['delivery']['delivery_flag'] ) {
			$mes .= sprintf( __("If you specify multiple shipping address, you cannot use '%s' payment method.",'usces'), $usces_entries['order']['payment_name'] );
			return $mes;
		}

		$acting_opts = $this->get_acting_settings();
		$fee_limit_amount = 0;
		switch( $payment['settlement'] ) {
		case $this->acting_flg_conv:
			if( 'fix' == $acting_opts['conv_fee_type'] ) {
				$fee_limit_amount = $acting_opts['conv_fee_limit_amount'];
			}
			break;

		case $this->acting_flg_atodene:
			if( 'fix' == $acting_opts['atodene_fee_type'] ) {
				$fee_limit_amount = $acting_opts['atodene_fee_first_amount'];
			}
			break;
		}

		if( 0 < $fee_limit_amount && $usces_entries['order']['total_full_price'] > $fee_limit_amount ) {
			$mes .= sprintf( __('It exceeds the maximum amount of "%1$s" (total amount %2$s).', 'usces'), $usces_entries['order']['payment_name'], usces_crform( $fee_limit_amount, true, false, 'return', true ) );
		}

		return $mes;
	}

	/**********************************************
	* 決済オプション取得
	* @param  -
	* @return array $acting_settings
	***********************************************/
	protected function get_acting_settings() {
		global $usces;

		$acting_settings = ( isset($usces->options['acting_settings'][$this->paymod_id]) ) ? $usces->options['acting_settings'][$this->paymod_id] : array();
		return $acting_settings;
	}

	/**********************************************
	* 処理日付生成
	* @param  -
	* @return date 'YYYYMMDD'
	***********************************************/
	protected function get_transaction_date() {

		$transactiondate = date_i18n( 'Ymd', current_time('timestamp') );
		return $transactiondate;
	}

	/**********************************************
	* e-SCOTT 会員ID取得
	* @param  $member_id
	* @return string $escott_member_id
	***********************************************/
	public function get_quick_kaiin_id( $member_id ) {
		global $usces;

		if( empty($member_id) ) {
			return false;
		}

		$escott_member_id = $usces->get_member_meta_value( $this->quick_key_pre.'_member_id', $member_id );
		return $escott_member_id;
	}

	/**********************************************
	* e-SCOTT 会員パスワード取得
	* @param  $member_id
	* @return string $escott_member_passwd
	***********************************************/
	public function get_quick_pass( $member_id ) {
		global $usces;

		if( empty($member_id) ) {
			return false;
		}

		$escott_member_passwd = $usces->get_member_meta_value( $this->quick_key_pre.'_member_passwd', $member_id );
		return $escott_member_passwd;
	}

	/**********************************************
	* e-SCOTT 会員ID生成
	* @param  $member_id
	* @return string KaiinId
	***********************************************/
	public function make_kaiin_id( $member_id ) {

		$digit = 11 - strlen( $member_id );
		$num = str_repeat( "9", $digit );
		$id = sprintf( '%0'.$digit.'d', mt_rand( 1, (int)$num ) );
		return 'w'.$member_id.'i'.$id;
	}

	/**********************************************
	* e-SCOTT 会員パスワード生成
	* @param  -
	* @return string KaiinPass
	***********************************************/
	public function make_kaiin_pass() {

		$passwd = sprintf( '%012d', mt_rand() );
		return $passwd;
	}

	/**********************************************
	* e-SCOTT 会員情報登録・更新
	* @param  ($param_list)
	* @return array $response_member
	***********************************************/
	public function escott_member_process( $param_list = array() ) {
		global $usces;

		$member = $usces->get_member();
		$acting_opts = $this->get_acting_settings();
		$params = array();
		$params['send_url'] = $acting_opts['send_url_member'];
		$params['param_list'] = $param_list;

		$response_member = array( 'ResponseCd'=>'' );
		$KaiinId = $this->get_quick_kaiin_id( $member['ID'] );
		$KaiinPass = $this->get_quick_pass( $member['ID'] );

		if( empty( $KaiinId ) || empty( $KaiinPass ) ) {
			$KaiinId = $this->make_kaiin_id( $member['ID'] );
			$KaiinPass = $this->make_kaiin_pass();
			$params['param_list']['OperateId'] = '4MemAdd';
			$params['param_list']['KaiinId'] = $KaiinId;
			$params['param_list']['KaiinPass'] = $KaiinPass;
			if( !isset($param_list['Token']) && isset($_POST['cardno']) && isset($_POST['expyy']) && isset($_POST['expmm']) ) {
				$params['param_list']['CardNo'] = trim($_POST['cardno']);
				$params['param_list']['CardExp'] = substr($_POST['expyy'],2).$_POST['expmm'];
				if( 'on' == $acting_opts['seccd'] && isset($_POST['seccd']) ) {
					$params['param_list']['SecCd'] = trim($_POST['seccd']);
				}
			}
			//e-SCOTT 新規会員登録
			$response_member = $this->connection( $params );
			if( 'OK' == $response_member['ResponseCd'] ) {
				$usces->set_member_meta_value( $this->quick_key_pre.'_member_id', $KaiinId, $member['ID'] );
				$usces->set_member_meta_value( $this->quick_key_pre.'_member_passwd', $KaiinPass, $member['ID'] );
				$response_member['KaiinId'] = $KaiinId;
				$response_member['KaiinPass'] = $KaiinPass;
				$response_member['use_token'] = true;
			}

		} else {
			$params['param_list']['OperateId'] = '4MemRefM';
			$params['param_list']['KaiinId'] = $KaiinId;
			$params['param_list']['KaiinPass'] = $KaiinPass;
			//e-SCOTT 会員照会
			$response_member = $this->connection( $params );
			if( 'OK' == $response_member['ResponseCd'] ) {
				if( isset($_POST['card_change']) && '1' == $_POST['card_change'] ) {
					$params['param_list']['OperateId'] = '4MemChg';
					if( !isset($param_list['Token']) && isset($_POST['cardno']) && '8888888888888888' != $_POST['cardno'] && isset($_POST['expyy']) && isset($_POST['expmm']) ) {
						$params['param_list']['CardNo'] = trim($_POST['cardno']);
						$params['param_list']['CardExp'] = substr($_POST['expyy'],2).$_POST['expmm'];
						if( 'on' == $acting_opts['seccd'] && isset($_POST['seccd']) ) {
							$params['param_list']['SecCd'] = trim($_POST['seccd']);
						}
					}
					//e-SCOTT 会員更新
					$response_member = $this->connection( $params );
					$response_member['KaiinId'] = $KaiinId;
					$response_member['KaiinPass'] = $KaiinPass;
					$response_member['use_token'] = true;
				} else {
					$response_member['KaiinId'] = $KaiinId;
					$response_member['KaiinPass'] = $KaiinPass;
					$response_member['use_token'] = false;
				}
			}
		}
		return $response_member;
	}

	/**********************************************
	* e-SCOTT 会員情報登録
	* @param  $member_id
	* @return array $response_member
	***********************************************/
	public function escott_member_register( $member_id ) {
		global $usces;

		$response_member = array( 'ResponseCd'=>'' );
		$acting_opts = $this->get_acting_settings();
		$TransactionDate = $this->get_transaction_date();
		$param_list = array();
		$params = array();

		$KaiinId = $this->make_kaiin_id( $member_id );
		$KaiinPass = $this->make_kaiin_pass();

		//共通部
		$param_list['MerchantId'] = $acting_opts['merchant_id'];
		$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
		$param_list['TransactionDate'] = $TransactionDate;
		$param_list['MerchantFree3'] = $this->merchantfree3;
		$param_list['TenantId'] = $acting_opts['tenant_id'];

		$token = ( isset($_POST['token']) ) ? trim($_POST['token']) : '';
		if( !empty($token) ) {
			$param_list['Token'] = $token;
			$param_list['OperateId'] = '1TokenSearch';
			$params['send_url'] = $acting_opts['send_url_token'];
			$params['param_list'] = $param_list;
			//e-SCOTT トークンステータス参照
			$response_token = $this->connection( $params );
			if( 'OK' != $response_token['ResponseCd'] || 'OK' != $response_token['TokenResponseCd'] ) {
				$tokenresponsecd = '';
				$responsecd = explode( '|', $response_token['ResponseCd'].'|'.$response_token['TokenResponseCd'] );
				foreach( (array)$responsecd as $cd ) {
					if( 'OK' != $cd ) {
						$response_token[$cd] = $this->response_message( $cd );
						$tokenresponsecd .= $cd.'|';
					}
				}
				$response_token['ResponseCd'] = rtrim($tokenresponsecd,'|');
				return $response_token;
			}
			unset($params['param_list']);

		} else {
			$param_list['CardNo'] = trim($_POST['cardno']);
			$param_list['CardExp'] = substr($_POST['expyy'],2).$_POST['expmm'];
			if( 'on' == $acting_opts['seccd'] && !empty($_POST['seccd']) ) {
				$param_list['SecCd'] = trim($_POST['seccd']);
			}
		}

		$params['send_url'] = $acting_opts['send_url_member'];
		$params['param_list'] = array_merge( $param_list,
			array(
				'OperateId' => '4MemAdd',
				'KaiinId' => $KaiinId,
				'KaiinPass' => $KaiinPass
			)
		);
		//e-SCOTT 新規会員登録
		$response_member = $this->connection( $params );
		if( 'OK' == $response_member['ResponseCd'] ) {
			$usces->set_member_meta_value( $this->quick_key_pre.'_member_id', $KaiinId, $member_id );
			$usces->set_member_meta_value( $this->quick_key_pre.'_member_passwd', $KaiinPass, $member_id );
			$response_member['KaiinId'] = $KaiinId;
			$response_member['KaiinPass'] = $KaiinPass;
		}
		return $response_member;
	}

	/**********************************************
	* e-SCOTT 会員情報更新
	* @param  $member_id
	* @return array $response_member
	***********************************************/
	public function escott_member_update( $member_id ) {

		$response_member = array( 'ResponseCd'=>'' );
		$KaiinId = $this->get_quick_kaiin_id( $member_id );
		$KaiinPass = $this->get_quick_pass( $member_id );

		if( $KaiinId && $KaiinPass ) {
			$acting_opts = $this->get_acting_settings();
			$TransactionDate = $this->get_transaction_date();
			$param_list = array();
			$params = array();

			//共通部
			$param_list['MerchantId'] = $acting_opts['merchant_id'];
			$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
			$param_list['TransactionDate'] = $TransactionDate;
			$param_list['MerchantFree3'] = $this->merchantfree3;
			$param_list['TenantId'] = $acting_opts['tenant_id'];

			$token = ( isset($_POST['token']) ) ? trim($_POST['token']) : '';
			if( !empty($token) ) {
				$param_list['Token'] = $token;
			} else {
				if( !empty($_POST['cardno']) ) {
					$param_list['CardNo'] = trim($_POST['cardno']);
				}
				if( 'on' == $acting_opts['seccd'] && !empty($_POST['seccd']) ) {
					$param_list['SecCd'] = trim($_POST['seccd']);
				}
				if( !empty($_POST['expyy']) && !empty($_POST['expmm']) ) {
					$param_list['CardExp'] = substr($_POST['expyy'],2).$_POST['expmm'];
				}
			}

			$params['send_url'] = $acting_opts['send_url_member'];
			$params['param_list'] = array_merge( $param_list,
				array(
					'OperateId' => '4MemChg',
					'KaiinId' => $KaiinId,
					'KaiinPass' => $KaiinPass
				)
			);
			//e-SCOTT 会員更新
			$response_member = $this->connection( $params );
			if( 'OK' != $response_member['ResponseCd'] ) {
				usces_log('['.$this->acting_name.'] 4MemChg NG : '.print_r($response_member,true), 'acting_transaction.log');
			}
		}
		return $response_member;
	}

	/**********************************************
	* e-SCOTT 会員情報削除
	* @param  $member_id
	* @return array $response_member
	***********************************************/
	public function escott_member_delete( $member_id ) {
		global $usces;

		$response_member = array( 'ResponseCd'=>'' );
		$KaiinId = $this->get_quick_kaiin_id( $member_id );
		$KaiinPass = $this->get_quick_pass( $member_id );

		if( $KaiinId && $KaiinPass ) {
			$acting_opts = $this->get_acting_settings();
			$TransactionDate = $this->get_transaction_date();
			$param_list = array();
			$params = array();

			//共通部
			$param_list['MerchantId'] = $acting_opts['merchant_id'];
			$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
			$param_list['TransactionDate'] = $TransactionDate;
			$param_list['MerchantFree3'] = $this->merchantfree3;
			$param_list['TenantId'] = $acting_opts['tenant_id'];
			$params['send_url'] = $acting_opts['send_url_member'];
			$params['param_list'] = array_merge( $param_list,
				array(
					'OperateId' => '4MemInval',
					'KaiinId' => $KaiinId,
					'KaiinPass' => $KaiinPass
				)
			);
			//e-SCOTT 会員無効
			$response_member = $this->connection( $params );
			if( 'OK' == $response_member['ResponseCd'] ) {
				$params['param_list'] = array_merge( $param_list,
					array(
						'OperateId' => '4MemDel',
						'KaiinId' => $KaiinId,
						'KaiinPass' => $KaiinPass
					)
				);
				//e-SCOTT 会員削除
				$response_member = array( 'ResponseCd'=>'' );
				$response_member = $this->connection( $params );
				if( 'OK' == $response_member['ResponseCd'] ) {
					$usces->del_member_meta( $this->quick_key_pre.'_member_id', $member_id );
					$usces->del_member_meta( $this->quick_key_pre.'_member_passwd', $member_id );
				} else {
					usces_log('['.$this->acting_name.'] 4MemDel NG : '.print_r($response_member,true), 'acting_transaction.log');
				}
			} else {
				usces_log('['.$this->acting_name.'] 4MemInval NG : '.print_r($response_member,true), 'acting_transaction.log');
			}
		}
		return $response_member;
	}

	/**********************************************
	* e-SCOTT 会員情報照会
	* @param  $member_id ($KaiinId) ($KaiinPass)
	* @return array $response_member
	***********************************************/
	public function escott_member_reference( $member_id, $KaiinId = '', $KaiinPass = '' ) {

		$response_member = array( 'ResponseCd'=>'' );
		if( empty($KaiinId) ) {
			$KaiinId = $this->get_quick_kaiin_id( $member_id );
		}
		if( empty($KaiinPass) ) {
			$KaiinPass = $this->get_quick_pass( $member_id );
		}

		if( $KaiinId && $KaiinPass ) {
			$acting_opts = $this->get_acting_settings();
			$TransactionDate = $this->get_transaction_date();
			$param_list = array();
			$params = array();

			//共通部
			$param_list['MerchantId'] = $acting_opts['merchant_id'];
			$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
			$param_list['TransactionDate'] = $TransactionDate;
			$param_list['MerchantFree3'] = $this->merchantfree3;
			$param_list['TenantId'] = $acting_opts['tenant_id'];
			$params['send_url'] = $acting_opts['send_url_member'];
			$params['param_list'] = array_merge( $param_list,
				array(
					'OperateId' => '4MemRefM',
					'KaiinId' => $KaiinId,
					'KaiinPass' => $KaiinPass
				)
			);
			//e-SCOTT 会員照会
			$response_member = $this->connection( $params );
			if( 'OK' == $response_member['ResponseCd'] ) {
				$response_member['KaiinId'] = $KaiinId;
				$response_member['KaiinPass'] = $KaiinPass;
			}
		}
		return $response_member;
	}

	/**********************************************
	* e-SCOTT トークン検索
	* @param  $token
	* @return array $response_token
	***********************************************/
	public function escott_token_search( $token ) {

		$acting_opts = $this->get_acting_settings();
		$TransactionDate = $this->get_transaction_date();
		$param_list = array();
		$params = array();

		//共通部
		$param_list['MerchantId'] = $acting_opts['merchant_id'];
		$param_list['MerchantPass'] = $acting_opts['merchant_pass'];
		$param_list['TransactionDate'] = $TransactionDate;
		$param_list['MerchantFree3'] = $this->merchantfree3;
		$param_list['TenantId'] = $acting_opts['tenant_id'];
		$params['send_url'] = $acting_opts['send_url_token'];
		$params['param_list'] = array_merge( $param_list,
			array(
				'OperateId' => '1TokenSearch',
				'Token' => $token
			)
		);

		//e-SCOTT トークンステータス参照
		$response_token = $this->connection( $params );
		if( 'OK' != $response_token['ResponseCd'] || 'OK' != $response_token['TokenResponseCd'] ) {
			$tokenresponsecd = '';
			$responsecd = explode( '|', $response_token['ResponseCd'].'|'.$response_token['TokenResponseCd'] );
			foreach( (array)$responsecd as $cd ) {
				if( 'OK' != $cd ) {
					$response_token[$cd] = $this->response_message( $cd );
					$tokenresponsecd .= $cd.'|';
				}
			}
			$tokenresponsecd = rtrim($tokenresponsecd,'|');
		}
		return $response_token;
	}

	/**********************************************
	* 処理区分名称
	* @param  $OperateId
	* @return $operate_name
	***********************************************/
	protected function get_operate_name( $OperateId ) {

		$operate_name = '';
		switch( $OperateId ) {
		case '1Check'://カードチェック
			$operate_name = __('Card check','usces');
			break;
		case '1Auth'://与信
			$operate_name = __('Credit','usces');
			break;
		case '1Capture'://売上計上
			$operate_name = __('Sales recorded','usces');
			break;
		case '1Gathering'://与信売上計上
			$operate_name = __('Credit sales','usces');
			break;
		case '1Change'://利用額変更
			$operate_name = __('Change spending amount','usces');
			break;
		case '1Delete'://取消
			$operate_name = __('Unregister','usces');
			break;
		case '1Search'://取引参照
			$operate_name = __('Transaction reference','usces');
			break;
		case '1ReAuth'://再オーソリ
			$operate_name = __('Re-authorization','usces');
			break;
		case '2Add'://登録
			$operate_name = __('Register');
			break;
		case '2Chg'://変更
			$operate_name = __('Change');
			break;
		case '2Del'://削除
			$operate_name = __('Unregister','usces');
			break;
		case '5Auth'://外貨与信
			$operate_name = __('Foreign currency credit','usces');
			break;
		case '5Gathering'://外貨与信売上確定
			$operate_name = __('Foreign currency credit sales confirmed','usces');
			break;
		case '5Capture'://外貨売上確定
			$operate_name = __('Foreign currency sales fixed','usces');
			break;
		case '5Delete'://外貨取消
			$operate_name = __('Foreign currency cancellation','usces');
			break;
		case '5OpeUnInval'://外貨取引保留解除
			$operate_name = __('Withdrawal of foreign currency transactions','usces');
			break;
		case 'receipted'://入金
			$operate_name = __('Payment','usces');
			break;
		case 'expiration'://期限切れ
			$operate_name = __('Expired','usces');
			break;
		}
		return $operate_name;
	}

	/**********************************************
	* 収納機関名称
	* @param  $CvsCd
	* @return $cvs_name
	***********************************************/
	protected function get_cvs_name( $CvsCd ) {

		$cvs_name = '';
		switch( trim($CvsCd) ) {
		case 'LSN':
			$cvs_name = 'ローソン';
			break;
		case 'FAM':
			$cvs_name = 'ファミリーマート';
			break;
		case 'SAK':
			$cvs_name = 'サンクス';
			break;
		case 'CCK':
			$cvs_name = 'サークルK';
			break;
		case 'ATM':
			$cvs_name = 'Pay-easy（ATM）';
			break;
		case 'ONL':
			$cvs_name = 'Pay-easy（オンライン）';
			break;
		case 'LNK':
			$cvs_name = 'Pay-easy（情報リンク）';
			break;
		case 'SEV':
			$cvs_name = 'セブンイレブン';
			break;
		case 'MNS':
			$cvs_name = 'ミニストップ';
			break;
		case 'DAY':
			$cvs_name = 'デイリーヤマザキ';
			break;
		case 'EBK':
			$cvs_name = '楽天銀行';
			break;
		case 'JNB':
			$cvs_name = 'ジャパンネット銀行';
			break;
		case 'EDY':
			$cvs_name = 'Edy';
			break;
		case 'SUI':
			$cvs_name = 'Suica';
			break;
		case 'FFF':
			$cvs_name = 'スリーエフ';
			break;
		case 'JIB':
			$cvs_name = 'じぶん銀行';
			break;
		case 'SNB':
			$cvs_name = '住信SBIネット銀行';
			break;
		case 'SCM':
			$cvs_name = 'セイコーマート';
			break;
		}
		return $cvs_name;
	}

	/**********************************************
	* 手数料名称
	* @param  $fee_type
	* @return string $fee_name
	***********************************************/
	protected function get_fee_name( $fee_type ) {

		$fee_name = '';
		if( 'fix' == $fee_type ) {
			$fee_name = __('Fixation','usces');
		} elseif( 'change' == $fee_type ) {
			$fee_name = __('Variable','usces');
		}
		return $fee_name;
	}

	/**********************************************
	* エラーコード対応メッセージ
	* @param  $code
	* @return string $message
	***********************************************/
	public function response_message( $code ) {

		switch( $code ) {
		case 'K01'://当該 OperateId の設定値を網羅しておりません。（送信項目不足、または項目エラー）設定値をご確認の上、再処理行ってください。
			$message = 'オンライン取引電文精査エラー';
			break;
		case 'K02'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「MerchantId」精査エラー';
			break;
		case 'K03'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「MerchantPass」精査エラー';
			break;
		case 'K04'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「TenantId」精査エラー';
			break;
		case 'K05'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「TransactionDate」精査エラー';
			break;
		case 'K06'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「OperateId」精査エラー';
			break;
		case 'K07'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「MerchantFree1」精査エラー';
			break;
		case 'K08'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「MerchantFree2」精査エラー';
			break;
		case 'K09'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「MerchantFree3」精査エラー';
			break;
		case 'K10'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「ProcessId」精査エラー';
			break;
		case 'K11'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「ProcessPass」精査エラー';
			break;
		case 'K12'://Master 電文で発行された「ProcessId」または「ProcessPass」では無いことを意味します。設定値をご確認の上、再処理行ってください。
			$message = '項目「ProcessId」または「ProcessPass」不整合エラー';
			break;
		case 'K14'://要求された Process 電文の「OperateId」が要求対象外です。例：「1Delete：取消」に対して再度「1Delete：取消」を送信したなど。
			$message = 'OperateId のステータス遷移不整合';
			break;
		case 'K15'://返戻対象となる会員の数が、最大件（30 件）を超えました。
			$message = '会員参照（同一カード番号返戻）時の返戻対象会員数エラー';
			break;
		case 'K20'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「CardNo」精査エラー';
			break;
		case 'K21'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「CardExp」精査エラー';
			break;
		case 'K22'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「PayType」精査エラー';
			break;
		case 'K23'://半角数字ではないことまたは、利用額変更で元取引と金額が同一となっていることを意味します。 8桁以下 (0 以外 )の半角数字であること、利用額変更で元取引と金額が同一でないことをご確認の上、再処理を行ってください。
			$message = '項目「Amount」精査エラー';
			break;
		case 'K24'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「SecCd」精査エラー';
			break;
		case 'K28'://オンライン収納で「半角数字ハイフン≦13桁では無い」設定値を確認の上、再処理を行ってください。
			$message = '項目「TelNo」精査エラー';
			break;
		case 'K39'://YYYMMDD形式では無い、または未来日付あることを意味します。設定値をご確認の上、再処理を行ってください。
			$message = '項目「SalesDate」精査エラー';
			break;
		case 'K45'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「KaiinId」精査エラー';
			break;
		case 'K46'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「KaiinPass」精査エラー';
			break;
		case 'K47'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「NewKaiinPass」精査エラー';
			break;
		case 'K50'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「PayLimit」精査エラー';
			break;
		case 'K51'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「NameKanji」精査エラー';
			break;
		case 'K52'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「NameKana」精査エラー';
			break;
		case 'K53'://形式エラーです。 設定値をご確認の上、再処理を行ってください。
			$message = '項目「ShouhinName」精査エラー';
			break;
		case 'K68'://会員登録機能が未設定となっております。
			$message = '会員の登録機能は利用できません';
			break;
		case 'K69'://この会員ID はすでに使用されています。
			$message = '会員ID の重複エラー';
			break;
		case 'K70'://会員削除電文に対して会員が無効状態ではありません。
			$message = '会員が無効状態ではありません';
			break;
		case 'K71'://会員ID・パスワードが一致しません。
			$message = '会員ID の認証エラー';
			break;
		case 'K73'://会員無効解除電文に対して会員が既に有効となっています。
			$message = '会員が既に有効となっています';
			break;
		case 'K74'://会員認証に連続して失敗し、ロックアウトされました。
			$message = '会員認証に連続して失敗し、ロックアウトされました';
			break;
		case 'K75'://会員は有効でありません。
			$message = '会員は有効でありません';
			break;
		case 'K79'://現在は Login 無効または会員無効状態です。
			$message = '会員判定エラー（Login 無効または会員無効）';
			break;
		case 'K80'://Master 電文は会員ID が設定されています。Process 電文も会員ID を設定してください。
			$message = '会員ID 設定不一致（設定が必要）';
			break;
		case 'K81'://Master 電文は会員 ID が未設定です。Process 電文の会員ID も未設定としてください。
			$message = '会員ID 設定不一致（設定が不要）';
			break;
		case 'K82'://カード番号が適切ではありません。
			$message = 'カード番号の入力内容不正';
			break;
		case 'K83'://カード有効期限が適切ではありません。
			$message = 'カード有効期限の入力内容不正';
			break;
		case 'K84'://会員ID が適切ではありません。
			$message = '会員ID の入力内容不正';
			break;
		case 'K85'://会員パスワードが適切ではありません。
			$message = '会員パスワードの入力内容不正';
			break;
		case 'K88'://取引の対象が複数件存在します。弊社までお問い合わせください。
			$message = '元取引重複エラー';
			break;
		case 'K96'://障害報が通知されている場合は、回復報を待って再処理を行ってください。その他は、弊社までお問い合わせください。
			$message = '本システム通信障害発生（タイムアウト）';
			break;
		case 'K98'://障害報が通知されている場合は、回復報を待って再処理を行ってください。その他は、弊社までお問い合わせください。
			$message = '本システム内部で軽度障害が発生';
			break;
		case 'K99'://弊社までお問い合わせください。
			$message = 'その他例外エラー';
			break;
		case 'KG8'://マーチャントID、マーチャントパスワド認証に連続して失敗し、ロックアウトされました。
			$message = '事業者認証に連続して失敗し、ロックアウトされました';
			break;
		case 'KGH'://会員参照の利用は制限されています。
			$message = '会員参照電文利用設定エラー ';
			break;
		case 'KHX'://形式エラー。設定値を確認の上、再処理を行ってください。
			$message = '項目「Token」精査エラー';
			break;
		case 'KHZ'://利用可能なトークンがありません。
			$message = '利用可能トークンなしエラー';
			break;
		case 'KI1'://形式エラー。設定値を確認の上、再処理を行ってください。
			$message = '項目「k_TokenNinsyoCode」精査エラー';
			break;
		case 'KI2'://すでに利用されたトークンです。
			$message = '使用済みトークンエラー';
			break;
		case 'KI3'://トークンの有効期限が切れています。
			$message = 'トークン有効期限切れエラー';
			break;
		case 'KI4'://形式エラー。設定値を確認の上、再処理を行ってください。
			$message = '項目「端末情報」精査エラー';
			break;
		case 'KI5'://同一カード番号の連続入力によりロックされています。
			$message = '同一カード番号の連続入力によりロックされています。';
			break;
		case 'KI6'://同一端末からの連続入力により端末がロックされています。
			$message = '同一端末からの連続入力により端末がロックされています。';
			break;
		case 'KI8'://取引の対象が複数件存在します。 
			$message = '取引の対象が複数件存在します。 ';
			break;
		case 'C01'://貴社送信内容が仕様に沿っているかご確認の上、弊社までお問い合わせください。
			$message = '弊社設定関連エラー';
			break;
		case 'C02'://障害報が通知されている場合は、回復報を待って再処理を行ってください。その他は、弊社までお問い合わせください。
			$message = 'e-SCOTT システムエラー';
			break;
		case 'C03'://障害報が通知されている場合は、回復報を待って再処理を行ってください。その他は、弊社までお問い合わせください。
			$message = 'e-SCOTT 通信エラー';
			break;
		case 'C10'://ご契約のある支払回数（区分）をセットし再処理行ってください。
			$message = '支払区分エラー';
			break;
		case 'C11'://ボーナス払いご利用対象外期間のため、支払区分を変更して再処理を行ってください。
			$message = 'ボーナス期間外エラー';
			break;
		case 'C12'://ご契約のある分割回数（区分）をセットし再処理行ってください。
			$message = '分割回数エラー';
			break;
		case 'C13'://カード有効期限の年月入力間違え。または、有効期限切れカードです。
			$message = '有効期限切れエラー';
			break;
		case 'C14'://取消処理が既に行われております。管理画面で処理状況をご確認ください。
			$message = '取消済みエラー';
			break;
		case 'C15'://ボーナス払いの下限金額未満によるエラーのため、支払方法を変更して再処理を行ってください。
			$message = 'ボーナス金額下限エラー';
			break;
		case 'C16'://該当のカード会員番号は存在しない。
			$message = 'カード番号エラー';
			break;
		case 'C17'://ご契約範囲外のカード番号。もしくは存在しないカード番号体系。
			$message = 'カード番号体系エラー';
			break;
		case 'C18'://オーソリ除外となるカード番号。本エラーを発生するには個別に設定が必要になります。弊社までお問い合わせください。
			$message = 'オーソリ除外対象のカード番号体系エラー';
			break;
		case 'C70'://貴社送信内容が仕様に沿っているかご確認の上、弊社までお問い合わせください。
			$message = '弊社設定情報エラー';
			break;
		case 'C71'://貴社送信内容が仕様に沿っているかご確認の上、弊社までお問い合わせください。
			$message = '弊社設定情報エラー';
			break;
		case 'C80'://カード会社システムの停止を意味します。
			$message = 'カード会社センター閉局';
			break;
		case 'C98'://貴社送信内容が仕様に沿っているかご確認の上、弊社までお問い合わせください。
			$message = 'その他例外エラー';
			break;
		case 'G12'://クレジットカードが使用不可能です。
			$message = 'カード使用不可';
			break;
		case 'G22'://支払永久禁止を意味します。
			$message = '"G22" が設定されている';
			break;
		case 'G30'://取引の判断保留を意味します。
			$message = '取引判定保留';
			break;
		case 'G42'://暗証番号が正しくありません。※デビットカードの場合、発生するがあります。
			$message = '暗証番号エラー';
			break;
		case 'G44'://入力されたセキュリティコードが正しくありません。
			$message = 'セキュリティコード誤り';
			break;
		case 'G45'://セキュリティコードが入力されていません。
			$message = 'セキュリティコード入力無';
			break;
		case 'G54'://1日利用回数または金額オーバーです。
			$message = '利用回数エラー';
			break;
		case 'G55'://1日利用限度額オーバーです。
			$message = '限度額オーバー';
			break;
		case 'G56'://クレジットカードが無効です。
			$message = '無効カード';
			break;
		case 'G60'://事故カードが入力されたことを意味します。
			$message = '事故カード';
			break;
		case 'G61'://無効カードが入力されたことを意味します。
			$message = '無効カード';
			break;
		case 'G65'://カード番号の入力が誤っていることを意味します。
			$message = 'カード番号エラー';
			break;
		case 'G68'://金額の入力が誤っていることを意味します。
			$message = '金額エラー';
			break;
		case 'G72'://ボーナス金額の入力が誤っていることを意味します。
			$message = 'ボーナス額エラー';
			break;
		case 'G74'://分割回数の入力が誤っていることを意味します。
			$message = '分割回数エラー';
			break;
		case 'G75'://分割払いの下限金額を回ってること意味します。
			$message = '分割金額エラー';
			break;
		case 'G78'://支払方法の入力が誤っていることを意味します。
			$message = '支払区分エラー';
			break;
		case 'G83'://有効期限の入力が誤っていることを意味します。
			$message = '有効期限エラー';
			break;
		case 'G84'://承認番号の入力が誤っていることを意味します。
			$message = '承認番号エラー';
			break;
		case 'G85'://CAFIS 代行中にエラーが発生したことを意味します。
			$message = 'CAFIS 代行エラー';
			break;
		case 'G92'://カード会社側で任意にエラーとしたい場合に発生します。
			$message = 'カード会社任意エラー';
			break;
		case 'G94'://サイクル通番が規定以上または数字でないことを意味します。
			$message = 'サイクル通番エラー';
			break;
		case 'G95'://カード会社の当該運用業務が終了していることを意味します。
			$message = '当該業務オンライン終了';
			break;
		case 'G96'://取扱不可のクレジットカードが入力されたことを意味します。
			$message = '事故カードデータエラー';
			break;
		case 'G97'://当該要求が拒否され、取扱不能を意味します。
			$message = '当該要求拒否';
			break;
		case 'G98'://接続されたクレジットカード会社の対象業務ではないことを意味します。
			$message = '当該自社対象業務エラー';
			break;
		case 'G99'://接続要求自社受付拒否を意味します。
			$message = '接続要求自社受付拒否';
			break;
		case 'W01'://弊社までお問い合わせください。
			$message = 'オンライン収納代行サービス設定エラー';
			break;
		case 'W02'://弊社までお問い合わせください。
			$message = '設定値エラー';
			break;
		case 'W03'://弊社までお問い合わせください。
			$message = 'オンライン収納代行サービス内部エラー（Web系）';
			break;
		case 'W04'://弊社までお問い合わせください。
			$message = 'システム設定エラー';
			break;
		case 'W05'://送信内容をご確認の上、再処理を行ってください。エラーが解消しない場合は、弊社までお問い合わせください。
			$message = '項目設定エラー';
			break;
		case 'W06'://弊社までお問い合わせください。
			$message = 'オンライン収納代行サービス内部エラー（DB系）';
			break;
		case 'W99'://弊社までお問い合わせください。
			$message = 'その他例外エラー';
			break;
		default:
			$message = $code;
		}
		return $message;
	}

	/**********************************************
	* エラーコード対応メッセージ
	* @param  $code
	* @return string $message
	***********************************************/
	protected function error_message( $code ) {

		switch( $code ) {
		case 'K01'://オンライン取引電文精査エラー
		case 'K02'://項目「MerchantId」精査エラー
		case 'K03'://項目「MerchantPass」精査エラー
		case 'K04'://項目「TenantId」精査エラー
		case 'K05'://項目「TransactionDate」精査エラー
		case 'K06'://項目「OperateId」精査エラー
		case 'K07'://項目「MerchantFree1」精査エラー
		case 'K08'://項目「MerchantFree2」精査エラー
		case 'K09'://項目「MerchantFree3」精査エラー
		case 'K10'://項目「ProcessId」精査エラー
		case 'K11'://項目「ProcessPass」精査エラー
		case 'K12'://項目「ProcessId」または「ProcessPass」不整合エラー
		case 'K14'://OperateId のステータス遷移不整合
		case 'K15'://会員参照（同一カード番号返戻）時の返戻対象会員数エラー
		case 'K22'://項目「PayType」精査エラー
		case 'K23'://項目「Amount」精査エラー
		case 'K25':
		case 'K26':
		case 'K27':
		case 'K30':
		case 'K31':
		case 'K32':
		case 'K33':
		case 'K34':
		case 'K35':
		case 'K36':
		case 'K37':
		case 'K39'://項目「SalesDate」精査エラー
		case 'K50'://項目「PayLimit」精査エラー
		case 'K53'://項目「ShouhinName」精査エラー
		case 'K54':
		case 'K55':
		case 'K56':
		case 'K57':
		case 'K58':
		case 'K59':
		case 'K60':
		case 'K61':
		case 'K64':
		case 'K65':
		case 'K66':
		case 'K67':
		case 'K68'://会員の登録機能は利用できません
		case 'K69'://会員ID の重複エラー
		case 'K70'://会員が無効状態ではありません
		case 'K71'://会員ID の認証エラー
		case 'K73'://会員が既に有効となっています
		case 'K74'://会員認証に連続して失敗し、ロックアウトされました
		case 'K75'://会員は有効でありません
		case 'K76':
		case 'K77':
		case 'K78':
		case 'K79'://会員判定エラー（Login 無効または会員無効）
		case 'K80'://会員ID 設定不一致（設定が必要）
		case 'K81'://会員ID 設定不一致（設定が不要）
		case 'K84'://会員ID の入力内容不正
		case 'K85'://会員パスワードの入力内容不正
		case 'K88'://元取引重複エラー
		case 'K95':
		case 'K96'://本システム通信障害発生（タイムアウト）
		case 'K98'://本システム内部で軽度障害が発生
		case 'K99'://その他例外エラー
		case 'KG8'://事業者認証に連続して失敗し、ロックアウトされました
		case 'KHZ'://利用可能なトークンがありません
		case 'KI8'://取引の対象が複数件存在します
		case 'C01'://弊社設定関連エラー
		case 'C02'://e-SCOTT システムエラー
		case 'C03'://e-SCOTT 通信エラー
		case 'C10'://支払区分エラー
		case 'C11'://ボーナス期間外エラー
		case 'C12'://分割回数エラー
		case 'C14'://取消済みエラー
		case 'C70'://弊社設定情報エラー
		case 'C71'://弊社設定情報エラー
		case 'C80'://カード会社センター閉局
		case 'C98'://その他例外エラー
		case 'G74'://分割回数エラー
		case 'G78'://支払区分エラー
		case 'G85'://CAFIS 代行エラー
		case 'G92'://カード会社任意エラー
		case 'G94'://サイクル通番エラー
		case 'G95'://当該業務オンライン終了
		case 'G98'://当該自社対象業務エラー
		case 'G99'://接続要求自社受付拒否
		case 'W01'://オンライン収納代行サービス設定エラー
		case 'W02'://設定値エラー
		case 'W03'://オンライン収納代行サービス内部エラー（Web系）
		case 'W04'://システム設定エラー
		case 'W05'://項目設定エラー
		case 'W06'://オンライン収納代行サービス内部エラー（DB系）
		case 'W99'://その他例外エラー
			$message = __('Sorry, please contact the administrator from the inquiry form.','usces');//恐れ入りますが、お問い合わせフォームより管理者にお問い合わせください。
			break;
		case 'K20'://項目「CardNo」精査エラー
		case 'K82'://カード番号の入力内容不正
		case 'C16'://カード番号エラー
		case 'C17'://カード番号体系エラー
		case 'G65'://カード番号エラー
			$message = __('Credit card number is not appropriate.','usces');//指定のカード番号が適切ではありません。
			break;
		case 'K21'://項目「CardExp」精査エラー
		case 'K83'://カード有効期限の入力内容不正
		case 'C13'://有効期限切れエラー
		case 'G83'://有効期限エラー
			$message = __('Card expiration date is not appropriate.','usces');//カード有効期限が適切ではありません。
			break;
		case 'K24'://項目「SecCd」精査エラー
		case 'G44'://セキュリティコード誤り
		case 'G45'://セキュリティコード入力無
			$message = __('Security code is not appropriate.','usces');//セキュリティコードが適切ではありません。
			break;
		case 'K40':
		case 'K41':
		case 'K42':
		case 'K43':
		case 'K44':
		case 'K45'://項目「KaiinId」精査エラー
		case 'K46'://項目「KaiinPass」精査エラー
		case 'K47'://項目「NewKaiinPass」精査エラー
		case 'K48':
		case 'KE0':
		case 'KE1':
		case 'KE2':
		case 'KE3':
		case 'KE4':
		case 'KE5':
		case 'KEA':
		case 'KEB':
		case 'KEC':
		case 'KED':
		case 'KEE':
		case 'KEF':
		case 'KHX'://項目「Token」精査エラー
		case 'G42'://暗証番号エラー
		case 'G84'://承認番号エラー
			$message = __('Credit card information is not appropriate.','usces');//カード情報が適切ではありません。
			break;
		case 'C15'://ボーナス金額下限エラー
			$message = __('Please change the payment method and error due to less than the minimum amount of bonus payment.','usces');//ボーナス払いの下限金額未満によるエラーのため、支払方法を変更して再処理を行ってください。
			break;
		case 'G12'://カード使用不可
		case 'G22'://"G22" が設定されている
		case 'G30'://取引判定保留
		case 'G56'://無効カード
		case 'G60'://事故カード
		case 'G61'://無効カード
		case 'G96'://事故カードデータエラー
		case 'G97'://当該要求拒否
			$message = __('Credit card is unusable.','usces');//クレジットカードが使用不可能です。
			break;
		case 'G54'://利用回数エラー
			$message = __('It is over 1 day usage or over amount.','usces');//1日利用回数または金額オーバーです。
			break;
		case 'G55'://限度額オーバー
			$message = __('It is over limit for 1 day use.','usces');//1日利用限度額オーバーです。
			break;
		case 'G68'://金額エラー
		case 'G72'://ボーナス額エラー
			$message = __('Amount is not appropriate.','usces');//金額が適切ではありません。
			break;
		case 'G75'://分割金額エラー
			$message = __('It is lower than the lower limit of installment payment.','usces');//分割払いの下限金額を下回っています。
			break;
		case 'K28':
			$message = __('Customer telephone number is not appropriate.','usces');//お客様電話番号が適切ではありません。
			break;
		case 'K51'://項目「NameKanji」精査エラー
			$message = __('Customer name is not entered properly.','usces');//お客様氏名が適切に入力されていません。
			break;
		case 'K52'://項目「NameKana」精査エラー
			$message = __('Customer kana name is not entered properly.','usces');//お客様氏名カナが適切に入力されていません。
			break;
		default:
			$message = __('Sorry, please contact the administrator from the inquiry form.','usces');//恐れ入りますが、お問い合わせフォームより管理者にお問い合わせください。
		}
		return $message;
	}

	/**********************************************
	* 重複送信不可
	* @param  $acting_flg, $rand
	* @return -
	***********************************************/
	public function duplication_control( $acting_flg, $rand ) {
		global $wpdb;
		$key = 'wc_trans_id';

		if( !usces_check_trans_id( $rand ) ) {
			exit();
		}
		usces_save_trans_id( $rand, $acting_flg );

		$order_meta_table_name = $wpdb->prefix.'usces_order_meta';
		$query = $wpdb->prepare( "SELECT order_id FROM $order_meta_table_name WHERE meta_value = %d AND meta_key = %s", $rand, $key );
		$order_id = $wpdb->get_var( $query );
		if( !$order_id ) {
			return;
		}

		if( $this->acting_flg_card == $acting_flg ) {
			$response_data['acting'] = $this->acting_card;
			$response_data['acting_return'] = 1;
			$response_data['result'] = 1;
			$response_data['nonce'] = wp_create_nonce( $this->paymod_id.'_transaction' );
			wp_redirect( add_query_arg( $response_data, USCES_CART_URL ) );
			exit();

		} elseif( $this->acting_flg_conv == $acting_flg ) {

		}
	}

	/**********************************************
	* ソケット通信接続
	* @param  $params
	* @return array $response_data
	***********************************************/
	public function connection( $params ) {

		$gc = new SLNConnection();
		$gc->set_connection_url( $params['send_url'] );
		$gc->set_connection_timeout( 60 );
		$response_list = $gc->send_request( $params['param_list'] );

		if( !empty($response_list) ) {
			$resdata = explode( "\r\n\r\n", $response_list );
			parse_str( $resdata[1], $response_data );
			if( !array_key_exists( 'ResponseCd', $response_data ) ) {
				$response_data['ResponseCd'] = 'NG';
			}

		} else {
			$response_data['ResponseCd'] = 'NG';
		}
		return $response_data;
	}
}

/**************************************************************************************/
//クラス定義 : SLNConnection
if( !class_exists('SLNConnection') ) {
	class SLNConnection
	{
		// プロパティ定義
		// 接続先URLアドレス
		private $connection_url;

		// 通信タイムアウト
		private $connection_timeout;

		// メソッド定義
		// コンストラクタ
		// 引数： なし
		// 戻り値： なし
		function __construct()
		{
			// プロパティ初期化
			$this->connection_url = "";
			$this->connection_timeout = 600;
		}

		// 接続先URLアドレスの設定
		// 引数： 接続先URLアドレス
		// 戻り値： なし
		function set_connection_url( $connection_url = "" )
		{
			$this->connection_url = $connection_url;
		}

		// 接続先URLアドレスの取得
		// 引数： なし
		// 戻り値： 接続先URLアドレス
		function get_connection_url()
		{
			return $this->connection_url;
		}

		// 通信タイムアウト時間（s）の設定
		// 引数： 通信タイムアウト時間（s）
		// 戻り値： なし
		function set_connection_timeout( $connection_timeout = 0 )
		{
			$this->connection_timeout = $connection_timeout;
		}

		// 通信タイムアウト時間（s）の取得
		// 引数： なし
		// 戻り値： 通信タイムアウト時間（s）
		function get_connection_timeout()
		{
			return $this->connection_timeout;
		}

		// リクエスト送信クラス
		// 引数： リクエストパラメータ（要求電文）配列
		// 戻り値： レスポンスパラメータ（応答電文）配列
		function send_request( &$param_list = array() )
		{
			$rValue = array();
			// パラメータチェック
			if( empty($param_list) === false ) {
				// 送信先情報の準備
				$url = parse_url( $this->connection_url );

				// HTTPデータ生成
				//$http_data = "";
				//reset( $param_list );
				//while( list($key, $value) = each($param_list) ) {
				//	$http_data .= ( ($http_data !== "") ? "&" : "" ).$key."=".$value;
				//}
				$http_data = http_build_query( $param_list );

				// HTTPヘッダ生成
				$http_header = "POST ".$url['path']." HTTP/1.1"."\r\n".
				"Host: ".$url['host']."\r\n".
				"User-Agent: SLN_PAYMENT_CLIENT_PG_PHP_VERSION_1_0"."\r\n".
				"Content-Type: application/x-www-form-urlencoded"."\r\n".
				"Content-Length: ".strlen($http_data)."\r\n".
				"Connection: close";

				// POSTデータ生成
				$http_post = $http_header."\r\n\r\n".$http_data;

				// 送信処理
				$errno = 0;
				$errstr = "";
				$hm = array();
				$context = stream_context_create(
					array(
						'ssl' => array( 'capture_session_meta' => true )
					)
				);

				// ソケット通信接続
				$fp = @stream_socket_client( 'tlsv1.2://'.$url['host'].':443', $errno, $errstr, $this->connection_timeout, STREAM_CLIENT_CONNECT, $context );
				if( $fp === false ) {
					usces_log('e-SCOTT send error : '.__('TLS 1.2 connection failed.','usces'), 'acting_transaction.log');//TLS1.2接続に失敗しました
					$fp = @stream_socket_client( 'ssl://'.$url['host'].':443', $errno, $errstr, $this->connection_timeout, STREAM_CLIENT_CONNECT, $context );
					if( $fp === false ) {
						usces_log('e-SCOTT send error : '.__('SSL connection failed.','usces'), 'acting_transaction.log');//SSL接続に失敗しました
						return $rValue;
					}
				}

				if( $fp !== false ) {
					// 接続後タイムアウト設定
					$result = socket_set_timeout( $fp, $this->connection_timeout );
					if( $result === true ) {
						// データ送信
						fwrite( $fp, $http_post );
						// 応答受信
						$response_data = "";
						while( !feof($fp) ) {
							$response_data .= fgets( $fp, 4096 );
						}

						// ソケット通信情報を取得
						$hm = stream_get_meta_data( $fp );
						// ソケット通信切断
						$result = fclose( $fp );
						if( $result === true ) {
							if( $hm['timed_out'] !== true ) {
								// レスポンスデータ生成
								$rValue = $response_data;
							} else {
								// エラー： タイムアウト発生
								usces_log('e-SCOTT send error : '.__('Timeout occurred during communication.','usces'), 'acting_transaction.log');//通信中にタイムアウトが発生しました
							}
						} else {
							// エラー： ソケット通信切断失敗
							usces_log('e-SCOTT send error : '.__('Failed to disconnect from SLN.','usces'), 'acting_transaction.log');//SLNとの切断に失敗しました
						}
					} else {
						// エラー： タイムアウト設定失敗 
						usces_log('e-SCOTT send error : '.__('Timeout setting failed.','usces'), 'acting_transaction.log');//タイムアウト設定に失敗しました
					}
				}
			} else {
				// エラー： パラメータ不整合
				usces_log('e-SCOTT send error : '.__('Invalid request parameter specification.','usces'), 'acting_transaction.log');//リクエストパラメータの指定が正しくありません
			}
			return $rValue;
		}
	}
}
