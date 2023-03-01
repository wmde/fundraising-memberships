<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;

class DoctrineMembershipApplicationPrePersistSubscriber implements EventSubscriber {

	public function __construct(
		private readonly MembershipTokenGenerator $updateTokenGenerator,
		private readonly MembershipTokenGenerator $accessTokenGenerator
	) {
	}

	public function getSubscribedEvents(): array {
		return [ Events::prePersist ];
	}

	public function prePersist( LifecycleEventArgs $args ): void {
		$entity = $args->getObject();

		if ( $entity instanceof MembershipApplication ) {
			$entity->modifyDataObject( function ( MembershipApplicationData $data ): void {
				if ( $this->isEmpty( $data->getAccessToken() ) ) {
					$data->setAccessToken( $this->accessTokenGenerator->generateToken() );
				}

				if ( $this->isEmpty( $data->getUpdateToken() ) ) {
					$data->setUpdateToken( $this->updateTokenGenerator->generateToken() );
				}
			} );
		}
	}

	private function isEmpty( ?string $stringOrNull ): bool {
		return $stringOrNull === null || $stringOrNull === '';
	}

}
