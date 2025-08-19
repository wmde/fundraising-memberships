<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Domain\FeeChangeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\FeeChangeRepository;

readonly class DoctrineFeeChangeRepository implements FeeChangeRepository {

	public function __construct(
		private EntityManager $entityManager
	) {
	}

	public function feeChangeExists( string $uuid ): bool {
		return $this->entityManager->getRepository( FeeChange::class )->count( [ 'uuid' => $uuid ] ) === 1;
	}

	public function getFeeChange( string $uuid ): FeeChange {
		$feeChange = $this->entityManager->getRepository( FeeChange::class )->findOneBy( [ 'uuid' => $uuid ] );

		if ( $feeChange === null ) {
			throw new FeeChangeException( "Could not find FeeChange with uuid {$uuid}" );
		}

		return $feeChange;
	}

	public function storeFeeChange( FeeChange $feeChange ): void {
		$this->entityManager->persist( $feeChange );
		$this->entityManager->flush();
	}
}
