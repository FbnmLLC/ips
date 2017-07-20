<?php

namespace IPS\nexus\Gateway;

if ( ! defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _ir123pay extends \IPS\nexus\Gateway {
	const ir123pay_SEND_URL = 'https://123pay.ir/api/v1/create/payment';
	const ir123pay_CHECK_URL = 'https://123pay.ir/api/v1/verify/payment\'';

	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress ) {
		if ( $amount->currency != 'IRR' ) {
			return false;
		}

		return parent::checkValidity( $amount, $billingAddress );
	}

	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = null ) {
		$transaction->save();
		$data = array(
			'amount'       => $transaction->amount->amount,
			'callback_url' => urlencode( (string) \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/ir123pay.php?nexusTransactionId=' . $transaction->id )
		);

		$response = $this->api( $data );
		$result   = json_decode( $response );
		if ( $result->status ) {
			$_SESSION['tid']    = $transaction->id;
			$_SESSION['RefNum'] = $result->RefNum;
			\IPS\Output::i()->redirect( \IPS\Http\Url::external( $result->payment_url ) );
		}

		throw new \RuntimeException;
	}

	public function capture( \IPS\nexus\Transaction $transaction ) {
		//
	}

	public function settings( &$form ) {
		$settings = json_decode( $this->settings, true );
		$form->add( new \IPS\Helpers\Form\Text( 'merchant_id', $this->id ? $settings['merchant_id'] : '', true ) );
	}

	public function testSettings( $settings ) {
		return $settings;
	}

	public function api( $data, $verify = false ) {
		$data['merchant_id'] = json_decode( $this->settings )->merchant_id;

		return intval( (string) \IPS\Http\Url::external( $verify ? self::ir123pay_CHECK_URL : self::ir123pay_SEND_URL )->request()->post( $data ) );
	}

}