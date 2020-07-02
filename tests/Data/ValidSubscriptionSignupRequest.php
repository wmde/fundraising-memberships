<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Data;

use WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionSignupNotification\SubscriptionSignupRequest;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class ValidSubscriptionSignupRequest {

	public const APPLICATION_ID = 1;
	public const CURRENCY_CODE = 'EUR';
	public const SUBSCRIPTION_ID = 'subscr_id';
	public const SUBSCRIPTION_DATE = '12:34:56 Jan 25, 2017 PST';
	public const TRANSACTION_TYPE = 'subscr_signup';
	public const PAYMENT_TYPE = 'instant';
	public const PAYER_ID = 'payer_id';
	public const PAYER_STATUS = 'verified';
	public const PAYER_ADDRESS_STATUS = 'confirmed';
	public const PAYER_FIRST_NAME = 'Hank';
	public const PAYER_LAST_NAME = 'Scorpio';
	public const PAYER_ADDRESS_NAME = 'Hank Scorpio';
	public const PAYER_ADDRESS_STREET = 'Hammock District';
	public const PAYER_ADDRESS_POSTAL_CODE = '12345';
	public const PAYER_ADDRESS_CITY = 'Cypress Creek';
	public const PAYER_ADDRESS_COUNTRY = 'US';
	public const PAYER_EMAIL = 'hank.scorpio@globex.com';

	public static function newValidRequest(): SubscriptionSignupRequest {
		$request = new SubscriptionSignupRequest();
		$request->setSubscriptionId( self::SUBSCRIPTION_ID );
		$request->setSubscriptionDate( self::SUBSCRIPTION_DATE );
		$request->setTransactionType( self::TRANSACTION_TYPE );
		$request->setCurrencyCode( self::CURRENCY_CODE );

		$request->setPaymentType( self::PAYMENT_TYPE );
		$request->setPayerId( self::PAYER_ID );
		$request->setPayerStatus( self::PAYER_STATUS );
		$request->setPayerAddressStatus( self::PAYER_ADDRESS_STATUS );
		$request->setPayerFirstName( self::PAYER_FIRST_NAME );
		$request->setPayerLastName( self::PAYER_LAST_NAME );
		$request->setPayerAddressName( self::PAYER_ADDRESS_NAME );
		$request->setPayerAddressStreet( self::PAYER_ADDRESS_STREET );
		$request->setPayerAddressPostalCode( self::PAYER_ADDRESS_POSTAL_CODE );
		$request->setPayerAddressCity( self::PAYER_ADDRESS_CITY );
		$request->setPayerAddressCountry( self::PAYER_ADDRESS_COUNTRY );
		$request->setPayerEmail( self::PAYER_EMAIL );

		$request->setApplicationId( self::APPLICATION_ID );

		return $request;
	}

}
