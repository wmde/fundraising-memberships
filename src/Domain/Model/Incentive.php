<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Model;

class Incentive {
	private ?int $id;

	public function __construct( private readonly string $name ) {
		$this->id = null;
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getName(): string {
		return $this->name;
	}

}
