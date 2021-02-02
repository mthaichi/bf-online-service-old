<?php
/*
Plugin Name: Online Service
Plugin URI:
Description: オンライン礼拝を管理するプラグインです。
Author:BREADFISH
Author URI:http://breadfish.jp
Version: 0.3.2
*/

/**
 * Online_Service class.
 */
class Online_Service {
	
	private $version = '0.4.1';
	
	private $os_data = array();
	
	private $options = array();
	
	private static $instance;
	
	private $editor_template_part = 'online-service-options-edit';
	
	private $setting_group_name = 'online-service-setting-group';
	
	private $option_name = 'online_service_setting';
	
	private $is_acf_installed = FALSE;
	
	private $is_pdf_embedder_enabled = FALSE;
	
	private $entry_table_name = 'bf_os_entries';
	
	private $def_options = array( 
	'wait_image_url' => '', 
	'title_html' => '%message_date% <br class="sp_br">%message_title%',
	'embed_youtube_html' => '<iframe frameborder="0" height="450" src="%embed_url%" width="100%"></iframe>
<a href="%url%" target="_blank" rel="noopener noreferrer">Youtubeで見る</a>', 'wait_youtube_html' => '<a href="javascript:location.reload();"><img class="alignnone size-full" src="%image_url%" alt="" width="100%"></a>',
	'message_pdf_html' => '<a style="font-weight: bold; font-size: 1.3em;" href="%message_pdf%" target="_blank" rel="noopener noreferrer">%linktext%</a>',
	'shuho1_html' => '<a href="$pdf"><img class="alignnone size-full" style="border: 1px solid #ddd;" src="%image_url%" alt="" width="100%"></a>',
	'shuho2_html' => '<a href="$pdf"><img class="alignnone size-full" style="border: 1px solid #ddd;" src="%image_url%" alt="" width="100%"></a>',
	'message_mp3_html' => '[audio mp3="%message_mp3%"][/audio]' . "\n\n" . '<a href="%message_mp3%">MP3ダウンロード</a>',
	'entry_form_html' => '<div class="online-service-entry-box"><div class="title"><span class="online-service-entry-box-title"><span class="post-title">%title%</span> 出席報告</span></div>
<div class="inner"><p>オンライン礼拝に参加された方は氏名を入力して「出席しました」を押してください。</p>
<form class="os-entry-form">%name_input% %submit_button%<br>%result_message%</form></div></div>'
	 );
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		add_action( 'init', array(
			 $this,
			'custom_post_type' 
		) );
	}
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		return $this->get_instance();
	}
	
	/**
	 * get_instance function.
	 * 
	 * @access public
	 * @return void
	 */
	function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * activate function.
	 * 
	 * @access public
	 * @return void
	 */
	function activate() {
		flush_rewrite_rules();
	}
	
	/**
	 * custom_post_type function.
	 * 
	 * @access public
	 * @return void
	 */
	public function custom_post_type() {
		register_post_type( 'online_service', array(
			'label' => 'オンライン礼拝',
			'hierarchial' => FALSE,
			'show_ui' => true,
			'public' => true,
			'query_var' => FALSE,
			'menu_icon' => '',
			'supports' => array(
				 'title' 
			) 
		) );
	}
	
	/**
	 * initialize function.
	 * 
	 * @access public
	 * @return void
	 */
	public function initialize() {
		
		$target_date = $_GET['date'] ? htmlspecialchars($_GET['date']) : '';
		
		$args = array(
			'posts_per_page' => 1,
			'post_type' => array(
				 'online_service' 
			),
			'meta_key' => 'service_date',
			'orderby' => 'meta_value',
			'order' => 'DESC'
		);
		
		
		if ($target_date <> '') {
			$args['meta_query'] = array(
					'key' => 'service_date',
					'value' => date('Y/m/d', strtotime($target_date)),
					'compare' => '<=',
					'type' => 'DATE'
			);
		}
		
		
		$os_posts = get_posts( $args );
		if ( is_array( $os_posts ) && count( $os_posts ) > 0 ) {
			$this->wp_data = $os_posts[0];
		} else {
			$this->wp_data = (object) array(
				 'ID' => null,
				'service_date' => '',
				'message_title' => 'データがありません',
				'youtube_url' => '',
				'message_pdf' => '',
				'shuho_pdf' => '', 
				'shuho_image_1' => '', 
				'shuho_image_2' => ''
			);
		}
		
		
		if ( !is_null( $this->wp_data->ID ) ) {
			$this->os_data['youtube_url'] = get_post_meta( $this->wp_data->ID, 'youtube_url', true );
		}
		
		$this->options = get_option( $this->option_name );
		
		foreach ( (array) $this->options as $opt_key => $opt_val ) {
			if ( $opt_key && !$opt_val ) {
				$this->options[$opt_key] = $this->def_options[$opt_key];
			}
		}
		
		foreach ( (array) $this->def_options as $opt_key => $opt_val ) {
			if ( !array_key_exists( $opt_key, (array) $this->options ) ) {
				$this->options[$opt_key] = $this->def_options[$opt_key];
			}
		}
		
		if ( !$this->options['online_service_wait_image_url'] = get_option( 'online_service_wait_image_url' ) ) {
			$this->options['online_service_wait_image_url'] = plugins_url( 'images/wait_youtube.png', __FILE__ );
		}
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'pdf-embedder/pdf_embedder.php') ) {
			$this->is_pdf_embedder_enabled = true;
		}		
		add_shortcode( 'OS_service_date', array(
			 $this,
			'display_service_date' 
		) );	
		add_shortcode( 'OS_main_title', array(
			 $this,
			'display_main_title' 
		) );		
		add_shortcode( 'OS_title', array(
			 $this,
			'display_title' 
		) );
		add_shortcode( 'OS_youtube', array(
			 $this,
			'display_youtube' 
		) );
		add_shortcode( 'OS_shuho', array(
			 $this,
			'display_shuho' 
		) );
		add_shortcode( 'OS_message_pdf', array(
			 $this,
			'display_message_pdf' 
		) );
		add_shortcode( 'OS_message_mp3', array(
			 $this,
			'display_message_mp3' 
		) );
		add_shortcode( 'OS_archive', array(
			 $this,
			'display_archive' 
		) );
		add_shortcode( 'OS_entry_form', array(
			 $this,
			'display_entry_form' 
		) );				
		add_action( 'admin_menu', array(
			 $this,
			'add_admin_menu' 
		) );
		add_action( 'admin_init', array(
			 $this,
			'register_setting' 
		) );
		
		add_action( 'wp_head', array(
			$this,
			'display_entry_form_ajax'
		) );
		
		add_action( 'wp_ajax_bf_online_service_entry_register', array(
			$this,		
			'entry_register'
		) );
		add_action( 'wp_ajax_nopriv_bf_online_service_entry_register', array(
			$this,		
			'entry_register'
		) );
		
		add_action( 'add_meta_boxes', array(
			$this,
			'online_service_meta_box'
		) );


		
		if( class_exists('acf') ) { 
				$this->is_acf_installed = TRUE;
		}
		if ( ! $this->db_table_exists($this->get_entry_table_name())) {
			$this->activation();
		}
	}
	
	/**
	 * register_setting function.
	 * 
	 * @access public
	 * @return void
	 */
	public function register_setting() {
		register_setting( $this->setting_group_name, $this->option_name );
		register_setting( $this->setting_group_name, 'online_service_wait_image_url' );
		
	}
	
	/**
	 * add_admin_menu function.
	 * 
	 * @access public
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page( 'オンライン礼拝', 'オンライン礼拝', 'manage_options', __FILE__, array(
			$this,
			'output_editor' 
		) );
	}
	
	/**
	 * output_editor function.
	 * 
	 * @access public
	 * @return void
	 */
	public function output_editor() {	
		require_once __DIR__ . "/" . $this->editor_template_part . ".php";
	}
	
	
	/**
	 * アクティベーション。テーブルの作成を行う。
	 * 
	 * @access public
	 * @return void
	 */
	public function activation() {
	 
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $this->get_entry_table_name();
	 
		$sql = "CREATE TABLE $table_name (
		  id bigint NOT NULL AUTO_INCREMENT,
		  entry_name text NOT NULL,
		  post_id bigint unsigned NOT NULL,
		  ip_address text NOT NULL,
		  post_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";
	 
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );	
	 
	}	
	
	/**
	 * テーブルの有無を確認
	 * 
	 * @access public
	 * @return void
	 */	
	public function db_table_exists($table_name) {
		global $wpdb;
		$wpdb->get_row('SHOW TABLES FROM ' . DB_NAME . " LIKE '" . $table_name . "'");
		if( $wpdb->num_rows == 1 ){ 
			return TRUE;	
		} else {
			return FALSE;
		}		
	}
	
	
	public function display_service_date( $atts ) {
		if ( $this->wp_data->service_date ) {
			$service_date = date( 'Y/m/d', strtotime( $this->wp_data->service_date ) );
		} else {
			$service_date = '';
		}
		return $service_date;
	}

	/**
	 * display_title function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_main_title( $atts ) {		
		return $this->wp_data->post_title;
	}	
	
	/**
	 * display_title function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_title( $atts ) {
		if ( $this->wp_data->service_date ) {
			$service_date = date( 'Y/m/d', strtotime( $this->wp_data->service_date ) );
		} else {
			$service_date = '';
		}
		$message_title = $this->wp_data->message_title;
		
		
		$html = $this->options['title_html'];
		
		$html = $this->expantion_template( $html, array(
			'message_date' => $service_date,
			'message_title' => $message_title 
		) );
		
		return $html;
	}
	
	/**
	 * display_message_pdf function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_message_pdf( $atts ) {
		
		$message_pdf = wp_get_attachment_url( $this->wp_data->message_pdf );
		
		if ( $message_pdf == '') {
			return '';
		}
		
		$atts        = shortcode_atts( array(
			 'linktext' => '説教概要PDF' 
		), $atts );
		
		$html = $this->options['message_pdf_html'];
		
		$html = $this->expantion_template( $html, array(
			'linktext' => $atts['linktext'],
			'message_pdf' => $message_pdf 
		) );
		return $html;
	}
	
	/**
	 * display_youtube function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_youtube( $atts ) {
		
		$url = $this->wp_data->youtube_url;
		
		if ( $url != "" ) {
			
			$embed_url = $this->get_embed_youtube_url( $url );
			
			$html = $this->options['embed_youtube_html'];
			
			$html = $this->expantion_template( $html, array(
				'embed_url' => $embed_url,
				'url' => $url 
			) );
			
		} else {
			
			$image_url = get_option( 'online_service_wait_image_url' );
			
			if ( !$image_url || $image_url == '' ) {
				$image_url = plugins_url( 'images/wait_youtube.png', __FILE__ );
			}
			
			
			$html      = $this->options['wait_youtube_html'];
			$html = $this->expantion_template( $html, array(
				 'image_url' => $image_url 
			) );
			
		}
		return $html;
	}
	
	/**
	 * display_shuho function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_shuho( $atts ) {
		$pdf     = wp_get_attachment_url( $this->wp_data->shuho_pdf );
		$image_1 = wp_get_attachment_url( $this->wp_data->shuho_image_1 );
		
		if ( $pdf == '' ) {
			return 'データがありません';
		}
		
		if ( $this->is_pdf_embedder_enabled ) {
			return do_shortcode('<div style="width:99%">[pdf-embedder url="' . $pdf . '"]</div><a href="' . $pdf . '">PDFダウンロード</a>');
		}
		
		$html = $this->options['shuho1_html'];
		
		$html = $this->expantion_template( $html, array(
			'image_url' => $image_1,
			'pdf' => $pdf 
		) );
		
		$image2_html = '';
		if ( $this->wp_data->shuho_image_2 != "" ) {
			$image_2     = wp_get_attachment_url( $this->wp_data->shuho_image_2 );
			$image2_html = $this->options['shuho2_html'];
			$image2_html = $this->expantion_template( $image2_html, array(
				'image_url' => $image_2,
				'pdf' => $pdf 
			) );
		}
		return $html . $image2_html;
	}
	
	

	
	/**
	 * display_archive function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_archive( $atts ) {
		$args = array(
			'posts_per_page' => -1,
			'post_type' => array(
				 'online_service' 
			), 
			'meta_key' => 'service_date',
			'orderby' => 'meta_value',
			'order' => 'DESC'
		);
		
		$atts        = shortcode_atts( array(
			 'type' => 'youtube',
			 'date_after' => '',
			 'date_before' => '',
			 'th_width' => '50px'
		), $atts );
		
		
		if ($atts['date_after'] != '' && $atts['date_before'] != '') {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key' => 'service_date',
					'value' => date('Y/m/d', strtotime($atts['date_after'])),
					'compare' => '>=',
					'type' => 'DATE'
				),
				array(
					'key' => 'service_date',
					'value' => date('Y/m/d', strtotime($atts['date_before'])),
					'compare' => '<=',
					'type' => 'DATE'
				)
			);	
		} elseif ($atts['date_after'] != '') {
			$args['meta_query'] = array(
				'key' => 'service_date',
				'value' => date('Y/m/d', strtotime($atts['date_after'])),
				'compare' => '>=',
				'type' => 'DATE'
			);
		} elseif ($atts['date_before'] != '') {
			$args['meta_query'] = array(
				'key' => 'service_date',
				'value' => date('Y/m/d', strtotime($atts['date_before'])),
				'compare' => '<=',
				'type' => 'DATE'
			);	
		}
		
		
		$post_list = get_posts( $args );
		
		$html            = "";
		$retrieved_year  = 0;
		$retrieved_month = 0;
		
		$posts_by_month = array();
		foreach ( $post_list as $post ) {
			$post_year  = substr( $post->service_date, 0, 4 );
			$post_month = substr( $post->service_date, 4, 2 );
			$post_day   = substr( $post->service_date, 6, 2 );
			
			if ($atts['type'] == 'shuho') {
				$post_url = wp_get_attachment_url( $post->shuho_pdf );
			} else if ($atts['type'] == 'message_pdf') {
				$post_url = wp_get_attachment_url( $post->message_pdf );
			} else if ($atts['type'] == 'mp3') {
				$post_url = wp_get_attachment_url( $post->message_mp3 );
			} else if ($atts['type'] == 'page') {
				$post_url = $this->options['online_service_backnumber_url'] . "?date=" . $post_year . $post_month . $post_day;
			} else if ($atts['type'] == 'youtube') {
 				$post_url  = $post->youtube_url;
 			} else  {
 				$post_url  = $post->youtube_url;
 			}
 			
 			
 			
			if ( $retrieved_year != $post_year ) {
				if ( $retrieved_year > 0 ) {
					$html .= implode( ' ', array_reverse( $posts_by_month ) );
					$posts_by_month = array();
					$html .= "</td></tr>";
					
					$html .= "</tbody></table>";
				}
				
				$retrieved_year = $post_year;
				$html .= '<h3>' . $retrieved_year . '年</h3>';
				$html .= '<table><tbody>';
			}
			
			if ( $retrieved_month != $post_month ) {
				if ( $retrieved_month > 0 ) {
					$html .= implode( ' ', array_reverse( $posts_by_month ) );
					$posts_by_month = array();
					$html .= "</td></tr>";
				}
				$html .= '<tr><th style="width:' . $atts['th_width'] . '">' . intval( $post_month ) . "月</th><td>";
				$retrieved_month = $post_month;
			}
			
			$post_day_str = ( $post_url ? '<a href="' . $post_url . '">' : '' ) . intval( $post_day ) . '日' . ( $post_url ? '</a>' : '' );
			array_push( $posts_by_month, $post_day_str );
			
		}
		$html .= implode( ' ', array_reverse( $posts_by_month ) );
		
		$html .= "</td></tr></tbody></table>";
		
		return $html;
	}
	

	
	
	/**
	 * display_message_mp3 function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */
	public function display_message_mp3( $atts ) {
		
		$message_mp3 = wp_get_attachment_url( $this->wp_data->message_mp3 );
		$html        = $this->options['message_mp3_html'];
		
		if ( $message_mp3 == "" ) {
			return "<p>音声はまだ公開されていません。</p>";
		}
		
		$html = $this->expantion_template( $html, array(
			'message_mp3' => $message_mp3 
		) );		

		return do_shortcode( $html );
	}
	
	
	/**
	 * display_entry_form function.
	 * 
	 * @access public
	 * @param mixed $atts
	 * @return void
	 */	
	public function display_entry_form( $atts ) {
		$html        = $this->options['entry_form_html'];
		
		$name_input_html = '<input type="text" id="online_service_entry_name" value="" placeholder="氏名を入力ください">';
		$submit_button_html = '<input type="submit" value="出席しました" class="entry_submit">';
		$result_html = '<div class="online-service-entry-success"></div><div class="online-service-entry-error"></div>';
		
		if ( ! $this->wp_data->service_date ) {
			return "データがありません";
		}
		if ( $this->wp_data->service_date ) {
			$service_date = date( 'Y/m/d', strtotime( $this->wp_data->service_date ) );
		} else {
			$service_date = '';
		}
		
		
		$html = $this->expantion_template( $html, array(
			'title' => $this->wp_data->post_title,
			'name_input' => $name_input_html,
			'submit_button' => $submit_button_html,
			'result_message' => $result_html,
			'service_date' => $service_date
		) );		

		return do_shortcode( $html );
	
	
	}

	public function display_entry_form_ajax() {
	?>
	<style type="text/css">
		
	.online-service-entry-success {
     	 display:none;
		 color:green;
		 margin-top:10px;
		 padding: 4px;
		 border: 1px solid green;
		 background-color: #f1fff1;
	}
	
	.online-service-entry-error {
     	 display:none;
		 color:red;
		 margin-top:10px;
		 padding: 4px;
		 border: 1px solid red;
		 background-color: #fff1f1;
	}	

	.online-service-entry-box {
	  border: 2px solid #008800;
	  position: relative;
	  margin-top: 1em;
	  margin-bottom: 10px;
	}
	
	.online-service-entry-box div.title {
	  text-align: center;
	  position: absolute;
	  right: 0;
	  left: 0;
	  top: -16px;
	}
	
	.online-service-entry-box span.online-service-entry-box-title {
	  padding: 0 .5em;
	  background: #FFF;
	  color: #008800;
	}
	.online-service-entry-box .inner {
	  padding: 0 8px 10px 10px;
	  font-size:0.8em;
	  line-height: 1em;
	  margin: 10px 0 0 0;
	}
	
	@media (max-width:766px) {
		.online-service-entry-box span.online-service-entry-box-title {
			font-size:0.7em;
		}
	}	
	</style>
	    <script>
	        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	 
			jQuery(function() {
				jQuery('.entry_submit').click(function() {
					var self = this;
					
					jQuery('.entry_submit').prop('disabled', true);
					
				    jQuery.ajax({
				        type: 'POST',
				        url: ajaxurl,
				        data: {
				            'post_id' : <?php echo $this->wp_data->ID; ?>,
				            'entry_name' : jQuery('#online_service_entry_name').val(),
							'action' : 'bf_online_service_entry_register',
				        },
				        success: function( response ){
				         	var json = JSON.parse( response );
				         	jQuery('.online-service-entry-success').hide();
				         	jQuery('.online-service-entry-error').hide();

				         	if ( json.result == 1 ) {
					       

					         	jQuery('.online-service-entry-success').show().html(json.entry_name + 'さんの<strong>' + json.title + '</strong>の出席登録が完了しました。');
					         	jQuery('#online_service_entry_name').val("");
					        } else if ( json.result == -1 ) { 	
					         	jQuery('.online-service-entry-error').show().html(json.entry_name + 'さんはすでに出席登録されています。');

					        } else if ( json.result == -2 ) { 	
					         	jQuery('.online-service-entry-error').show().html('名前を入力してください。');
				         	} else {
					         	jQuery('.online-service-entry-error').show().html(json.entry_name + '礼拝出席登録はエラーのため完了できませんでした。');    
				         	}
							jQuery('.entry_submit').prop('disabled', false);
	         	
				        }  					 
					});
				});
			});	
	 
	    </script>
	<?php
	}
		
	public function entry_register () {
		global $wpdb;
		$post_id = htmlspecialchars($_POST['post_id']);
		$ipaddr = $_SERVER["REMOTE_ADDR"];
		$nowdate = date('Y-m-d h:m:s');	
		$entry_name = htmlspecialchars($_POST['entry_name']);
		
		$entry_name = preg_replace("/( |　)/", "", $entry_name);
		
		$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $this->get_entry_table_name() . " WHERE entry_name = %s and post_id = %d", $entry_name, $post_id));
		
		$entry_name = mb_substr($entry_name, 0, 20);
		$entry_name = mb_convert_kana($entry_name, "RNK");
		
		
		
		
		if (  $entry_name && $count ) {
			$result = -1;	
		} else if ( ! $entry_name ) {
			$result = -2;
		} else {
			$result = $wpdb->insert($this->get_entry_table_name(), array(
				'entry_name' => $entry_name,
				'ip_address' => $ipaddr,
				'post_id'    => $post_id,
				'post_date'	 => $nowdate
			));
		}
		
		if ( $this->wp_data->service_date ) {
			$service_date = date( 'Y/m/d', strtotime( $this->wp_data->service_date ) );
		} else {
			$service_date = '';
		}		
		
		echo json_encode(array(
			'title' => $this->wp_data->post_title,
			'service_date' => $service_date,
			'entry_name' => $entry_name,
			'result' => $result
		));
		die();
		
		
	}
		
		
	function online_service_meta_box( ){
		add_meta_box( 'online_service_meta_box', '出席者', array($this, 'online_service_meta_box_in'), 'online_service', 'side', 'low');
	}
		
	function online_service_meta_box_in( $post ){
		global $wpdb;
		
		$names = $wpdb->get_col($wpdb->prepare("SELECT entry_name FROM " . $this->get_entry_table_name() . " WHERE  post_id = %d and entry_name != '' ORDER BY entry_name" , $post->ID));

		?>
<textarea style="width:100%; height:600px">
<?php foreach($names as $one): ?>
<?php echo $one . "\n"; ?>
<?php endforeach; ?></textarea>
		
		
		

		<?php
	}
		
	
	/**
	 * get_embed_youtube_url function.
	 * 
	 * @access private
	 * @param mixed $url
	 * @return void
	 */
	private function get_embed_youtube_url( $url ) {
		return preg_replace( '/^(https?:\/\/).+\/([a-zA-Z0-9.\-_]+)/', '$1youtube.com/embed/$2', $url );
	}
	
	/**
	 * get_option function.
	 * 
	 * @access private
	 * @return void
	 */
	private function get_option() {
		return get_option( $this->option_name );
	}
	
	/**
	 * expantion_template function.
	 * 
	 * @access private
	 * @param mixed $html
	 * @param mixed $args
	 * @return void
	 */
	private function expantion_template( $html, $args ) {
		
		foreach ( $args as $key => $val ) {
			$html = preg_replace( "/%" . $key . "%/", $val, $html );
		}
		return $html;

	}
	
	
	/**
	 * DBで使うテーブル名を返す
	 * 
	 * @access public
	 * @return void
	 */
	function get_entry_table_name() {
	 
		global $wpdb;
		return $wpdb->prefix . $this->entry_table_name;	 
	}
	
	/**
	 * generate_upload_image_tag function.
	 * 
	 * @access private
	 * @param mixed $name
	 * @param mixed $value
	 * @return void
	 */
	private function generate_upload_image_tag( $name, $value ) {
?>
		<input name="<?php
		echo $name;
?>" type="text" value="<?php
		echo $value;
?>" />
		<input type="button" name="<?php
		echo $name;
?>_select" value="選択" />
		<input type="button" name="<?php
		echo $name;
?>_clear" value="クリア" />
		<div id="<?php
		echo $name;
?>_thumbnail" class="uploded-thumbnail">
		<?php
		if ( $value ):
?>
		  <img src="<?php
			echo $value;
?>" alt="選択中の画像" width="300">
		<?php
		endif;
?>
		</div>
		
		<script type="text/javascript">
		(function ($) {
 		
		  var custom_uploader;
		
		  $("input:button[name=<?php
		echo $name;
?>_select]").click(function(e) {
		
		      e.preventDefault();
		
		      if (custom_uploader) {
		
		          custom_uploader.open();
		          return;
		
		      }
		
		      custom_uploader = wp.media({
		
		          title: "画像を選択してください",
		
		          /* ライブラリの一覧は画像のみにする */
		          library: {
		              type: "image"
		          },
		
		          button: {
		              text: "画像の選択"
		          },
		
		          /* 選択できる画像は 1 つだけにする */
		          multiple: false
		
		      });
		
		      custom_uploader.on("select", function() {
		
		          var images = custom_uploader.state().get("selection");
		
		          /* file の中に選択された画像の各種情報が入っている */
		          images.each(function(file){
		
		              /* テキストフォームと表示されたサムネイル画像があればクリア */
		              $("input:text[name=<?php
		echo $name;
?>]").val("");
		              $("#<?php
		echo $name;
?>_thumbnail").empty();
		
		              /* テキストフォームに画像の URL を表示 */
		              $("input:text[name=<?php
		echo $name;
?>]").val(file.attributes.sizes.full.url);
		
		              /* プレビュー用に選択されたサムネイル画像を表示 */
		              $("#<?php
		echo $name;
?>_thumbnail").append('<img src="'+file.attributes.sizes.full.url+'" width="300" />');
		
		          });
		      });
		
		      custom_uploader.open();
		
		  });
		
		  /* クリアボタンを押した時の処理 */
		  $("input:button[name=<?php
		echo $name;
?>_clear]").click(function() {
		
		      $("input:text[name=<?php
		echo $name;
?>]").val("");
		      $("#<?php
		echo $name;
?>_thumbnail").empty();
		
		  });
		
		})(jQuery);
		</script>
		<?php
		wp_enqueue_media();
		wp_enqueue_script( 'media-grid' );
		wp_enqueue_script( 'media' );
	}
}

$os = new Online_Service();

add_action( 'plugins_loaded', array(
	 $os,
	'initialize' 
) );

register_activation_hook( __FILE__, array(
	 $os,
	'activate' 
) );


