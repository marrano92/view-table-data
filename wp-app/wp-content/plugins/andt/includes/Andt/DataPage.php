<?php

/**
 * DataPage
 */

namespace Andt;

use Andt\FilterInput;

/**
 * Class DataPage
 *
 * @package Andt
 */
class DataPage {

	/**
	 * Protected vars
	 *
	 * @var string
	 */
	protected
		$title;

	/**
	 * Constructor
	 *
	 * @param string $title
	 */
	public function __construct( string $title ) {
		$this->title = $title;
	}

	/**
	 * Magic getter
	 *
	 * @param $key
	 *
	 * @return mixed
	 *
	 * @codeCoverageIgnore
	 */
	public function __get( $key ) {
		return $this->$key ?? null;
	}

	/**
	 * Factory
	 *
	 * @codeCoverageIgnore
	 *
	 * @return DataPage
	 */
	public static function init() {
		$title = _x( 'Data Table', 'DataPage', 'andt' );
		$obj   = new self( $title );

		if ( current_user_can( 'read' ) ) {
			$capability = 'read';

			add_menu_page( $obj->title, $obj->title, $capability, __CLASS__, [ $obj, 'render' ] );
			add_filter( 'andt/force_reload_clusters', '__return_true' );
			add_action( 'admin_enqueue_scripts', [ $obj, 'enqueue_parent_styles'] );
			add_action( 'admin_footer', [ $obj, 'my_action_javascript' ] );
		}

		return $obj;
	}

	function enqueue_parent_styles() {
		wp_enqueue_style( 'bootstrap-table-style', 'https://unpkg.com/bootstrap-table@1.15.4/dist/bootstrap-table.min.css' );
		wp_enqueue_style( 'bootstrap-style', 'https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css' );
		wp_enqueue_script( 'jquery-3', 'https://code.jquery.com/jquery-3.3.1.min.js' );
		wp_enqueue_script( 'bootstrap-table-script', 'https://unpkg.com/bootstrap-table@1.15.4/dist/bootstrap-table.min.js' );
		wp_enqueue_script( 'popper-script', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js' );
		wp_enqueue_script( 'bootstrap-script', 'https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js' );
	}

	/**
	 * Renders callback
	 *
	 * @codeCoverageIgnore
	 */
	public function render() {
		global $wpdb;
		$table      = 'contratti';
		$additional = 100;
		$this->handle_submissions();

		echo '<div class="wrap">', "\n", '<div class="icon32" id="icon-options-general"><br/>', "</div>\n";
		printf( "<h2>%s</h2>\n", $this->title );

		echo '<h3>' . _x( 'Select make to show the scores fo all models', 'Options Page', 'andt' ) . '</h3>';

		$total_rows = $wpdb->get_results( "SELECT COUNT(*) AS total FROM {$table}" );
		$filter = '';
		$pagination = 0;
		$input_pagination      = new FilterInput( INPUT_GET, 'pagination' );

		if ( $input_pagination->has_var() ) {
			$pagination = $input_pagination->get();
		}

		$input_filter      = new FilterInput( INPUT_GET, 'filter-by' );
		if ( $input_filter->has_var() ) {
			$filter = $input_filter->get();
		}
		$filter_arg = $filter ? '&filter-by' : $filter;

		echo '<h2 class="nav-tab-wrapper">';
		for ( $i = 0; $i <= (int) $total_rows[0]->total; $i ++ ) {
			$x = $i + $additional;

			if ( $x >= (int) $total_rows[0]->total ) {
				$x = (int) $total_rows[0]->total;
			}

			if ($pagination == $i) {
				printf( '<a href="%s" class="nav-tab nav-tab-active">Page - %s/%s</a>  ', esc_url( add_query_arg( [ 'pagination' => $i ] ) ), $i, $x );
			}else {
				printf( '<a href="%s" class="nav-tab">Page - %s/%s</a>  ', esc_url( add_query_arg( [ 'pagination' => $i ] ) ), $i, $x );
			}
			$i = $i + $additional;
		}
		echo '</h2>';

		echo '<form action="options-general.php?page=' . __CLASS__ . '&pagination=' . $pagination . $filter_arg .'" method="post">', "\n";

		$datas   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d, %d", $pagination, $additional ) );
		$columns = $wpdb->get_col( "DESC {$table}", 0 );

		if ( false ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( "This data doesn't exist in the database!!!", "andt" ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Nascondi questa notifica.</span></button></div>';

			return;
		}

		echo '<fieldset class="group">
				<table 
						class="form-table" 
						data-toggle="table" 
						data-search="true"
				>', PHP_EOL;

		echo '<thead><tr>';
		array_map( function ( $column ) {
			printf( '<th data-sortable="true">%s</th>', $column );
		}, $columns );
		echo '</tr></thead>';

		echo PHP_EOL.'<tbody>';

		foreach ( $datas as $row ) {
			echo '<tr>';
			array_map(
				function ( $column ) use ( $row ) {
					$name = str_replace([' ', '.'], '_', strtolower($column));
					printf(
						'<td><span style="display: none">%4$s</span> <input type="text" name="%1$s[%2$d][%3$s]" id="%3$s_%2$d" value="%4$s"></td>',
						'data_option',
						$row->veinum,
						$column,
						$row->$column
					);
				},
				$columns
			);

			echo '</tr>', PHP_EOL;
		}

		echo '</tbody></table></fieldset>', PHP_EOL;

		submit_button();

		echo "</form>\n</div>\n";
	}

	/**
	 * Handle the right submission
	 *
	 * @throws \Exception
	 */
	public function handle_submissions() {
		$post = new FilterInput( INPUT_POST, 'data_option' );
		if ( $post->has_var() ) {
			$data_table = $_POST['data_option'];
			$this->handle( $data_table );
		}
	}

	/**
	 * Handle Submission
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function handle( $data_table ) {
		global $wpdb;

		foreach ( $data_table as $veinum => $row ) {
			foreach ( $row as $column => $value ) {
				$table      = 'contratti';
				$data  = [ $column => $value ];
				$where = [ 'veinum' => $veinum ];
				$wpdb->update( $table, $data, $where );
			}
		}
		echo '<div class="notice notice-success is-dismissible"><p>Operation done.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Nascondi questa notifica.</span></button></div>';
	}

	public function my_action_javascript() {
		echo '<script type="text/javascript">
				jQuery(document).ready(function ($) {
					
				});
		</script>';
	}
}
