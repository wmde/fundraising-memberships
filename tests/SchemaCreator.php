<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

class SchemaCreator {

	private SchemaTool $schemaTool;

	public function __construct( private readonly EntityManager $entityManager ) {
		$this->schemaTool = new SchemaTool( $this->entityManager );
	}

	public function createSchema(): void {
		$this->getSchemaTool()->createSchema( $this->getClassMetaData() );
	}

	public function dropSchema(): void {
		$this->getSchemaTool()->dropSchema( $this->getClassMetaData() );
	}

	private function getSchemaTool(): SchemaTool {
		return $this->schemaTool;
	}

	/**
	 * @return list<ClassMetadata<object>>
	 */
	private function getClassMetaData(): array {
		return $this->entityManager->getMetadataFactory()->getAllMetadata();
	}

}
