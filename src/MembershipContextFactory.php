<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use DateInterval;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Timestampable\TimestampableListener;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Authorization\RandomMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationPrePersistSubscriber;

/**
 * @license GPL-2.0-or-later
 */
class MembershipContextFactory {

	/**
	 * Used by FunFunFactory in MappingDriverChain::addDriver
	 * @deprecated Use {@see ORMSetup::createXMLMetadataConfiguration()} with class mapping constant instead
	 */
	public const ENTITY_NAMESPACE = 'WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities';
	/**
	 * Used by FunFunFactory in MappingDriverChain::addDriver
	 * @deprecated Use {@see ORMSetup::createXMLMetadataConfiguration()} with class mapping constant instead
	 */
	public const DOMAIN_ENTITY_NAMESPACE = 'WMDE\Fundraising\MembershipContext\Domain\Model';

	public const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping/';
	public const DOMAIN_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DomainClassMapping/';

	private array $config;

	private ?MembershipTokenGenerator $tokenGenerator;

	public function __construct( array $config ) {
		$this->config = $config;
		$this->tokenGenerator = null;
	}

	public function newMappingDriver(): MappingDriverChain {
		$driver = new MappingDriverChain();
		$driver->addDriver( new XmlDriver( self::DOCTRINE_CLASS_MAPPING_DIRECTORY ), self::ENTITY_NAMESPACE );
		$driver->addDriver( new XmlDriver( self::DOMAIN_CLASS_MAPPING_DIRECTORY ), self::DOMAIN_ENTITY_NAMESPACE );
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
	 * @deprecated Use {@see ORMSetup::createXMLMetadataConfiguration} with the class mapping constants instead
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
