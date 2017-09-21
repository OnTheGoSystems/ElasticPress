<?php

class EP_Index_Command_Data_Provider {
	/** @var wpdb */
	private $wpdb;

	/** @var string */
	private $post_types;

	/** @var string */
	private $post_status;

	/** @var int */
	private $offset;

	/** @var int */
	private $posts_per_page = 350;

	public function __construct( wpdb $wpdb, array $args ) {
		$this->wpdb = $wpdb;

		$this->init_post_types( $args );
		$this->post_status = implode( ',', array_map( array( $this, 'escape_params' ), ep_get_indexable_post_status() ) );

		if ( ! empty( $args['posts-per-page'] ) ) {
			$this->posts_per_page = absint( $args['posts-per-page'] );
		}
	}

	public function get_count() {
		$sql  = "
			SELECT COUNT(id) FROM {$this->wpdb->posts}
			WHERE post_type IN ({$this->post_types}) AND post_status IN ({$this->post_status})
		";

		$sql = apply_filters( 'ep_cli_index_count_query', $sql );

		return (int) $this->wpdb->get_var( $sql );
	}

	public function get_chunk( ) {
		$sql  = "
				SELECT id FROM {$this->wpdb->posts}
				WHERE post_type IN ({$this->post_types}) AND post_status IN ({$this->post_status})
				LIMIT %d,%d
			";
		$sql = $this->wpdb->prepare( $sql, $this->offset, $this->posts_per_page );

		$sql = apply_filters( 'ep_cli_index_ids_query', $sql );

		return $this->wpdb->get_col( $sql );
	}

	public function next() {
		$this->offset += $this->posts_per_page;
	}

	/**
	 * @return int
	 */
	public function get_offset() {
		return $this->offset;
	}

	/**
	 * @param array $args
	 */
	private function init_post_types( array $args ) {
		if ( ! empty( $args['post-type'] ) ) {
			$post_type = explode( ',', $args['post-type'] );
			$post_type = array_map( 'trim', $post_type );
		} else {
			$post_type = ep_get_indexable_post_types();
		}

		if ( is_array( $post_type ) ) {
			$post_type = array_values( $post_type );
		}

		$this->post_types = implode( ',', array_map( array( $this, 'escape_params' ), $post_type ) );
	}

	private function escape_params( $param ) {
		return $this->wpdb->prepare( '%s', $param );
	}
}