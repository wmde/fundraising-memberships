<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\LegacyConverters\LegacyToDomainConverter;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizer;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;

class DoctrineMembershipAnonymizer implements MembershipAnonymizer {

	private const int BATCH_SIZE = 20;

	public function __construct(
		private readonly MembershipRepository $membershipRepository,
		private readonly EntityManager $entityManager,
		private readonly PaymentAnonymizer $paymentAnonymizer
	) {
	}

	public function anonymizeAll(): int {
		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder->select( 'm' )
			->from( MembershipApplication::class, 'm' )
			->andWhere( 'm.isScrubbed = 0' )
			->andWhere( $queryBuilder->expr()->orX(
				$queryBuilder->expr()->isNotNull( 'm.export' ),
				$queryBuilder->expr()->in( 'm.status', [
					strval( MembershipApplication::STATUS_CANCELED ),
					strval( MembershipApplication::STATUS_CANCELLED_MODERATION )
				] )
			) );

		try {
			/** @var iterable<MembershipApplication> $memberships */
			$memberships = $queryBuilder->getQuery()->toIterable();
			$converter = new LegacyToDomainConverter();
			$count = 0;
			$paymentIds = [];

			foreach ( $memberships as $doctrineMembership ) {
				$membership = $converter->createFromLegacyObject( $doctrineMembership );
				$membership->scrubPersonalData();
				$this->membershipRepository->storeApplication( $membership );
				$paymentIds[] = $membership->getPaymentId();
				$count++;

				if ( $count % self::BATCH_SIZE === 0 ) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			}

			$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );

			return $count;
		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update memberships.', 0, $e );
		}
	}

	public function anonymizeWithIds( int ...$membershipIds ): void {
		$counter = 0;
		$paymentIds = [];
		foreach ( $membershipIds as $id ) {
			$membership = $this->membershipRepository->getMembershipApplicationById( $id );

			if ( $membership === null ) {
				throw new AnonymizationException( "Could not find donation with id $id" );
			}

			try {
				$membership->scrubPersonalData();
				$this->membershipRepository->storeApplication( $membership );
				$paymentIds[] = $membership->getPaymentId();

				$counter++;
				if ( $counter % self::BATCH_SIZE === 0 ) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			} catch ( \Exception $e ) {
				throw new AnonymizationException( 'Could not update memberships.', 0, $e );
			}
		}

		$this->paymentAnonymizer->anonymizeWithIds( ...$paymentIds );
	}
}
