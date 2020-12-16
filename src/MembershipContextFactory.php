<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use DateInterval;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Timestampable\TimestampableListener;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipContextFactory {

	/**
	 * Use this constant for MappingDriverChain::addDriver
	 */
	public const ENTITY_NAMESPACE = 'WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities';

	public const DOMAIN_ENTITY_NAMESPACE = 'WMDE\Fundraising\MembershipContext\Domain\Model';

	private const ENTITY_PATHS = [
		__DIR__ . '/DataAccess/DoctrineEntities/'
	];

	private const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping';

	private array $config;
	private Configuration $doctrineConfig;

	// Singleton instances
	private ?MembershipTokenGenerator $tokenGenerator;

	public function __construct( array $config, Configuration $doctrineConfig ) {
		$this->config = $config;
		$this->doctrineConfig = $doctrineConfig;
		$this->tokenGenerator = null;
	}

	public function newMappingDriver(): MappingDriverChain {
		$driver = new MappingDriverChain();
		// We're only calling this for the side effect of adding Mapping/Driver/DoctrineAnnotations.php
		// to the AnnotationRegistry. When AnnotationRegistry is deprecated with Doctrine Annotations 2.0,
		// instantiate AnnotationReader directly instead.
		$driver->addDriver( $this->doctrineConfig->newDefaultAnnotationDriver( self::ENTITY_PATHS, false ), self::ENTITY_NAMESPACE );
		$driver->addDriver( new XmlDriver( self::DOCTRINE_CLASS_MAPPING_DIRECTORY ), self::DOMAIN_ENTITY_NAMESPACE );
		return $driver;
	}

	/**
	 * Append the mapping drivers from this context to another MappingDriverChain.
	 *
	 * When calling from the FunFunFactory, use this method instead of newMappingDriver,
	 * otherwise the "domain entities" will be left out!
	 *
	 * This is a transitional method that is only needed as long as we have a mix of the annotation driver
	 * for the legacy Membership entity and the XML-annotated Domain entities
	 *
	 * @param MappingDriverChain $visitingChain
	 */
	public function visitMappingDriver( MappingDriverChain $visitingChain ): void {
		foreach ( $this->newMappingDriver()->getDrivers() as $namespace => $driver ) {
			$visitingChain->addDriver( $driver, $namespace );
		}
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

	/**
	 * This setter should only be used in tests, to replace the default implementation.
	 *
	 * @param MembershipTokenGenerator|null $tokenGenerator
	 */
	public function setTokenGenerator( ?MembershipTokenGenerator $tokenGenerator ): void {
		$this->tokenGenerator = $tokenGenerator;
	}

}
