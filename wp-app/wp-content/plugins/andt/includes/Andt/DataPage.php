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

		if ( current_user_can( 'administrator' ) ) {
			$capability = 'administrator';

			add_options_page( $obj->title, $obj->title, $capability, __CLASS__, [ $obj, 'render' ] );
			add_filter( 'andt/force_reload_clusters', '__return_true' );
		}

		return $obj;
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

		for ( $i = 0; $i <= (int) $total_rows[0]->total; $i ++ ) {
			$x = $i + $additional;

			if ( $x >= (int) $total_rows[0]->total ) {
				$x = (int) $total_rows[0]->total;
			}

			printf( '<a href="%s">Page - %s/%s</a>  ', esc_url( add_query_arg( [ 'pagination' => $i ] ) ), $i, $x );
			$i = $i + $additional;
		}

		$pagination = 0;
		$input      = new FilterInput( INPUT_GET, 'pagination' );

		if ( $input->has_var() ) {
			$pagination = $input->get();
		}

		echo '<form action="options-general.php?page=' . __CLASS__ . '&pagination=' . $pagination . '" method="post">', "\n";


		$datas   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} LIMIT %d, %d", $pagination, $additional ) );
		$columns = $wpdb->get_col( "DESC {$table}", 0 );

		if ( false ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( "This data doesn't exist in the database!!!", "andt" ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Nascondi questa notifica.</span></button></div>';

			return;
		}

		echo '<fieldset class="group"><table class="form-table">', PHP_EOL;

		echo '<tr>';
		array_map( function ( $column ) {
			printf( '<th>%s</th>', $column );
		}, $columns );
		echo '</tr>';

		echo PHP_EOL;

		foreach ( $datas as $row ) {
			echo '<tr>';
			array_map(
				function ( $column ) use ( $row ) {
					printf(
						'<td><input type="text" name="%1$s[%2$d]" id="interior_%2$d" value="%3$s" ></td>',
						'data_option',
						$column,
						$row->$column
					);
				},
				$columns
			);

			echo '</tr>', PHP_EOL;
		}

		echo '</table></fieldset>', PHP_EOL;

		submit_button();

		echo "</form>\n</div>\n";
	}

	/**
	 * Handle the right submission
	 *
	 * @throws \Exception
	 */
	public function handle_submissions() {
		$post = new FilterInput( INPUT_POST, 'andt_options' );
		if ( $post->has_var() ) {
			$models = $_POST['andt_options'];
			$this->handle( $models );
		}
	}

	/**
	 * Handle Submission
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function handle( $models ) {
		global $wpdb;

		foreach ( $models as $key => $model ) {
			$submodelid = $key;
			foreach ( $model as $key => $value ) {
				$table = sprintf( '%sscoremodels', $wpdb->prefix );
				$data  = [ $key => $value ];
				$where = [ 'submodel_id' => $submodelid ];
				$wpdb->update( $table, $data, $where );
			}
		}
		echo '<div class="notice notice-success is-dismissible"><p>Operation done.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Nascondi questa notifica.</span></button></div>';
	}

}
