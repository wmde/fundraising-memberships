<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use DateInterval;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Configuration;
use Gedmo\Timestampable\TimestampableListener;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipContextFactory {

	/**
	 * Use this constant for MappingDriverChain::addDriver
	 */
	public const ENTITY_NAMESPACE = 'WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities';

	private const ENTITY_PATHS = [
		__DIR__ . '/DataAccess/DoctrineEntities/'
	];

	private array $config;
	private Configuration $doctrineConfig;

	// Singleton instances
	private ?MembershipTokenGenerator $tokenGenerator;

	public function __construct( array $config, Configuration $doctrineConfig ) {
		$this->config = $config;
		$this->doctrineConfig = $doctrineConfig;
		$this->tokenGenerator = null;
	}

	public function newMappingDriver(): MappingDriver {
		// We're only calling this for the side effect of adding Mapping/Driver/DoctrineAnnotations.php
		// to the AnnotationRegistry. When AnnotationRegistry is deprecated with Doctrine Annotations 2.0,
		// instantiate AnnotationReader directly instead.
		return $this->doctrineConfig->newDefaultAnnotationDriver( self::ENTITY_PATHS, false );
	}

	/**
	 * @return EventSubscriber[]
	 */
	public function newEventSubscribers(): array {
		return array_merge(
			$this->newDoctrineSpecificEventSubscribers(),
			[
				DoctrineMembershipApplicationPrePersistSubscriber::class => $this->newDoctrineMembershipPrePersistSubscriber(
				)
			]
		);
	}

	private function newDoctrineSpecificEventSubscribers(): array {
		$timestampableListener = new TimestampableListener();
		$timestampableListener->setAnnotationReader( new AnnotationReader() );
		return [
			TimestampableListener::class => $timestampableListener
		];
	}

	private function newDoctrineMembershipPrePersistSubscriber(): DoctrineMembershipApplicationPrePersistSubscriber {
		$tokenGenerator = $this->getTokenGenerator();
		return new DoctrineMembershipApplicationPrePersistSubscriber(
			$tokenGenerator,
			$tokenGenerator
		);
	}

	private function getTokenGenerator(): MembershipTokenGenerator {
		if ( $this->tokenGenerator === null ) {
			$this->tokenGenerator = new RandomMembershipTokenGenerator(
				$this->config['token-length'],
				new DateInterval( $this->config['token-validity-timestamp'] )
			);
		}

		return $this->tokenGenerator;
	}

	// Setters for replacing instances in tests

	public function setTokenGenerator( ?MembershipTokenGenerator $tokenGenerator ): void {
		$this->tokenGenerator = $tokenGenerator;
	}

}
