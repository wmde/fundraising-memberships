<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Data;

use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\DummyPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookablePayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

class ValidPayments {
	public const PAYMENT_AMOUNT_IN_EURO = 10;
	public const PAYMENT_BIC = 'INGDDEFFXXX';
	public const PAYMENT_IBAN = 'DE12500105170648489890';
	public const FIRST_PAYMENT_DATE = '2021-02-01';

	public static function newPayment(): Payment {
		return DirectDebitPayment::create(
			id: 1,
			amount: Euro::newFromFloat( self::PAYMENT_AMOUNT_IN_EURO ),
			interval: PaymentInterval::Quarterly,
			iban: new Iban( self::PAYMENT_IBAN ),
			bic: self::PAYMENT_BIC
		);
	}

	public static function newPaymentWithHighAmount( PaymentInterval $paymentInterval, float $amount ): Payment {
		return DirectDebitPayment::create(
			id: 1,
			amount: Euro::newFromFloat( $amount ),
			interval: $paymentInterval,
			iban: new Iban( self::PAYMENT_IBAN ),
			bic: self::PAYMENT_BIC
		);
	}

	public static function newPayPalPayment(): Payment {
		return new PayPalPayment(
			id: 1,
			amount: Euro::newFromFloat( self::PAYMENT_AMOUNT_IN_EURO ),
			interval: PaymentInterval::Quarterly
		);
	}

	public static function newBookedPayPalPayment(): Payment {
		$payment = self::newPayPalPayment();
		if ( !( $payment instanceof BookablePayment ) ) {
			throw new \LogicException( 'Please ensure PayPalPayment implements BookablePayment' );
		}
		$payment->bookPayment(
			self::newPayPalData(),
			new DummyPaymentIdRepository()
		);
		return $payment;
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function newPayPalData(): array {
		return [
			'address_city' => 'Chicago',
			'address_country_code' => 'US of EEHHH',
			'address_name' => 'Joe Dirt',
			'address_status' => 'Upside Down',
			'address_street' => 'Sesame',
			'address_zip' => '666',
			'first_name' => 'Joe',
			'item_number' => 1,
			'last_name' => 'Dirt',
			'mc_currency' => 'EUR',
			'mc_fee' => '2.70',
			'mc_gross' => '2.70',
			'payer_email' => 'foerderpp@wikimedia.de',
			'payer_id' => '42',
			'payer_status' => 'verified',
			'payment_date' => self::FIRST_PAYMENT_DATE,
			'payment_status' => 'processed',
			'payment_type' => 'instant',
			'settle_amount' => '2.70',
			'subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'txn_id' => '4242',
		];
	}
}
