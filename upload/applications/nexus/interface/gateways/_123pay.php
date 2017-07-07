<?php

require_once '../../../../init.php';
\IPS\Session\Front::i();

try {
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->nexusTransactionId );

	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING ) {
		throw new \OutofRangeException;
	}
} catch ( \OutOfRangeException $e ) {
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->nexusTransactionId, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https ) );
}

try {
	$response = $transaction->method->api(
		array(
			'RefNum' => \IPS\Request::i()->RefNum,
		), true
	);

	$result = json_decode( $response );

	if ( $result->status && $_SESSION['tid'] == $transaction->id && \IPS\Request::i()->RefNum == $_SESSION['RefNum'] ) {
		$transaction->gw_id = \IPS\Request::i()->RefNum . '_' . \IPS\Request::i()->RefNum;
		$transaction->save();
		$transaction->checkFraudRulesAndCapture( null );
		$transaction->sendNotification();
		\IPS\Session::i()->setMember( $transaction->invoice->member );
		\IPS\Output::i()->redirect( $transaction->url() );
	}

	throw new \OutofRangeException;
} catch ( \Exception $e ) {
	\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array(
		'_step' => 'checkout_pay',
		'err'   => $transaction->member->language()->get( 'gateway_err' )
	) ) );
}