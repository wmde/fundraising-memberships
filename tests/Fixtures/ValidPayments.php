<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class ValidPayments {
	public const PAYMENT_AMOUNT_IN_EURO = 10;
	public const PAYMENT_BANK_ACCOUNT = '0648489890';
	public const PAYMENT_BANK_CODE = '50010517';
	public const PAYMENT_BANK_NAME = 'ING-DiBa';
	public const PAYMENT_BIC = 'INGDDEFFXXX';
	public const PAYMENT_IBAN = 'DE12500105170648489890';

	public static function newDirectDebitLegacyData(): LegacyPaymentData {
		$legacy = self::newDirectDebitPayment()->getLegacyData();
		return new LegacyPaymentData( $legacy->amountInEuroCents, $legacy->intervalInMonths, $legacy->paymentName, [
			...$legacy->paymentSpecificValues,
			'konto' => self::PAYMENT_BANK_ACCOUNT,
			'blz' => self::PAYMENT_BANK_CODE,
			'bankname' => self::PAYMENT_BANK_NAME
		],
		);
	}

	public static function newDirectDebitPayment(): Payment {
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
}
