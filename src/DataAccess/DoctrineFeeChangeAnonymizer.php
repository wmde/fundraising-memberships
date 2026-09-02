<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\FeeChangeAnonymizer;
use WMDE\Fundraising\MembershipContext\Domain\FeeChangeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\FeeChangeRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;

class DoctrineFeeChangeAnonymizer implements FeeChangeAnonymizer {

	private const int BATCH_SIZE = 20;

	public function __construct(
		private readonly FeeChangeRepository $feeChangeRepository,
		private readonly EntityManager $entityManager,
		private readonly PaymentAnonymizer $paymentAnonymizer
	) {
	}

	public function anonymizeAll(): int {
		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder->select( 'f' )
			->from( FeeChange::class, 'f' )
			->where( $queryBuilder->expr()->isNotNull( 'f.exportDate' ) );

		try {
			/** @var iterable<FeeChange> $feeChanges */
			$feeChanges = $queryBuilder->getQuery()->toIterable();

			$count = 0;
			$paymentIds = [];

			foreach ( $feeChanges as $feeChange ) {
				$paymentIds[] = $feeChange->getPaymentId();
				$feeChange->scrub();
				$this->feeChangeRepository->storeFeeChange( $feeChange );

				$count++;

				if ( $count % self::BATCH_SIZE === 0 ) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			}

			$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );

			return $count;

		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update fee changes.', 0, $e );
		}
	}

	public function anonymizeWithIds( string ...$feeChangeUuids ): void {
		$count = 0;
		$paymentIds = [];
		foreach ( $feeChangeUuids as $uuid ) {

			try {
				$feeChange = $this->feeChangeRepository->getFeeChange( $uuid );
			} catch ( FeeChangeException $e ) {
				throw new AnonymizationException( "Could not find fee change with UUID $uuid", 0, $e );
			}

			try {
				$paymentIds[] = $feeChange->getPaymentId();
				$feeChange->scrub();
				$this->feeChangeRepository->storeFeeChange( $feeChange );

				$count++;

				if ( $count % self::BATCH_SIZE === 0 ) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			} catch ( \Exception $e ) {
				throw new AnonymizationException( "Could not update fee changes", 0, $e );
			}
		}

		$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );
	}
}
