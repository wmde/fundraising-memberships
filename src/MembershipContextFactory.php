<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

class MembershipContextFactory {

	/**
	 * @todo Make private when no outside code uses these constants
	 */
	public const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping/';
	public const DOMAIN_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DomainClassMapping/';

	/**
	 * @return string[]
	 */
	public function getDoctrineMappingPaths(): array {
		return [
			self::DOCTRINE_CLASS_MAPPING_DIRECTORY,
			self::DOMAIN_CLASS_MAPPING_DIRECTORY
		];
	}

	public function registerCustomTypes( Connection $connection ): void {
		$this->registerDoctrineModerationIdentifierType( $connection );
	}

	public function registerDoctrineModerationIdentifierType( Connection $connection ): void {
		static $isRegistered = false;
		if ( $isRegistered ) {
			return;
		}
		Type::addType( 'MembershipModerationIdentifier', 'WMDE\Fundraising\MembershipContext\DataAccess\DoctrineTypes\ModerationIdentifier' );
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping( 'MembershipModerationIdentifier', 'MembershipModerationIdentifier' );
		$isRegistered = true;
	}

}
