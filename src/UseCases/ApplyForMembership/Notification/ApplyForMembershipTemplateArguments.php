<?php

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification;

class ApplyForMembershipTemplateArguments {

	/**
	 * @param int $id
	 * @param string $membershipType
	 * @param string $membershipFee
	 * @param int $membershipFeeInCents
	 * @param int $paymentIntervalInMonths
	 * @param string $paymentType
	 * @param string $salutation
	 * @param string $title
	 * @param string $lastName
	 * @param string $firstName
	 * @param bool $hasReceiptEnabled
	 * @param string[] $incentives
	 * @param array<string,boolean> $moderationFlags
	 */
	public function __construct(
			public readonly int $id,
			public readonly string $membershipType,
			public readonly string $membershipFee,
			public readonly int $membershipFeeInCents,
			public readonly int $paymentIntervalInMonths,
			public readonly string $paymentType,
			public readonly string $salutation,
			public readonly string $title,
			public readonly string $lastName,
			public readonly string $firstName,
			public readonly bool $hasReceiptEnabled,
			public readonly array $incentives,
			public readonly array $moderationFlags
		) {
	}

}
