<?php

namespace Cauley\Invoices;

use Mediavine\API_Services;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This plugin requires WordPress' );
}

if ( class_exists( 'Cauley\Invoices\Plugin' ) ) {

	class Invoices_API extends Plugin {

		public static $instance = null;

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
				self::$instance->init();
			}
			return self::$instance;
		}

		function init() {
			add_action( 'rest_api_init', array( $this, 'routes' ) );
		}

		function validation( \WP_REST_Request $request, \WP_REST_Response $response ) {
			$sanitized = $request->sanitize_params();
			if ( is_wp_error( $sanitized ) ) {
				return new \WP_Error( 403, 'Entry Not Created', array( 'message' => 'Invalid Data' ) );
			}
			return $response;
		}

		function create( \WP_REST_Request $request, \WP_REST_Response $response ) {
			$params   = $request->get_params();

			$invoice = self::$models->cauley_invoices->create( $params );
			$data     = self::$api_services->prepare_item_for_response( $invoice, $request );
			$response = API_Services::set_response_data( $data, $response );
			$response->set_status( 201 );

			return $response;
		}

		function find( \WP_REST_Request $request, \WP_REST_Response $response ) {
			$params = $request->get_params();

			$query_args = array();
			if ( isset( $response->query_args ) ) {
				$query_args = $response->query_args;
			}

			$query_args['where'] = array();

			$invoices = self::$models->cauley_invoices->find( $query_args );

			$response->data = array();
			$data = array();
			if ( wp_is_numeric_array( $invoices ) ) {
				foreach ( $invoices as $invoice ) {
					$invoice->id            = intval( $invoice->id );
					$invoice->thumbnail_uri = \wp_get_attachment_url( $invoice->thumbnail_id );
					if ( ! empty( $invoice->metadata) ) {
						$invoice->metadata = json_decode( $invoice->metadata );
					}
					$data[] = $invoice;
				}
				$response->set_status( 200 );
			}

			$response = API_Services::set_response_data( $data, $response );
			$response->header( 'X-Total-Items', self::$models->cauley_invoices->get_count( $query_args ) );
			return $response;
		}

		public function find_one( \WP_REST_Request $request, \WP_REST_Response $response ) {
			$params   = $request->get_params();
			$invoice = self::$models->cauley_invoices->find_one( $params['id'] );

			if ( empty( $invoice ) ) {
				return new \WP_Error( 404, 'Entry Not Found', array( 'message' => 'Entry Not Found' ) );
			}

			$invoice->id = intval( $invoice->id );
			$response     = API_Services::set_response_data( $invoice, $response );
			$response->set_status( 200 );

			return $response;
		}

		public function destroy( \WP_REST_Request $request, \WP_REST_Response $response ) {
			$deleted  = self::$models->cauley_invoices->delete( $response->data['data']->id );
			$data     = self::$api_services->prepare_item_for_response( $deleted, $request );
			$response = API_Services::set_response_data( $data, $response );
			$response->set_status( 204 );

			return $response;
		}

		function routes() {
			register_rest_route( "$this->api_route/$this->api_version",
				'/invoices',
				array(
					array(
						'methods'             => \WP_REST_Server::EDITABLE,
						'callback'            => function ( \WP_REST_Request $request ) {
							return \Mediavine\API_Services::middleware(
								array(
									array( $this, 'validation'),
									array( $this, 'create' ),
								),
								$request
							);
						},
						'permission_callback' => function( \WP_REST_Request $request ) {
							return true;
						},
					),
					array(
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => function ( \WP_REST_Request $request ) {
							return \Mediavine\API_Services::middleware(
								array(
									array( self::$api_services, 'process_pagination' ),
									array( $this, 'find' ),
								),
								$request
							);
						},
						// 'permission_callback' => array( self::$api_services, 'permitted' ),
						'permission_callback' => function( \WP_REST_Request $request ) {
							return true;
						},
					),
				)
			);

			register_rest_route( "$this->api_route/$this->api_version", '/invoices/(?P<id>\d+)', array(
					array(
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => function( \WP_REST_Request $request ) {
							return \Mediavine\API_Services::middleware(
								array(
									array( $this, 'find_one' ),
								), $request
							);
						},
						'permission_callback' => function( \WP_REST_Request $request ) {
							return true;
						},
					),
					array(
						'methods'             => \WP_REST_Server::DELETABLE,
						'callback'            => function( \WP_REST_Request $request ) {
							return \Mediavine\API_Services::middleware(
								array(
									array( $this, 'find_one' ),
									array( $this, 'destroy' ),
								), $request
							);
						},
						'permission_callback' => function( \WP_REST_Request $request ) {
							return true;
						},
					),
					// array(
					// 	'methods'             => \WP_REST_Server::EDITABLE,
					// 	'callback'            => function ( \WP_REST_Request $request ) {
					// 		return \Mediavine\API_Services::middleware(
					// 			array(
					// 				array( $this->api, 'find_one' ),
					// 				array( $this->api, 'update' ),
					// 			),
					// 			$request
					// 		);
					// 	},
					// 	'permission_callback' => array( self::$api_services, 'permitted' ),
					// ),
				)
			);
		}
	}
}
