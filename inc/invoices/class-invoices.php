<?php

namespace Cauley\Invoices;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This plugin requires WordPress' );
}

if ( class_exists( 'Cauley\Invoices\Plugin' ) ) {
	class Invoices extends Plugin {

		public static $instance = null;

		private $table_name = 'cauley_invoices';

		public $schema = array(
			'object_id'             => array(
				'type'   => 'bigint(20)',
				'unique' => true,
			),
			'type'                  => 'varchar(20)',
			'due'                   => 'longtext',
			'title'                 => 'longtext',
			'author'                => 'longtext',
			'description'           => 'longtext',
			'invoice_total'         => 'bigint(20)',
			'tags'                  => 'longtext',
			'thumbnail_id'          => 'bigint(20)',
			'metadata'              => 'longtext',
		);

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
				self::$instance->init();
			}
			return self::$instance;
		}

		function init() {
			add_filter( 'mv_custom_schema', function( $tables ) {
				$tables[] = [
					'version'     => self::DB_VERSION,
					'table_name'  => $this->table_name,
					'schema'      => $this->schema,
				];
				return $tables;
			} );

			add_filter( 'mv_dbi_before_create_' . $this->table_name, [ $this, 'normalize_in' ] );
			add_filter( 'mv_dbi_before_update_' . $this->table_name, [ $this, 'normalize_in' ] );

			add_filter( 'mv_dbi_after_create_' . $this->table_name, [ $this, 'normalize_out' ] );
			add_filter( 'mv_dbi_after_select_' . $this->table_name, [ $this, 'normalize_out' ] );

		}

		function normalize_in( $data ) {
			if ( ! empty($data['metadata']) ) {
				$data['metadata'] = wp_json_encode( $data['metadata'] );
			}
			return $data;
		}

		function normalize_out ( $data ) {
			if ( ! empty($data->metadata) ) {
				$data->metadata = json_decode( $data->metadata );
			}
			$data->id            = intval( $data->id );
			if ( ! empty( $data->thumbnail_id ) ) {
				$data->thumbnail_uri = \wp_get_attachment_url( $data->thumbnail_id );
			}
			return $data;
		}
	}
}
